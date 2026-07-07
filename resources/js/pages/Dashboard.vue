<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
import TerrenoMapCard from '@/components/dashboard/TerrenoMapCard.vue';
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

type AnimalConUbicacion = Omit<Animal, 'collar'> & {
    collar?: {
        id: number;
        animal_id: number;
        serie: string;
        modelo: string;
        estado: string;
        ubicaciones?: UbicacionCollar[];
    } | null;
};

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
const alertasLocales = ref<Alerta[]>([...props.alertas]);
const animalesLocales = ref<AnimalConUbicacion[]>([...props.animales]);
const alertasNoLeidasCount = ref(props.alertasNoLeidas);
const mostrarPanel = ref<'alertas' | 'tracking'>('alertas');
const collarSeleccionado = ref<number | null>(null);
const collaresDisponibles = ref<
    { id: number; serie: string; animal?: { nombre: string; codigo: string } | null }[]
>([]);

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
type CapaId = 'satelite' | 'hibrido' | 'calles';
const CAPAS_LABELS: Record<CapaId, string> = {
    satelite: 'Satélite',
    hibrido: 'Híbrido',
    calles: 'Calles',
};
const capaActiva = ref<CapaId>('satelite');

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
    if (!ub) return true;
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

// ── Polling de datos ──────────────────────────────────────────────────────
async function refrescarDatos() {
    try {
        const data = await apiFetch('/api/dashboard/datos');
        animalesLocales.value = data.animales;
        alertasLocales.value = data.alertas;
        alertasNoLeidasCount.value = data.alertasNoLeidas;
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
    cargarCollares();
    refreshInterval = setInterval(refrescarDatos, 60000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

// Actualizar animales locales cuando cambian los props
watch(
    () => props.animales,
    (newAnimales) => {
        animalesLocales.value = [...newAnimales];
    },
    { deep: true },
);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <!-- Stats cards -->
        <div class="grid grid-cols-4 gap-1.5 md:gap-3">
            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="flex flex-col items-center justify-center p-1.5 md:flex-row md:gap-3 md:p-4">
                    <div class="flex items-center gap-1 md:h-10 md:w-10 md:justify-center md:rounded-full md:bg-blue-500/10">
                        <Radio class="h-3.5 w-3.5 text-blue-500 md:h-5 md:w-5" />
                        <span class="text-sm font-bold leading-none md:hidden">{{ stats.total }}</span>
                    </div>
                    <div class="mt-0.5 flex flex-col items-center md:mt-0 md:items-start">
                        <p class="hidden text-2xl font-bold leading-none md:block">{{ stats.total }}</p>
                        <p class="text-[9px] leading-tight text-muted-foreground md:text-xs">Collares</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="flex flex-col items-center justify-center p-1.5 md:flex-row md:gap-3 md:p-4">
                    <div class="flex items-center gap-1 md:h-10 md:w-10 md:justify-center md:rounded-full md:bg-green-500/10">
                        <Check class="h-3.5 w-3.5 text-green-500 md:h-5 md:w-5" />
                        <span class="text-sm font-bold leading-none md:hidden">{{ stats.dentro }}</span>
                    </div>
                    <div class="mt-0.5 flex flex-col items-center md:mt-0 md:items-start">
                        <p class="hidden text-2xl font-bold leading-none md:block">{{ stats.dentro }}</p>
                        <p class="text-[9px] leading-tight text-muted-foreground md:text-xs">Terreno</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="flex flex-col items-center justify-center p-1.5 md:flex-row md:gap-3 md:p-4">
                    <div class="flex items-center gap-1 md:h-10 md:w-10 md:justify-center md:rounded-full md:bg-red-500/10">
                        <AlertTriangle class="h-3.5 w-3.5 text-red-500 md:h-5 md:w-5" />
                        <span class="text-sm font-bold leading-none text-red-500 md:hidden">{{ stats.fuera }}</span>
                    </div>
                    <div class="mt-0.5 flex flex-col items-center md:mt-0 md:items-start">
                        <p class="hidden text-2xl font-bold leading-none text-red-500 md:block">{{ stats.fuera }}</p>
                        <p class="text-[9px] leading-tight text-muted-foreground md:text-xs">Fuera</p>
                    </div>
                </CardContent>
            </Card>

            <Card class="border-sidebar-border/70 dark:border-sidebar-border">
                <CardContent class="flex flex-col items-center justify-center p-1.5 md:flex-row md:gap-3 md:p-4">
                    <div class="flex items-center gap-1 md:h-10 md:w-10 md:justify-center md:rounded-full md:bg-amber-500/10">
                        <Bell class="h-3.5 w-3.5 text-amber-500 md:h-5 md:w-5" />
                        <span class="text-sm font-bold leading-none md:hidden">{{ alertasNoLeidasCount }}</span>
                    </div>
                    <div class="mt-0.5 flex flex-col items-center md:mt-0 md:items-start">
                        <p class="hidden text-2xl font-bold leading-none md:block">{{ alertasNoLeidasCount }}</p>
                        <p class="text-[9px] leading-tight text-muted-foreground md:text-xs">Alertas</p>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Controles globales: capa + refresh -->
        <div class="flex items-center justify-between">
            <div class="flex gap-1 rounded-lg bg-muted p-1">
                <button
                    v-for="(label, id) in CAPAS_LABELS"
                    :key="id"
                    type="button"
                    class="rounded-md px-2.5 py-1 text-xs transition-colors"
                    :class="
                        capaActiva === id
                            ? 'bg-primary text-primary-foreground'
                            : 'hover:bg-background'
                    "
                    @click="capaActiva = id as CapaId"
                >
                    {{ label }}
                </button>
            </div>

            <div class="flex items-center gap-2">
                <!-- Indicador de tracking activo -->
                <div
                    v-if="trackingState.activo"
                    class="flex items-center gap-2 rounded-lg bg-green-600 px-3 py-1.5 text-xs text-white shadow"
                >
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-white opacity-75" />
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-white" />
                    </span>
                    GPS activo · {{ trackingState.ultimoEnvio || 'conectando...' }}
                </div>

                <Button variant="outline" size="sm" @click="refrescarDatos">
                    <RefreshCw class="mr-1 h-3.5 w-3.5" />
                    Actualizar
                </Button>
            </div>
        </div>

        <!-- Mapas por terreno + Panel lateral -->
        <div class="flex flex-1 flex-col gap-4 md:flex-row">
            <!-- Grid de mapas por terreno -->
            <div class="flex-1">
                <div
                    v-if="props.terrenos.length === 0"
                    class="flex h-[300px] items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 text-muted-foreground"
                >
                    <div class="text-center">
                        <MapPin class="mx-auto mb-2 h-8 w-8 opacity-50" />
                        <p class="text-sm font-medium">Sin terrenos registrados</p>
                        <p class="text-xs">Registra terrenos para verlos en el mapa.</p>
                    </div>
                </div>

                <div
                    v-else
                    class="grid gap-4"
                    :class="props.terrenos.length === 1 ? 'grid-cols-1' : 'grid-cols-1 lg:grid-cols-2'"
                >
                    <TerrenoMapCard
                        v-for="terreno in props.terrenos"
                        :key="terreno.id"
                        :terreno="terreno"
                        :animales="animalesLocales"
                        :capa-activa="capaActiva"
                    />
                </div>
            </div>

            <!-- Panel derecho: Alertas / Tracking -->
            <div class="flex w-full flex-col md:w-80">
                <!-- Tabs -->
                <div class="mb-2 flex gap-1 rounded-lg bg-muted p-1">
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
                    <CardContent class="flex-1 space-y-2 overflow-y-auto p-3 pt-0">
                        <div
                            v-if="alertasLocales.length === 0"
                            class="flex flex-col items-center justify-center py-8 text-center text-muted-foreground"
                        >
                            <Check class="mb-2 h-8 w-8 text-green-500" />
                            <p class="text-sm font-medium">Sin alertas</p>
                            <p class="text-xs">Todos los animales están dentro de sus terrenos.</p>
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
                                    :class="alerta.leida ? 'text-muted-foreground' : 'text-red-500'"
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
                        <CardTitle class="text-sm">Emular Collar GPS</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 p-3 pt-0">
                        <p class="text-xs text-muted-foreground">
                            Usa el GPS de tu celular para simular un collar.
                            La ubicación se envía al servidor cada minuto.
                        </p>

                        <div v-if="!trackingState.activo">
                            <label class="mb-1.5 block text-xs font-medium">
                                Seleccionar collar
                            </label>
                            <select
                                v-model="collarSeleccionado"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                            >
                                <option :value="null" disabled>-- Elegir collar --</option>
                                <option
                                    v-for="c in collaresDisponibles"
                                    :key="c.id"
                                    :value="c.id"
                                >
                                    {{ c.serie }}
                                    {{ c.animal ? `(${c.animal.nombre})` : '' }}
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
                            <div class="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-900 dark:bg-green-950/30">
                                <div class="flex items-center gap-2">
                                    <span class="relative flex h-2.5 w-2.5">
                                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75" />
                                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-green-500" />
                                    </span>
                                    <span class="text-xs font-medium text-green-700 dark:text-green-400">
                                        Tracking activo
                                    </span>
                                </div>

                                <div class="mt-2 space-y-1 text-[11px] text-muted-foreground">
                                    <p>Lat: {{ trackingState.ultimaLat?.toFixed(6) || '...' }}</p>
                                    <p>Lng: {{ trackingState.ultimaLng?.toFixed(6) || '...' }}</p>
                                    <p>Último envío: {{ trackingState.ultimoEnvio || 'pendiente' }}</p>
                                    <p v-if="trackingState.dentroDeTerreno !== null">
                                        Estado:
                                        <span
                                            :class="
                                                trackingState.dentroDeTerreno
                                                    ? 'font-medium text-green-600'
                                                    : 'font-medium text-red-600'
                                            "
                                        >
                                            {{ trackingState.dentroDeTerreno ? 'Dentro del terreno' : 'FUERA DEL TERRENO' }}
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
.maplibregl-popup-content {
    border-radius: 8px !important;
    padding: 10px 14px !important;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15) !important;
}
</style>
