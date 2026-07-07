<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from '@lucide/vue';
import TerrenoMapCard from '@/components/dashboard/TerrenoMapCard.vue';
import type { Terreno } from '@/types/models/terreno';
import type { Animal } from '@/types/models/animal';

const props = defineProps<{
    terreno: Terreno;
    animales: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Terrenos', href: '/terrenos' },
            { title: 'Detalles del Terreno', href: '#' },
        ],
    },
});

const capaActiva = ref<'satelite' | 'hibrido' | 'calles'>('satelite');

function volver() {
    router.visit('/terrenos');
}
</script>

<template>
    <Head :title="`Terreno - ${props.terreno.nombre}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center gap-3">
            <Button variant="outline" size="icon" class="h-8 w-8 shrink-0" @click="volver">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <div class="flex flex-col">
                <h1 class="text-xl font-bold tracking-tight text-foreground md:text-2xl">
                    {{ props.terreno.nombre }}
                </h1>
                <p class="text-xs text-muted-foreground">
                    Granja: {{ props.terreno.granja?.nombre || '-' }}
                </p>
            </div>
        </div>

        <!-- Barra de info y controles -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <!-- Stats rápidas -->
            <div class="flex flex-wrap gap-4 rounded-lg border border-sidebar-border/70 bg-card p-3 shadow-sm dark:border-sidebar-border">
                <div class="flex flex-col">
                    <span class="text-[10px] font-medium uppercase text-muted-foreground">Área</span>
                    <span class="text-sm font-semibold">{{ props.terreno.area ? `${props.terreno.area} ha` : '-' }}</span>
                </div>
                <div class="h-8 w-px bg-border"></div>
                <div class="flex flex-col">
                    <span class="text-[10px] font-medium uppercase text-muted-foreground">Animales granja</span>
                    <span class="text-sm font-semibold">{{ props.animales.length }}</span>
                </div>
            </div>

            <!-- Selector de capas -->
            <div class="flex w-max gap-1 rounded-lg bg-muted p-1">
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1 text-xs transition-colors"
                    :class="capaActiva === 'satelite' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:bg-background/50'"
                    @click="capaActiva = 'satelite'"
                >
                    Satélite
                </button>
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1 text-xs transition-colors"
                    :class="capaActiva === 'hibrido' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:bg-background/50'"
                    @click="capaActiva = 'hibrido'"
                >
                    Híbrido
                </button>
                <button
                    type="button"
                    class="rounded-md px-2.5 py-1 text-xs transition-colors"
                    :class="capaActiva === 'calles' ? 'bg-background shadow text-foreground' : 'text-muted-foreground hover:bg-background/50'"
                    @click="capaActiva = 'calles'"
                >
                    Calles
                </button>
            </div>
        </div>

        <!-- Mapa y Lista -->
        <div class="flex flex-1 flex-col gap-4">
            <TerrenoMapCard 
                :terreno="props.terreno" 
                :animales="props.animales" 
                :capa-activa="capaActiva"
                hide-header
                map-height-class="h-full min-h-[350px] md:min-h-[500px]"
                class="flex-1 shadow-sm"
            />
            
            <div class="rounded-lg border border-sidebar-border/70 bg-card shadow-sm dark:border-sidebar-border overflow-hidden">
                <div class="p-4 border-b border-sidebar-border/70 bg-muted/20 dark:border-sidebar-border">
                    <h3 class="font-semibold text-sm">Animales de la granja ({{ props.animales.length }})</h3>
                </div>
                <div v-if="props.animales.length === 0" class="p-6 text-center text-sm text-muted-foreground">
                    No hay animales asignados.
                </div>
                <div v-else class="divide-y divide-border">
                    <div v-for="animal in props.animales" :key="animal.id" class="flex flex-col sm:flex-row sm:items-center justify-between p-4 hover:bg-muted/30 transition-colors gap-2">
                        <div class="flex flex-col">
                            <span class="font-semibold text-sm">{{ animal.nombre }} <span class="text-xs font-normal text-muted-foreground ml-1">({{ animal.codigo }})</span></span>
                            <span class="text-xs text-muted-foreground mt-0.5">Collar: {{ animal.collar?.serie || 'Sin collar' }}</span>
                        </div>
                        <div class="flex flex-col sm:items-end">
                            <span v-if="animal.collar?.ubicaciones?.length" class="text-xs text-muted-foreground">
                                Última señal: {{ new Date(animal.collar.ubicaciones[0].recibido_en).toLocaleString() }}
                            </span>
                            <span v-else class="text-xs text-amber-600/80 dark:text-amber-500/80 font-medium">
                                Sin ubicación registrada
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
