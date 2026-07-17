<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import { MapPin } from '@lucide/vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { Terreno, Coordenada } from '@/types/models/terreno';

interface UbicacionCollar {
    id: number;
    collar_id: number;
    latitud: number;
    longitud: number;
    recibido_en: string;
}

interface AnimalConUbicacion {
    id: number;
    nombre: string;
    codigo: string;
    collar?: {
        id: number;
        animal_id: number;
        serie: string;
        modelo: string;
        estado: string;
        ubicaciones?: UbicacionCollar[];
        ultima_ubicacion?: UbicacionCollar | null;
    } | null;
    [key: string]: any;
}

function getUbicacion(animal: AnimalConUbicacion): UbicacionCollar | undefined {
    return animal.collar?.ultima_ubicacion ?? animal.collar?.ubicaciones?.[0];
}

const props = withDefaults(defineProps<{
    terreno: Terreno;
    animales: AnimalConUbicacion[];
    capaActiva: 'satelite' | 'hibrido' | 'calles';
    hideHeader?: boolean;
    mapHeightClass?: string;
}>(), {
    hideHeader: false,
    mapHeightClass: 'h-[280px]',
});

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

const mapEl = ref<HTMLDivElement | null>(null);
let map: maplibregl.Map | null = null;
let markers: maplibregl.Marker[] = [];

function puntoEnPoligono(lat: number, lng: number, coords: Coordenada[]): boolean {
    if (coords.length < 3) return false;
    let inside = false;
    for (let i = 0, j = coords.length - 1; i < coords.length; j = i++) {
        const yi = Number(coords[i].lat), xi = Number(coords[i].lng);
        const yj = Number(coords[j].lat), xj = Number(coords[j].lng);
        if ((yi > lat) !== (yj > lat) && lng < ((xj - xi) * (lat - yi)) / (yj - yi) + xi) {
            inside = !inside;
        }
    }
    return inside;
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

// Animales cuya última ubicación está dentro de este terreno
function animalesEnTerreno(): AnimalConUbicacion[] {
    return props.animales.filter((a) => {
        const ub = getUbicacion(a);
        if (!ub) return false;
        return puntoEnPoligono(Number(ub.latitud), Number(ub.longitud), props.terreno.coordenadas || []);
    });
}

// Animales con collar y ubicación que están FUERA de todos los terrenos (se muestran si están cerca)
function animalesCercanos(): AnimalConUbicacion[] {
    const coords = props.terreno.coordenadas || [];
    if (coords.length < 3) return [];

    // Calcular bounding box del terreno con un margen
    let minLat = Infinity, maxLat = -Infinity, minLng = Infinity, maxLng = -Infinity;
    for (const c of coords) {
        if (c.lat < minLat) minLat = c.lat;
        if (c.lat > maxLat) maxLat = c.lat;
        if (c.lng < minLng) minLng = c.lng;
        if (c.lng > maxLng) maxLng = c.lng;
    }
    // Margen de ~500m aprox
    const margen = 0.005;
    minLat -= margen; maxLat += margen;
    minLng -= margen; maxLng += margen;

    return props.animales.filter((a) => {
        const ub = getUbicacion(a);
        if (!ub) return false;
        const lat = Number(ub.latitud);
        const lng = Number(ub.longitud);
        // Está en el bounding box ampliado pero NO dentro del polígono
        if (lat >= minLat && lat <= maxLat && lng >= minLng && lng <= maxLng) {
            return !puntoEnPoligono(lat, lng, coords);
        }
        return false;
    });
}

function inicializarMapa() {
    if (!mapEl.value) return;
    const coords = props.terreno.coordenadas || [];

    let centerLng = -78.2595745;
    let centerLat = -7.275875;
    if (coords.length > 0) {
        let sumLat = 0, sumLng = 0;
        for (const c of coords) { sumLat += Number(c.lat); sumLng += Number(c.lng); }
        centerLat = sumLat / coords.length;
        centerLng = sumLng / coords.length;
    }

    // Guard against NaN coordinates
    if (isNaN(centerLat) || isNaN(centerLng)) {
        centerLat = -7.275875;
        centerLng = -78.2595745;
    }

    map = new maplibregl.Map({
        container: mapEl.value,
        style: {
            version: 8,
            glyphs: 'https://fonts.openmaptiles.org/{fontstack}/{range}.pbf',
            sources: {
                'base-tiles': {
                    type: 'raster',
                    tiles: CAPAS[props.capaActiva] as unknown as string[],
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
        attributionControl: false,
    });

    map.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'top-left');

    map.on('load', () => {
        dibujarTerreno();
        dibujarMarcadores();
        ajustarVista();
    });
}

function dibujarTerreno() {
    if (!map) return;
    const coords = props.terreno.coordenadas || [];
    if (coords.length < 3) return;

    const ring = coords.map((c) => [Number(c.lng), Number(c.lat)] as [number, number]);
    ring.push(ring[0]);

    const sourceId = `terreno-${props.terreno.id}`;

    map.addSource(sourceId, {
        type: 'geojson',
        data: {
            type: 'Feature',
            geometry: { type: 'Polygon', coordinates: [ring] },
            properties: { nombre: props.terreno.nombre },
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
            'text-field': props.terreno.nombre,
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

function dibujarMarcadores() {
    for (const m of markers) m.remove();
    markers = [];
    if (!map) return;

    const dentro = animalesEnTerreno();
    const cercanos = animalesCercanos();
    const todos = [...dentro, ...cercanos];

    for (const animal of todos) {
        const ub = getUbicacion(animal);
        if (!ub) continue;

        const estaDentro = dentro.includes(animal);

        const color = estaDentro ? '#22c55e' : '#ef4444';
        const el = document.createElement('div');
        el.className = 'animal-marker-container';
        el.innerHTML = `
            <div class="animal-pulse-ring" style="border-color: ${color};"></div>
            <div class="animal-pulse-ring animal-pulse-ring-2" style="border-color: ${color};"></div>
            <div class="animal-marker-dot" style="background: ${color};">
                ${estaDentro ? '🐄' : '⚠️'}
            </div>
        `;

        const popup = new maplibregl.Popup({ offset: 16, closeButton: false }).setHTML(`
            <div style="font-family: system-ui; font-size: 12px; max-width: 180px;">
                <div style="font-weight: 600;">${animal.nombre}</div>
                <div style="color: #666; font-size: 11px;">Código: ${animal.codigo}</div>
                <div style="color: #666; font-size: 11px;">Collar: ${animal.collar?.serie || '-'}</div>
                <div style="margin-top: 4px;">
                    <span style="
                        display: inline-block; padding: 1px 6px; border-radius: 9999px;
                        font-weight: 500; color: white; font-size: 10px;
                        background: ${estaDentro ? '#22c55e' : '#ef4444'};
                    ">${estaDentro ? 'Dentro' : 'FUERA'}</span>
                </div>
                <div style="color: #999; font-size: 10px; margin-top: 3px;">
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

    for (const c of props.terreno.coordenadas || []) {
        bounds.extend([Number(c.lng), Number(c.lat)]);
        hasPoints = true;
    }

    // También incluir los marcadores de animales cercanos
    const dentro = animalesEnTerreno();
    const cercanos = animalesCercanos();
    for (const a of [...dentro, ...cercanos]) {
        const ub = getUbicacion(a);
        if (ub) {
            bounds.extend([Number(ub.longitud), Number(ub.latitud)]);
        }
    }

    if (hasPoints) {
        map.fitBounds(bounds, { padding: 30, maxZoom: 18, duration: 300 });
    }
}

// Contadores para mostrar en el header
function contarDentro(): number {
    return animalesEnTerreno().length;
}

function contarFuera(): number {
    return animalesCercanos().length;
}

// Reaccionar a cambios en animales (polling)
watch(() => props.animales, () => {
    dibujarMarcadores();
}, { deep: true });

// Reaccionar a cambio de capa
watch(() => props.capaActiva, (id) => {
    (map?.getSource('base-tiles') as maplibregl.RasterTileSource | undefined)
        ?.setTiles(CAPAS[id] as unknown as string[]);
});

onMounted(() => inicializarMapa());

onUnmounted(() => {
    map?.remove();
    map = null;
});

defineExpose({ refrescarMarcadores: dibujarMarcadores });
</script>

<template>
    <Card class="overflow-hidden border-sidebar-border/70 dark:border-sidebar-border h-full flex flex-col">
        <CardHeader v-if="!props.hideHeader" class="flex-row items-center justify-between py-2.5 px-4">
            <div class="flex items-center gap-2">
                <MapPin class="h-4 w-4 text-green-600" />
                <CardTitle class="text-sm">{{ terreno.nombre }}</CardTitle>
            </div>
            <div class="flex items-center gap-1.5">
                <Badge
                    v-if="contarDentro() > 0"
                    variant="outline"
                    class="border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-950/30 dark:text-green-400 text-[10px] px-1.5 py-0"
                >
                    {{ contarDentro() }} dentro
                </Badge>
                <Badge
                    v-if="contarFuera() > 0"
                    variant="outline"
                    class="border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400 text-[10px] px-1.5 py-0"
                >
                    {{ contarFuera() }} fuera
                </Badge>
                <Badge
                    v-if="contarDentro() === 0 && contarFuera() === 0"
                    variant="outline"
                    class="text-[10px] px-1.5 py-0"
                >
                    Sin animales
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="p-0 flex-1">
            <div ref="mapEl" class="w-full" :class="props.mapHeightClass" />
        </CardContent>
    </Card>
</template>

<style>
/* Ocultar atribución para los mini mapas */
.maplibregl-ctrl-attrib {
    display: none;
}

/* ── Marcador animal pulsante ──────────────────────────────────────────── */
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
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    animation: marker-breathe 2s ease-in-out infinite;
}

.animal-pulse-ring {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    border: 2px solid;
    opacity: 0;
    animation: pulse-expand 2s ease-out infinite;
}

.animal-pulse-ring-2 {
    animation-delay: 1s;
}

@keyframes pulse-expand {
    0% {
        width: 28px;
        height: 28px;
        opacity: 0.6;
    }
    100% {
        width: 56px;
        height: 56px;
        opacity: 0;
    }
}

@keyframes marker-breathe {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.08);
    }
}
</style>
