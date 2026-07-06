<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import {
    AlertTriangle,
    Bell,
    BellOff,
    Check,
    MapPin,
    Radio,
    Play,
    Square,
    RefreshCw,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import type { Terreno, Coordenada } from '@/types/models/terreno';
import type { Animal } from '@/types/models/animal';
import type { Alerta } from '@/types/models/alerta';
import { useGpsTracking } from '@/composables/useGpsTracking';

interface UbicacionCollar {
    id: number;
    collar_id: number;
    latitud: number;
    longitud: number;
    recibido_en: string;
}

interface AnimalConUbicacion extends Animal {
    collar?: {
        id: number;
        animal_id: number;
        serie: string;
        modelo: string;
        estado: string;
        ubicaciones?: UbicacionCollar[];
    } | null;
}

const props = defineProps<{
    terrenos: Terreno[];
    animales: AnimalConUbicacion[];
    alertas: Alerta[];
    alertasNoLeidas: number;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// ── Estado reactivo ───────────────────────────────────────────────────────
const mapEl = ref<HTMLDivElement | null>(null);
const alertasLocales = ref<Alerta[]>([...props.alertas]);
const animalesLocales = ref<AnimalConUbicacion[]>([...props.animales]);
const alertasNoLeidasCount = ref(props.alertasNoLeidas);
const mostrarPanel = ref<'alertas' | 'tracking'>('alertas');
const collarSeleccionado = ref<number | null>(null);
const collaresDisponibles = ref<
    { id: number; serie: string; animal?: { nombre: string; codigo: string } | null }[]
>([]);

let map: maplibregl.Map | null = null;
let markers: maplibregl.Marker[] = [];
let refreshInterval: ReturnType<typeof setInterval> | null = null;

function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

async function apiFetch<T = any>(url: string, options: RequestInit = {}): Promise<T> {
    const res = await fetch(url, {
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...options.headers,
        },
        ...options,
    });
    return res.json();
}

// GPS Tracking
const { state: trackingState, iniciar, detener, solicitarPermisoNotificaciones } = useGpsTracking(60000);

// ── Capas de mapa ─────────────────────────────────────────────────────────
const CAPAS = {
    satelite: {
        label: 'Satélite',
        tiles: [
            'https://mt0.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
            'https://mt1.google.com/vt/lyrs=s&x={x}&y={y}&z={z}',
        ],
    },
    hibrido: {
        label: 'Híbrido',
        tiles: [
            'https://mt0.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
            'https://mt1.google.com/vt/lyrs=y&x={x}&y={y}&z={z}',
        ],
    },
    calles: {
        label: 'Calles',
        tiles: ['https://tile.openstreetmap.org/{z}/{x}/{y}.png'],
    },
} as const;

type CapaId = keyof typeof CAPAS;
const capaActiva = ref<CapaId>('satelite');

function cambiarCapa(id: CapaId) {
    capaActiva.value = id;
    (
        map?.getSource('base-tiles') as maplibregl.RasterTileSource | undefined
    )?.setTiles(CAPAS[id].tiles as unknown as string[]);
}

// ── Helpers ───────────────────────────────────────────────────────────────
function puntoEnPoligono(lat: number, lng: number, coords: Coordenada[]): boolean {
    if (coords.length < 3) return false;
    let inside = false;
    for (let i = 0, j = coords.length - 1; i < coords.length; j = i++) {
        const yi = coords[i].lat, xi = coords[i].lng;
        const yj = coords[j].lat, xj = coords[j].lng;
        if ((yi > lat) !== (yj > lat) && lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi) {
            inside = !inside;
        }
    }
    return inside;
}

function animalDentroDeTerreno(animal: AnimalConUbicacion): boolean {
    const ub = animal.collar?.ubicaciones?.[0];
    if (!ub) return true; // sin ubicación = no alertar
    for (const t of props.terrenos) {
        if (puntoEnPoligono(Number(ub.latitud), Number(ub.longitud), t.coordenadas || [])) {
            return true;
        }
    }
    return false;
}

function tiempoRelativo(fecha: string): string {
    const diff = Date.now() - new Date(fecha).getTime();
    const mins = Math.floor(diff / 60000);
    if (mins < 1) return 'ahora';
    if (mins < 60) return `hace ${mins} min`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24) return `hace ${hrs}h`;
    return `hace ${Math.floor(hrs / 24)}d`;
}

// ── Estadísticas ──────────────────────────────────────────────────────────
const stats = computed(() => {
    const total = animalesLocales.value.length;
    let dentro = 0;
    let fuera = 0;
    let sinSenal = 0;

    for (const a of animalesLocales.value) {
        const ub = a.collar?.ubicaciones?.[0];
        if (!ub) {
            sinSenal++;
            continue;
        }
        if (animalDentroDeTerreno(a)) dentro++;
        else fuera++;
    }

    return { total, dentro, fuera, sinSenal };
});

// ── Mapa ──────────────────────────────────────────────────────────────────
function inicializarMapa() {
    if (!mapEl.value) return;

    // Calcular centro basado en terrenos
    let centerLng = -78.2595745;
    let centerLat = -7.275875;
    if (props.terrenos.length > 0) {
        let sumLat = 0, sumLng = 0, count = 0;
        for (const t of props.terrenos) {
            for (const c of t.coordenadas || []) {
                sumLat += c.lat;
                sumLng += c.lng;
                count++;
            }
        }
        if (count > 0) {
            centerLat = sumLat / count;
            centerLng = sumLng / count;
        }
    }

    map = new maplibregl.Map({
        container: mapEl.value,
        style: {
            version: 8,
            glyphs: 'https://demotiles.maplibre.org/font/{fontstack}/{range}.pbf',
            sources: {
                'base-tiles': {
                    type: 'raster',
                    tiles: CAPAS.satelite.tiles as unknown as string[],
                    tileSize: 256,
                    maxzoom: 21,
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
        center: [centerLng, centerLat],
        zoom: 15,
        maxZoom: 22,
    });

    map.addControl(new maplibregl.NavigationControl(), 'top-left');

    map.on('load', () => {
        dibujarTerrenos();
        dibujarMarcadores();
        ajustarVista();
    });
}

function dibujarTerrenos() {
    if (!map) return;

    for (const terreno of props.terrenos) {
        const coords = terreno.coordenadas || [];
        if (coords.length < 3) continue;

        const ring = coords.map((c) => [c.lng, c.lat] as [number, number]);
        ring.push(ring[0]); // cerrar polígono

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
            paint: {
                'fill-color': '#22c55e',
                'fill-opacity': 0.15,
            },
        });

        map.addLayer({
            id: `${sourceId}-border`,
            type: 'line',
            source: sourceId,
            paint: {
                'line-color': '#16a34a',
                'line-width': 2,
            },
        });

        // Label del terreno
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

function dibujarMarcadores() {
    // Limpiar marcadores anteriores
    for (const m of markers) m.remove();
    markers = [];

    if (!map) return;

    for (const animal of animalesLocales.value) {
        const ub = animal.collar?.ubicaciones?.[0];
        if (!ub) continue;

        const dentro = animalDentroDeTerreno(animal);

        // Crear elemento del marcador
        const el = document.createElement('div');
        el.className = 'animal-marker';
        el.innerHTML = `
            <div style="
                display: flex;
                align-items: center;
                justify-content: center;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: ${dentro ? '#22c55e' : '#ef4444'};
                border: 3px solid white;
                box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                cursor: pointer;
                transition: transform 0.2s;
                font-size: 14px;
            " onmouseenter="this.style.transform='scale(1.3)'" onmouseleave="this.style.transform='scale(1)'">
                ${dentro ? '🐄' : '⚠️'}
            </div>
        `;

        const popup = new maplibregl.Popup({ offset: 20, closeButton: false }).setHTML(`
            <div style="font-family: system-ui; font-size: 13px; max-width: 200px;">
                <div style="font-weight: 600; margin-bottom: 4px;">${animal.nombre}</div>
                <div style="color: #666; font-size: 11px;">Código: ${animal.codigo}</div>
                <div style="color: #666; font-size: 11px;">Collar: ${animal.collar?.serie || '-'}</div>
                <div style="margin-top: 4px; font-size: 11px;">
                    <span style="
                        display: inline-block;
                        padding: 1px 6px;
                        border-radius: 9999px;
                        font-weight: 500;
                        color: white;
                        background: ${dentro ? '#22c55e' : '#ef4444'};
                    ">${dentro ? 'Dentro del terreno' : 'FUERA DEL TERRENO'}</span>
                </div>
                <div style="color: #999; font-size: 10px; margin-top: 4px;">
                    Última señal: ${tiempoRelativo(ub.recibido_en)}
                </div>
            </div>
        `);

        const marker = new maplibregl.Marker({ element: el })
            .setLngLat([Number(ub.longitud), Number(ub.latitud)])
            .setPopup(popup)
            .addTo(map);

        markers.push(marker);
    }
}

function ajustarVista() {
    if (!map) return;

    const bounds = new maplibregl.LngLatBounds();
    let hasPoints = false;

    for (const t of props.terrenos) {
        for (const c of t.coordenadas || []) {
            bounds.extend([c.lng, c.lat]);
            hasPoints = true;
        }
    }

    for (const a of animalesLocales.value) {
        const ub = a.collar?.ubicaciones?.[0];
        if (ub) {
            bounds.extend([Number(ub.longitud), Number(ub.latitud)]);
            hasPoints = true;
        }
    }

    if (hasPoints) {
        map.fitBounds(bounds, { padding: 50, maxZoom: 17, duration: 500 });
    }
}

// ── Polling de datos ──────────────────────────────────────────────────────
async function refrescarDatos() {
    try {
        const data = await apiFetch('/api/dashboard/datos');
        animalesLocales.value = data.animales;
        alertasLocales.value = data.alertas;
        alertasNoLeidasCount.value = data.alertasNoLeidas;
        dibujarMarcadores();
    } catch {
        // silenciar errores de red
    }
}

// ── Alertas ───────────────────────────────────────────────────────────────
async function marcarTodasLeidas() {
    try {
        await apiFetch('/api/dashboard/alertas/leidas', { method: 'POST' });
        alertasLocales.value = alertasLocales.value.map((a) => ({ ...a, leida: true }));
        alertasNoLeidasCount.value = 0;
    } catch {
        // silenciar
    }
}

// ── Tracking GPS ──────────────────────────────────────────────────────────
async function cargarCollares() {
    try {
        const data = await apiFetch('/api/tracking/collares');
        collaresDisponibles.value = data;
    } catch {
        // silenciar
    }
}

function iniciarTracking() {
    if (!collarSeleccionado.value) return;
    solicitarPermisoNotificaciones();
    iniciar(collarSeleccionado.value);
}

// ── Lifecycle ─────────────────────────────────────────────────────────────
onMounted(() => {
    inicializarMapa();
    cargarCollares();

    // Auto-refresh cada 60 segundos
    refreshInterval = setInterval(refrescarDatos, 60000);
});

onUnmounted(() => {
    map?.remove();
    map = null;
    if (refreshInterval) clearInterval(refreshInterval);
});

// Actualizar marcadores cuando cambian los animales
watch(
    () => props.animales,
    (newAnimales) => {
        animalesLocales.value = [...newAnimales];
        dibujarMarcadores();
    },
    { deep: true },
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Stats cards -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <Card
                class="border-sidebar-border/70 dark:border-sidebar-border"
            >
                <CardContent class="flex items-center gap-3 p-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-500/10"
                    >
                        <Radio class="h-5 w-5 text-blue-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground">
                            Con collar
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="border-sidebar-border/70 dark:border-sidebar-border"
            >
                <CardContent class="flex items-center gap-3 p-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-green-500/10"
                    >
                        <Check class="h-5 w-5 text-green-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">{{ stats.dentro }}</p>
                        <p class="text-xs text-muted-foreground">
                            En terreno
                        </p>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="border-sidebar-border/70 dark:border-sidebar-border"
            >
                <CardContent class="flex items-center gap-3 p-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-red-500/10"
                    >
                        <AlertTriangle class="h-5 w-5 text-red-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-red-500">
                            {{ stats.fuera }}
                        </p>
                        <p class="text-xs text-muted-foreground">Fuera</p>
                    </div>
                </CardContent>
            </Card>

            <Card
                class="border-sidebar-border/70 dark:border-sidebar-border"
            >
                <CardContent class="flex items-center gap-3 p-4">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-500/10"
                    >
                        <Bell class="h-5 w-5 text-amber-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold">
                            {{ alertasNoLeidasCount }}
                        </p>
                        <p class="text-xs text-muted-foreground">Alertas</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Mapa + Panel lateral -->
        <div class="flex flex-1 flex-col gap-4 md:flex-row">
            <!-- Mapa -->
            <div class="relative flex-1">
                <!-- Controles de capa -->
                <div
                    class="absolute top-2 right-2 z-10 flex gap-1 rounded-lg bg-background/80 p-1 shadow backdrop-blur-sm"
                >
                    <button
                        v-for="(cfg, id) in CAPAS"
                        :key="id"
                        type="button"
                        class="rounded-md px-2.5 py-1 text-xs transition-colors"
                        :class="
                            capaActiva === id
                                ? 'bg-primary text-primary-foreground'
                                : 'hover:bg-muted'
                        "
                        @click="cambiarCapa(id as CapaId)"
                    >
                        {{ cfg.label }}
                    </button>
                </div>

                <!-- Botón refresh -->
                <button
                    class="absolute top-2 left-12 z-10 flex h-8 w-8 items-center justify-center rounded-lg bg-background/80 shadow backdrop-blur-sm transition hover:bg-muted"
                    title="Actualizar datos"
                    @click="refrescarDatos"
                >
                    <RefreshCw class="h-4 w-4" />
                </button>

                <!-- Indicador de tracking activo -->
                <div
                    v-if="trackingState.activo"
                    class="absolute bottom-2 left-2 z-10 flex items-center gap-2 rounded-lg bg-green-600 px-3 py-1.5 text-xs text-white shadow"
                >
                    <span class="relative flex h-2 w-2">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75"
                        />
                        <span
                            class="relative inline-flex h-2 w-2 rounded-full bg-white"
                        />
                    </span>
                    GPS activo · {{ trackingState.ultimoEnvio || 'conectando...' }}
                </div>

                <div
                    ref="mapEl"
                    class="h-[400px] w-full overflow-hidden rounded-xl border border-sidebar-border/70 md:h-full md:min-h-[500px] dark:border-sidebar-border"
                />
            </div>

            <!-- Panel derecho: Alertas / Tracking -->
            <div class="flex w-full flex-col md:w-80">
                <!-- Tabs -->
                <div
                    class="mb-2 flex gap-1 rounded-lg bg-muted p-1"
                >
                    <button
                        class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            mostrarPanel === 'alertas'
                                ? 'bg-background shadow'
                                : 'hover:bg-background/50'
                        "
                        @click="mostrarPanel = 'alertas'"
                    >
                        <Bell class="mr-1 inline h-3.5 w-3.5" />
                        Alertas
                        <Badge
                            v-if="alertasNoLeidasCount > 0"
                            variant="destructive"
                            class="ml-1 h-4 px-1 text-[10px]"
                        >
                            {{ alertasNoLeidasCount }}
                        </Badge>
                    </button>
                    <button
                        class="flex-1 rounded-md px-3 py-1.5 text-xs font-medium transition-colors"
                        :class="
                            mostrarPanel === 'tracking'
                                ? 'bg-background shadow'
                                : 'hover:bg-background/50'
                        "
                        @click="mostrarPanel = 'tracking'"
                    >
                        <MapPin class="mr-1 inline h-3.5 w-3.5" />
                        GPS Collar
                    </button>
                </div>

                <!-- Panel de Alertas -->
                <Card
                    v-if="mostrarPanel === 'alertas'"
                    class="flex flex-1 flex-col overflow-hidden border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <CardHeader class="flex-row items-center justify-between py-3">
                        <CardTitle class="text-sm">Alertas recientes</CardTitle>
                        <Button
                            v-if="alertasNoLeidasCount > 0"
                            variant="ghost"
                            size="sm"
                            class="h-7 text-xs"
                            @click="marcarTodasLeidas"
                        >
                            <BellOff class="mr-1 h-3 w-3" />
                            Marcar leídas
                        </Button>
                    </CardHeader>
                    <CardContent
                        class="flex-1 space-y-2 overflow-y-auto p-3 pt-0"
                    >
                        <div
                            v-if="alertasLocales.length === 0"
                            class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground"
                        >
                            <Check class="mb-2 h-8 w-8 text-green-500" />
                            <p class="text-sm font-medium">
                                Sin alertas
                            </p>
                            <p class="text-xs">
                                Todos los animales están dentro de sus terrenos.
                            </p>
                        </div>

                        <div
                            v-for="alerta in alertasLocales"
                            :key="alerta.id"
                            class="rounded-lg border p-3 transition-colors"
                            :class="
                                alerta.leida
                                    ? 'border-border bg-muted/30'
                                    : 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30'
                            "
                        >
                            <div class="flex items-start gap-2">
                                <AlertTriangle
                                    class="mt-0.5 h-4 w-4 shrink-0"
                                    :class="
                                        alerta.leida
                                            ? 'text-muted-foreground'
                                            : 'text-red-500'
                                    "
                                />
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium">
                                        {{ alerta.animal?.nombre || 'Animal' }}
                                        <span class="text-muted-foreground">
                                            ({{ alerta.animal?.codigo || '-' }})
                                        </span>
                                    </p>
                                    <p class="mt-0.5 text-[11px] text-muted-foreground">
                                        {{ alerta.mensaje }}
                                    </p>
                                    <p class="mt-1 text-[10px] text-muted-foreground">
                                        {{ tiempoRelativo(alerta.created_at) }}
                                        · Collar: {{ alerta.collar?.serie || '-' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Panel de Tracking GPS -->
                <Card
                    v-if="mostrarPanel === 'tracking'"
                    class="flex flex-1 flex-col border-sidebar-border/70 dark:border-sidebar-border"
                >
                    <CardHeader class="py-3">
                        <CardTitle class="text-sm">
                            Emular Collar GPS
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 p-3 pt-0">
                        <p class="text-xs text-muted-foreground">
                            Usa el GPS de tu celular para simular un collar.
                            La ubicación se envía al servidor cada minuto.
                        </p>

                        <div v-if="!trackingState.activo">
                            <!-- Selector de collar -->
                            <label class="mb-1.5 block text-xs font-medium">
                                Seleccionar collar
                            </label>
                            <select
                                v-model="collarSeleccionado"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option :value="null" disabled>
                                    -- Elegir collar --
                                </option>
                                <option
                                    v-for="c in collaresDisponibles"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.serie }}
                                    {{
                                        c.animal
                                            ? `(${c.animal.nombre})`
                                            : ''
                                    }}
                                </option>
                            </select>

                            <Button
                                class="mt-3 w-full"
                                size="sm"
                                :disabled="!collarSeleccionado"
                                @click="iniciarTracking"
                            >
                                <Play class="mr-1 h-3.5 w-3.5" />
                                Iniciar tracking
                            </Button>
                        </div>

                        <div v-else class="space-y-3">
                            <div
                                class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950/30"
                            >
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"
                                        />
                                        <span
                                            class="relative inline-flex h-2.5 w-2.5 rounded-full bg-green-500"
                                        />
                                    </span>
                                    <span class="text-xs font-medium text-green-700 dark:text-green-400">
                                        Tracking activo
                                    </span>
                                </div>

                                <div class="mt-2 space-y-1 text-[11px] text-muted-foreground">
                                    <p>
                                        Lat: {{ trackingState.ultimaLat?.toFixed(6) || '...' }}
                                    </p>
                                    <p>
                                        Lng: {{ trackingState.ultimaLng?.toFixed(6) || '...' }}
                                    </p>
                                    <p>
                                        Último envío: {{ trackingState.ultimoEnvio || 'pendiente' }}
                                    </p>
                                    <p v-if="trackingState.dentroDeTerreno !== null">
                                        Estado:
                                        <span
                                            :class="
                                                trackingState.dentroDeTerreno
                                                    ? 'font-medium text-green-600'
                                                    : 'font-medium text-red-600'
                                            "
                                        >
                                            {{
                                                trackingState.dentroDeTerreno
                                                    ? 'Dentro del terreno'
                                                    : 'FUERA DEL TERRENO'
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <Button
                                variant="destructive"
                                size="sm"
                                class="w-full"
                                @click="detener"
                            >
                                <Square class="mr-1 h-3.5 w-3.5" />
                                Detener tracking
                            </Button>
                        </div>

                        <div
                            v-if="trackingState.error"
                            class="rounded-lg border border-red-200 bg-red-50 p-2 text-xs text-red-600 dark:border-red-900 dark:bg-red-950/30 dark:text-red-400"
                        >
                            {{ trackingState.error }}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>

<style>
/* Estilos para popups de MapLibre */
.maplibregl-popup-content {
    border-radius: 8px !important;
    padding: 10px 14px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}
</style>
