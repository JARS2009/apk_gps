<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import TerrenoForm from '@/components/terreno/TerrenoForm.vue';
import type { Granja } from '@/types/models/granja';
import type { Terreno } from '@/types/models/terreno';

const props = defineProps<{
    terreno: Terreno;
    granjas: Granja[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Terrenos', href: '/terrenos' },
            { title: 'Editar Terreno', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Editar Terreno - ${props.terreno.nombre}`" />

    <div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold tracking-tight text-foreground">
                Editar Terreno
            </h1>
            <p class="text-sm text-muted-foreground">
                Modifica los datos del terreno o vuelve a dibujar su contorno en
                el mapa.
            </p>
        </div>

        <Card
            class="border-sidebar-border/70 shadow-sm dark:border-sidebar-border"
        >
            <CardHeader class="pb-4">
                <CardTitle class="text-xl"
                    >Modificar {{ props.terreno.nombre }}</CardTitle
                >
                <CardDescription>
                    Actualiza la información básica y las coordenadas del
                    terreno.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <TerrenoForm
                    :terreno="props.terreno"
                    :granjas="props.granjas"
                    :action="`/terrenos/${props.terreno.id}`"
                    method="put"
                />
            </CardContent>
        </Card>
    </div>
</template>
