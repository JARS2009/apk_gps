<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { ArrowLeft, Navigation, Clock, Gauge, RefreshCw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Collar } from '@/types/models/collar';
import type { Terreno, Coordenada } from '@/types/models/terreno';

interface UbicacionRuta {
    id: number;
    latitud: number;
    longitud: number;
    velocidad: number | null;
    rumbo: number | null;
    evento: string;
    fecha_gps: string | null;
    created_at: string;
}

const props = defineProps<{
    collar: Collar;
    collares: Collar[];
    terrenos: Terreno[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Collares', href: '/collares' },
            { title: 'Ruta' },
        ],
    },
});

function cambiarCollar(event: Event) {
    const select = event.target as HTMLSelectElement;
    if (select.value) {
        router.visit(`/collares/${select.value}/ruta`);
    }
}

// ── Estado ───────────────────────────────────────────────────────────────
const ubicaciones = ref<UbicacionRuta[]>([]);
const cargando = ref(false);
const autoRefresh = ref(true);
let refreshInterval: ReturnType<typeof setInterval> | null = null;
let primeraVez = true;

// Filtros de fecha (hoy por defecto)
const hoy = new Date().toISOString().slice(0, 10);
const fechaDesde = ref(hoy);
const fechaHasta = ref('');

// ── Capas ────────────────────────────────────────────────────────────────
type CapaId = 'satelite' | 'hibrido' | 'calles';
const CAPAS_LABELS: Record<CapaId, string> = {
    satelite: 'Satélite',
    hibrido: 'Híbrido',
    calles: 'Calles',
};
const CAPAS = {
    satelite: [
        'https://mt0.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
    ],
    hibrido: [
        'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
    ],
    calles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
} as const;
const capaActiva = ref<CapaId>('satelite');

// ── Mapa ─────────────────────────────────────────────────────────────────
const mapEl = ref<HTMLDivElement | null>(null);
let map: maplibregl.Map | null = null;
let markerInicio: maplibregl.Marker | null = null;
let markerAnimal: maplibregl.Marker | null = null;

// ── Stats ────────────────────────────────────────────────────────────────
const stats = computed(() => {
    const pts = ubicaciones.value;
    if (pts.length === 0) return { puntos: 0, velMax: 0, velProm: 0, primera: '', ultima: '' };

    const vels = pts.filter(p => p.velocidad != null && Number(p.velocidad) > 0).map(p => Number(p.velocidad));
    const velMax = vels.length > 0 ? Math.max(...vels) : 0;
    const velProm = vels.length > 0 ? vels.reduce((a, b) => a + b, 0) / vels.length : 0;

    return {
        puntos: pts.length,
        velMax: velMax.toFixed(1),
        velProm: velProm.toFixed(1),
        primera: formatFecha(pts[0].fecha_gps || pts[0].created_at),
        ultima: formatFecha(pts[pts.length - 1].fecha_gps || pts[pts.length - 1].created_at),
    };
});

function formatFecha(fecha: string): string {
    return new Date(fecha).toLocaleString('es-CO', {
        day: '2-digit', month: '2-digit',
        hour: '2-digit', minute: '2-digit',
        second: '2-digit',
    });
}

// ── API ──────────────────────────────────────────────────────────────────
async function cargarUbicaciones(forzarRedibujado = false) {
    if (!props.collar.imei) return;

    cargando.value = true;
    try {
        const params = new URLSearchParams();
        if (fechaDesde.value) params.set('desde', `${fechaDesde.value} 00:00:00`);
        if (fechaHasta.value) params.set('hasta', `${fechaHasta.value} 23:59:59`);

        const res = await fetch(`/api/collares/${props.collar.id}/ubicaciones?${params}`, {
            headers: {
                Accept: 'application/json',
            },
        });
        const data = await res.json();
        const nuevas: UbicacionRuta[] = data.ubicaciones;

        // Si es la primera carga o cambió el filtro, redibujar todo
        if (primeraVez || forzarRedibujado || ubicaciones.value.length === 0) {
            ubicaciones.value = nuevas;
            dibujarRutaCompleta();
            primeraVez = false;
        } else {
            // Actualización incremental: solo agregar puntos nuevos
            const ultimoIdActual = ubicaciones.value[ubicaciones.value.length - 1]?.id ?? 0;
            const puntosNuevos = nuevas.filter(p => p.id > ultimoIdActual);

            if (puntosNuevos.length > 0) {
                ubicaciones.value.push(...puntosNuevos);
                agregarPuntosARuta(puntosNuevos);
            }
        }
    } catch {
        // silenciar
    } finally {
        cargando.value = false;
    }
}

// ── Mapa: inicialización ─────────────────────────────────────────────────
function inicializarMapa() {
    if (!mapEl.value) return;

    let centerLat = -7.152765;
    let centerLng = -78.507695;

    const coords = props.terrenos[0]?.coordenadas;
    if (coords && coords.length > 0) {
        let sumLat = 0, sumLng = 0;
        for (const c of coords) { sumLat += Number(c.lat); sumLng += Number(c.lng); }
        centerLat = sumLat / coords.length;
        centerLng = sumLng / coords.length;
    }

    map = new maplibregl.Map({
        container: mapEl.value,
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'base-tiles': {
                    type: 'raster',
                    tiles: CAPAS[capaActiva.value] as unknown as string[],
                    tileSize: 256,
                    maxzoom: 21,
                    attribution: '© Google',
                },
            },
            layers: [{
                id: 'base-layer',
                type: 'raster',
                source: 'base-tiles',
                minzoom: 0,
                maxzoom: 24,
            }],
        },
        center: [centerLng, centerLat],
        zoom: 15,
        maxZoom: 22,
        attributionControl: false,
    });

    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-left');

    map.on('load', () => {
        dibujarTerrenos();
        cargarUbicaciones();
    });
}

function dibujarTerrenos() {
    if (!map) return;

    for (const terreno of props.terrenos) {
        const coords = terreno.coordenadas || [];
        if (coords.length < 3) continue;

        const ring = coords.map(c => [Number(c.lng), Number(c.lat)] as [number, number]);
        ring.push(ring[0]);

        const sourceId = `terreno-${terreno.id}`;

        map.addSource(sourceId, {
            type: 'geojson',
            data: {
                type: 'Feature',
                geometry: { type: 'Polygon', coordinates: [ring] },
                properties: { nombre: terreno.nombre },
            },
        });

        map.addLayer({
            id: `${sourceId}-fill`,
            type: 'fill',
            source: sourceId,
            paint: { 'fill-color': '#22c55e', 'fill-opacity': 0.15 },
        });

        map.addLayer({
            id: `${sourceId}-border`,
            type: 'line',
            source: sourceId,
            paint: { 'line-color': '#16a34a', 'line-width': 2 },
        });

        map.addLayer({
            id: `${sourceId}-label`,
            type: 'symbol',
            source: sourceId,
            layout: {
                'text-field': terreno.nombre,
                'text-size': 12,
                'text-font': ['Open Sans Regular'],
                'text-anchor': 'center',
            },
            paint: {
                'text-color': '#ffffff',
                'text-halo-color': '#000000',
                'text-halo-width': 1.5,
            },
        });
    }
}

// ── Crear marcador pulsante del animal ────────────────────────────────────
function crearMarcadorAnimal(lng: number, lat: number, punto: UbicacionRuta): maplibregl.Marker {
    const el = document.createElement('div');
    el.className = 'animal-marker-container';
    el.innerHTML = `
        <div class="animal-pulse-ring"></div>
        <div class="animal-pulse-ring animal-pulse-ring-2"></div>
        <div class="animal-marker-dot">🐄</div>
    `;

    const popup = new maplibregl.Popup({ offset: 20, closeButton: false }).setHTML(`
        <div style="font-family: system-ui; font-size: 11px; max-width: 180px;">
            <div style="font-weight: 600;">${props.collar.animal?.nombre || 'Animal'}</div>
            <div>Collar: ${props.collar.serie}</div>
            <div>Vel: ${punto.velocidad ?? 0} km/h</div>
            <div>${formatFecha(punto.fecha_gps || punto.created_at)}</div>
        </div>
    `);

    return new maplibregl.Marker({ element: el })
        .setLngLat([lng, lat])
        .setPopup(popup);
}

function actualizarPopupAnimal(punto: UbicacionRuta) {
    if (!markerAnimal) return;
    const popup = markerAnimal.getPopup();
    if (popup) {
        popup.setHTML(`
            <div style="font-family: system-ui; font-size: 11px; max-width: 180px;">
                <div style="font-weight: 600;">${props.collar.animal?.nombre || 'Animal'}</div>
                <div>Collar: ${props.collar.serie}</div>
                <div>Vel: ${punto.velocidad ?? 0} km/h</div>
                <div>${formatFecha(punto.fecha_gps || punto.created_at)}</div>
            </div>
        `);
    }
}

// ── Dibujar ruta completa (primera carga o cambio de filtro) ─────────────
function dibujarRutaCompleta() {
    if (!map) return;

    // Limpiar todo
    if (map.getLayer('ruta-line')) map.removeLayer('ruta-line');
    if (map.getSource('ruta')) map.removeSource('ruta');
    markerInicio?.remove();
    markerAnimal?.remove();
    markerInicio = null;
    markerAnimal = null;

    const pts = ubicaciones.value;
    if (pts.length === 0) return;

    const coordinates = pts.map(p => [Number(p.longitud), Number(p.latitud)] as [number, number]);

    // Polyline
    map.addSource('ruta', {
        type: 'geojson',
        data: {
            type: 'Feature',
            geometry: { type: 'LineString', coordinates },
            properties: {},
        },
    });

    map.addLayer({
        id: 'ruta-line',
        type: 'line',
        source: 'ruta',
        paint: {
            'line-color': '#3b82f6',
            'line-width': 3,
            'line-opacity': 0.8,
        },
        layout: {
            'line-cap': 'round',
            'line-join': 'round',
        },
    });

    // Marcador inicio (verde)
    const inicio = pts[0];
    const elInicio = document.createElement('div');
    elInicio.innerHTML = `
        <div style="
            width: 14px; height: 14px; border-radius: 50%;
            background: #22c55e; border: 2px solid white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.3);
        "></div>
    `;
    markerInicio = new maplibregl.Marker({ element: elInicio })
        .setLngLat([Number(inicio.longitud), Number(inicio.latitud)])
        .setPopup(new maplibregl.Popup({ offset: 10, closeButton: false }).setHTML(`
            <div style="font-size: 11px;">
                <div style="font-weight: 600;">Inicio</div>
                <div>${formatFecha(inicio.fecha_gps || inicio.created_at)}</div>
            </div>
        `))
        .addTo(map);

    // Marcador animal pulsante (posición actual)
    const fin = pts[pts.length - 1];
    markerAnimal = crearMarcadorAnimal(Number(fin.longitud), Number(fin.latitud), fin);
    markerAnimal.addTo(map);

    // Ajustar vista
    const bounds = new maplibregl.LngLatBounds();
    for (const c of coordinates) bounds.extend(c);
    for (const t of props.terrenos) {
        for (const c of t.coordenadas || []) {
            bounds.extend([Number(c.lng), Number(c.lat)]);
        }
    }
    map.fitBounds(bounds, { padding: 40, maxZoom: 18, duration: 300 });
}

// ── Agregar puntos nuevos incrementalmente ───────────────────────────────
function agregarPuntosARuta(nuevos: UbicacionRuta[]) {
    if (!map) return;

    // Actualizar la polyline agregando los nuevos puntos
    const source = map.getSource('ruta') as maplibregl.GeoJSONSource | undefined;
    if (source) {
        const allPts = ubicaciones.value;
        const coordinates = allPts.map(p => [Number(p.longitud), Number(p.latitud)] as [number, number]);
        source.setData({
            type: 'Feature',
            geometry: { type: 'LineString', coordinates },
            properties: {},
        });
    }

    // Mover el marcador del animal a la última posición con animación suave
    const ultimo = nuevos[nuevos.length - 1];
    const nuevaPos: [number, number] = [Number(ultimo.longitud), Number(ultimo.latitud)];

    if (markerAnimal) {
        // Animación suave del marcador
        const posActual = markerAnimal.getLngLat();
        const inicio = { lng: posActual.lng, lat: posActual.lat };
        const fin = { lng: nuevaPos[0], lat: nuevaPos[1] };
        const duracion = 800; // ms
        const startTime = performance.now();

        function animar(time: number) {
            const t = Math.min((time - startTime) / duracion, 1);
            // Ease out cubic
            const ease = 1 - Math.pow(1 - t, 3);
            const lng = inicio.lng + (fin.lng - inicio.lng) * ease;
            const lat = inicio.lat + (fin.lat - inicio.lat) * ease;
            markerAnimal?.setLngLat([lng, lat]);
            if (t < 1) requestAnimationFrame(animar);
        }
        requestAnimationFrame(animar);

        actualizarPopupAnimal(ultimo);
    }
}

// ── Watchers ─────────────────────────────────────────────────────────────
watch(capaActiva, (id) => {
    (map?.getSource('base-tiles') as maplibregl.RasterTileSource | undefined)
        ?.setTiles(CAPAS[id] as unknown as string[]);
});

// Cuando cambian los filtros de fecha, redibujar todo
watch([fechaDesde, fechaHasta], () => {
    primeraVez = true;
    cargarUbicaciones(true);
});

// ── Lifecycle ────────────────────────────────────────────────────────────
onMounted(() => {
    inicializarMapa();
    refreshInterval = setInterval(() => {
        if (autoRefresh.value) cargarUbicaciones();
    }, 10000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
    map?.remove();
    map = null;
});
</script>

<template>
    <Head :title="`Ruta — ${collar.serie}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <Link href="/collares">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Collares
                    </Button>
                </Link>

                <!-- Selector de collar -->
                <div class="flex items-center gap-2 border-l pl-3">
                    <select
                        :value="collar.id"
                        @change="cambiarCollar"
                        class="h-8 rounded-md border border-input bg-background px-2.5 py-1 text-xs font-medium shadow-sm transition-all hover:bg-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                    >
                        <option
                            v-for="col in collares"
                            :key="col.id"
                            :value="col.id"
                        >
                            {{ col.animal?.nombre || 'Collar' }} ({{ col.serie }})
                        </option>
                    </select>
                </div>

                <Badge v-if="autoRefresh" variant="outline" class="gap-1 text-[10px] text-green-600">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                    </span>
                    En vivo
                </Badge>
            </div>

            <div class="flex items-center gap-2">
                <div class="flex gap-1 rounded-lg bg-muted p-1">
                    <button
                        v-for="(label, id) in CAPAS_LABELS"
                        :key="id"
                        type="button"
                        class="rounded-md px-2.5 py-1 text-xs transition-colors"
                        :class="capaActiva === id ? 'bg-primary text-primary-foreground' : 'hover:bg-background'"
                        @click="capaActiva = id as CapaId"
                    >
                        {{ label }}
                    </button>
                </div>
                <Button variant="outline" size="sm" :disabled="cargando" @click="cargarUbicaciones(true)">
                    <RefreshCw class="mr-1 h-3.5 w-3.5" :class="{ 'animate-spin': cargando }" />
                    Actualizar
                </Button>
            </div>
        </div>

        <!-- Filtros + Stats -->
        <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div class="flex items-end gap-3">
                <div class="grid gap-1">
                    <Label class="text-xs">Desde</Label>
                    <Input
                        type="date"
                        v-model="fechaDesde"
                        class="h-8 w-40 text-xs"
                    />
                </div>
                <div class="grid gap-1">
                    <Label class="text-xs">Hasta</Label>
                    <Input
                        type="date"
                        v-model="fechaHasta"
                        class="h-8 w-40 text-xs"
                    />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Badge variant="outline" class="gap-1 text-xs">
                    <Navigation class="h-3 w-3" />
                    {{ stats.puntos }} puntos
                </Badge>
                <Badge v-if="stats.puntos > 0" variant="outline" class="gap-1 text-xs">
                    <Gauge class="h-3 w-3" />
                    Máx {{ stats.velMax }} km/h · Prom {{ stats.velProm }} km/h
                </Badge>
                <Badge v-if="stats.puntos > 0" variant="outline" class="gap-1 text-xs">
                    <Clock class="h-3 w-3" />
                    {{ stats.primera }} → {{ stats.ultima }}
                </Badge>
            </div>
        </div>

        <!-- Mapa -->
        <div class="relative flex-1">
            <div ref="mapEl" class="h-full min-h-[400px] w-full rounded-xl border" />

            <div
                v-if="!collar.imei"
                class="absolute inset-0 flex items-center justify-center rounded-xl bg-background/80"
            >
                <div class="text-center text-muted-foreground">
                    <Navigation class="mx-auto mb-2 h-8 w-8 opacity-50" />
                    <p class="text-sm font-medium">Sin IMEI configurado</p>
                    <p class="text-xs">Asigna un IMEI al collar para ver su ruta.</p>
                </div>
            </div>

            <div
                v-else-if="ubicaciones.length === 0 && !cargando"
                class="absolute inset-0 flex items-center justify-center rounded-xl bg-background/80"
            >
                <div class="text-center text-muted-foreground">
                    <Navigation class="mx-auto mb-2 h-8 w-8 opacity-50" />
                    <p class="text-sm font-medium">Sin datos de ruta</p>
                    <p class="text-xs">No hay ubicaciones registradas para el rango seleccionado.</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
.maplibregl-popup-content {
    border-radius: 8px !important;
    padding: 10px 14px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}

/* ── Marcador del animal con pulso de vida ─────────────────────────────── */
.animal-marker-container {
    position: relative;
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
}

.animal-marker-dot {
    position: relative;
    z-index: 2;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #ef4444;
    border: 2.5px solid white;
    box-shadow: 0 0 14px #ef4444;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.4));
    animation: marker-breathe 1.2s ease-in-out infinite;
}

.animal-pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2.5px solid #ef4444;
    opacity: 0;
    animation: pulse-expand 1.2s cubic-bezier(0.24, 0, 0.38, 1) infinite;
}

.animal-pulse-ring-2 {
    animation-delay: 0.6s;
}

@keyframes pulse-expand {
    0% {
        width: 32px;
        height: 32px;
        opacity: 0.8;
    }
    100% {
        width: 64px;
        height: 64px;
        opacity: 0;
    }
}

@keyframes marker-breathe {
    0%, 100% {
        transform: scale(1);
        filter: drop-shadow(0 0 4px rgba(239, 68, 68, 0.4));
    }
    50% {
        transform: scale(1.15);
        filter: drop-shadow(0 0 14px #ef4444);
    }
}
</style>
