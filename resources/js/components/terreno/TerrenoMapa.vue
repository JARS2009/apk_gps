<script setup lang="ts">
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { onMounted, onUnmounted, ref, watch } from 'vue';
import type { Coordenada } from '@/types/models/terreno';

const props = defineProps<{
    modelValue: Coordenada[];
}>();

const emit = defineEmits<{
    'update:modelValue': [coords: Coordenada[]];
    'update:area': [area: number | null];
}>();

const mapEl = ref<HTMLDivElement | null>(null);
type Modo = 'idle' | 'dibujando' | 'listo';
const modo = ref<Modo>('idle');

let map: maplibregl.Map | null = null;
let vertices: [number, number][] = []; // [lng, lat]

// ── Área (Shoelace esférica) ───────────────────────────────────────────────
function calcularArea(verts: [number, number][]): number {
    if (verts.length < 3) {
        return 0;
    }

    const R = 6378137;
    let area = 0;

    for (let i = 0; i < verts.length; i++) {
        const j = (i + 1) % verts.length;
        const xi = (verts[i][0] * Math.PI) / 180;
        const yi = Math.log(
            Math.tan(Math.PI / 4 + (verts[i][1] * Math.PI) / 360),
        );
        const xj = (verts[j][0] * Math.PI) / 180;
        const yj = Math.log(
            Math.tan(Math.PI / 4 + (verts[j][1] * Math.PI) / 360),
        );
        area += xi * yj - xj * yi;
    }

    return Math.round((Math.abs((area / 2) * R * R) / 10000) * 100) / 100;
}

// ── GeoJSON ────────────────────────────────────────────────────────────────
function polyGeoJSON(
    verts: [number, number][],
    cerrado: boolean,
): GeoJSON.Feature {
    const ring = cerrado ? [...verts, verts[0]] : verts;

    return {
        type: 'Feature',
        geometry: { type: 'Polygon', coordinates: [ring] },
        properties: {},
    };
}

function pointsGeoJSON(verts: [number, number][]): GeoJSON.FeatureCollection {
    return {
        type: 'FeatureCollection',
        features: verts.map((v, i) => ({
            type: 'Feature',
            id: i,
            geometry: { type: 'Point', coordinates: v },
            properties: { index: i },
        })),
    };
}

function refreshSources(): void {
    if (!map?.getSource('polygon')) {
        return;
    }

    (map.getSource('polygon') as maplibregl.GeoJSONSource).setData(
        vertices.length >= 2
            ? (polyGeoJSON(
                  vertices,
                  modo.value === 'listo',
              ) as GeoJSON.Feature<GeoJSON.Geometry>)
            : ({
                  type: 'FeatureCollection',
                  features: [],
              } as GeoJSON.FeatureCollection),
    );
    (map.getSource('vertices') as maplibregl.GeoJSONSource).setData(
        pointsGeoJSON(vertices),
    );
}

function emitir(): void {
    const coords = vertices.map(([lng, lat]) => ({
        lat: Math.round(lat * 1e7) / 1e7,
        lng: Math.round(lng * 1e7) / 1e7,
    }));
    emit('update:modelValue', coords);
    emit('update:area', calcularArea(vertices));
}

// ── Acciones de dibujo ─────────────────────────────────────────────────────
function iniciarDibujo(): void {
    vertices = [];
    modo.value = 'dibujando';
    map!.getCanvas().style.cursor = 'crosshair';
    refreshSources();
    emit('update:modelValue', []);
    emit('update:area', null);
}

function finalizarDibujo(): void {
    if (vertices.length < 3) {
        return;
    }

    modo.value = 'listo';
    map!.getCanvas().style.cursor = '';
    refreshSources();
    emitir();
}

function borrar(): void {
    vertices = [];
    modo.value = 'idle';
    map!.getCanvas().style.cursor = '';
    refreshSources();
    emit('update:modelValue', []);
    emit('update:area', null);
}

// ── Eventos del mapa ───────────────────────────────────────────────────────
let lastClickMs = 0;

function handleClick(e: maplibregl.MapMouseEvent): void {
    if (modo.value !== 'dibujando') {
        return;
    }

    const now = Date.now();

    if (now - lastClickMs < 350) {
        // Doble clic → cerrar polígono
        finalizarDibujo();

        return;
    }

    lastClickMs = now;
    vertices = [...vertices, [e.lngLat.lng, e.lngLat.lat]];
    refreshSources();
}

// ── Drag de vértices ───────────────────────────────────────────────────────
let dragging = -1;

function onVertexDown(e: maplibregl.MapLayerMouseEvent): void {
    if (modo.value !== 'listo') {
        return;
    }

    e.preventDefault();
    dragging = (e.features?.[0]?.properties?.index as number) ?? -1;
    map!.getCanvas().style.cursor = 'grabbing';
    map!.dragPan.disable();
}

function onMouseMove(e: maplibregl.MapMouseEvent): void {
    if (dragging < 0) {
        return;
    }

    vertices[dragging] = [e.lngLat.lng, e.lngLat.lat];
    refreshSources();
}

function onMouseUp(): void {
    if (dragging < 0) {
        return;
    }

    dragging = -1;
    map!.getCanvas().style.cursor = '';
    map!.dragPan.enable();
    emitir();
}

// ── Capas de tiles ─────────────────────────────────────────────────────────
const CAPAS = {
    satelite: {
        label: 'Satélite',
        tiles: [
            'https://mt0.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
            'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        ],
        maxzoom: 21,
    },
    hibrido: {
        label: 'Híbrido',
        tiles: [
            'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
            'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        ],
        maxzoom: 21,
    },
    relieve: {
        label: 'Relieve',
        tiles: [
            'https://mt0.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
            'https://mt1.google.com/vt/lyrs=p&x={x}&y={y}&z={z}',
        ],
        maxzoom: 21,
    },
    calles: {
        label: 'Calles',
        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
        maxzoom: 19,
    },
} as const;

type CapaId = keyof typeof CAPAS;
const capaActiva = ref<CapaId>('satelite');

function cambiarCapa(id: CapaId): void {
    capaActiva.value = id;
    (
        map?.getSource('base-tiles') as maplibregl.RasterTileSource | undefined
    )?.setTiles(CAPAS[id].tiles as unknown as string[]);
}

function cargarPoligono(coords: Coordenada[]): void {
    if (!map || coords.length < 3) {
        return;
    }

    vertices = coords.map((c) => [c.lng, c.lat]);
    modo.value = 'listo';
    refreshSources();
    const lngs = coords.map((c) => c.lng);
    const lats = coords.map((c) => c.lat);
    map.fitBounds(
        [
            [Math.min(...lngs), Math.min(...lats)],
            [Math.max(...lngs), Math.max(...lats)],
        ],
        {
            padding: 60,
            duration: 400,
        },
    );
}

// ── Lifecycle ──────────────────────────────────────────────────────────────
onMounted(() => {
    map = new maplibregl.Map({
        container: mapEl.value!,
        style: {
            version: 8,
            glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
            sources: {
                'base-tiles': {
                    type: 'raster',
                    tiles: CAPAS.satelite.tiles as unknown as string[],
                    tileSize: 256,
                    maxzoom: CAPAS.satelite.maxzoom,
                    attribution: '© Google',
                },
            },
            layers: [
                {
                    id: 'base-layer',
                    type: 'raster',
                    source: 'base-tiles',
                    minzoom: 0,
                    maxzoom: 24,
                },
            ],
        },
        center: [-78.2595745, -7.275875],
        zoom: 15,
        maxZoom: 22,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-left');

    map.on('load', () => {
        // Fuentes GeoJSON
        map!.addSource('polygon', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: [] },
        });
        map!.addSource('vertices', {
            type: 'geojson',
            data: { type: 'FeatureCollection', features: [] },
        });

        // Relleno del polígono
        map!.addLayer({
            id: 'poly-fill',
            type: 'fill',
            source: 'polygon',
            paint: { 'fill-color': '#2563eb', 'fill-opacity': 0.18 },
        });
        // Borde
        map!.addLayer({
            id: 'poly-line',
            type: 'line',
            source: 'polygon',
            paint: {
                'line-color': '#2563eb',
                'line-width': 2,
                'line-dasharray': [1, 0],
            },
        });
        // Vértices (círculos blancos con borde azul)
        map!.addLayer({
            id: 'verts',
            type: 'circle',
            source: 'vertices',
            paint: {
                'circle-radius': 6,
                'circle-color': '#fff',
                'circle-stroke-width': 2,
                'circle-stroke-color': '#2563eb',
            },
        });

        // Eventos
        map!.on('click', handleClick);
        map!.on('mousemove', onMouseMove);
        map!.on('mouseup', onMouseUp);
        map!.on('mousedown', 'verts', onVertexDown);
        map!.on('mouseenter', 'verts', () => {
            if (modo.value === 'listo') {
                map!.getCanvas().style.cursor = 'grab';
            }
        });
        map!.on('mouseleave', 'verts', () => {
            if (dragging < 0) {
                map!.getCanvas().style.cursor =
                    modo.value === 'dibujando' ? 'crosshair' : '';
            }
        });

        if (props.modelValue.length >= 3) {
            cargarPoligono(props.modelValue);
        }
    });
});

watch(
    () => props.modelValue,
    (coords) => {
        if (!map || vertices.length > 0) {
            return;
        }

        if (coords.length >= 3) {
            cargarPoligono(coords);
        }
    },
    { deep: true },
);

onUnmounted(() => {
    map?.remove();
    map = null;
});
</script>

<template>
    <div class="grid gap-2">
        <!-- Selector de capa + botones de dibujo -->
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex gap-1">
                <button
                    v-for="(cfg, id) in CAPAS"
                    :key="id"
                    type="button"
                    class="rounded-md border px-3 py-1 text-xs transition-colors"
                    :class="
                        capaActiva === id
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-input bg-background hover:bg-muted'
                    "
                    @click="cambiarCapa(id as CapaId)"
                >
                    {{ cfg.label }}
                </button>
            </div>

            <div class="ml-auto flex gap-1">
                <button
                    v-if="modo !== 'dibujando'"
                    type="button"
                    class="rounded-md border border-primary bg-primary px-3 py-1 text-xs text-primary-foreground transition-opacity hover:opacity-90"
                    @click="iniciarDibujo"
                >
                    ✏ {{ modo === 'listo' ? 'Redibujar' : 'Dibujar terreno' }}
                </button>

                <template v-if="modo === 'dibujando'">
                    <button
                        type="button"
                        class="rounded-md border border-green-600 bg-green-600 px-3 py-1 text-xs text-white disabled:opacity-40"
                        :disabled="vertices.length < 3"
                        @click="finalizarDibujo"
                    >
                        ✓ Finalizar ({{ vertices.length }} pts)
                    </button>
                    <button
                        type="button"
                        class="rounded-md border border-input bg-background px-3 py-1 text-xs hover:bg-muted"
                        @click="borrar"
                    >
                        Cancelar
                    </button>
                </template>

                <button
                    v-if="modo === 'listo'"
                    type="button"
                    class="rounded-md border border-destructive px-3 py-1 text-xs text-destructive hover:bg-destructive/10"
                    @click="borrar"
                >
                    ✕ Borrar
                </button>
            </div>
        </div>

        <!-- Instrucción -->
        <p
            v-if="modo === 'dibujando'"
            class="text-xs text-amber-600 dark:text-amber-400"
        >
            Haz clic en el mapa para añadir vértices. Doble clic o "Finalizar"
            para cerrar el polígono.
        </p>
        <p
            v-else-if="modo === 'listo'"
            class="text-xs text-green-600 dark:text-green-400"
        >
            Polígono listo · Arrastra los puntos blancos para ajustar.
        </p>
        <p v-else class="text-xs text-muted-foreground">
            Pulsa "Dibujar terreno" para trazar el contorno.
        </p>

        <!-- Mapa -->
        <div
            ref="mapEl"
            class="h-[480px] w-full overflow-hidden rounded-md border border-input"
        />

        <p v-if="modelValue.length > 0" class="text-xs text-muted-foreground">
            {{ modelValue.length }} vértices definidos.
        </p>
    </div>
</template>
