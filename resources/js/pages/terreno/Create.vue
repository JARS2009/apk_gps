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

const props = defineProps<{
    granjas: Granja[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Terrenos', href: '/terrenos' },
            { title: 'Nuevo Terreno', href: '/terrenos/create' },
        ],
    },
});
</script>

<template>
    <Head title="Nuevo Terreno" />

    <div class="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
        <div class="flex flex-col gap-2">
            <h1 class="text-3xl font-bold tracking-tight text-foreground">
                Crear Terreno
            </h1>
            <p class="text-sm text-muted-foreground">
                Registra un nuevo terreno o potrero dibujando su área en el mapa
                interactivo.
            </p>
        </div>

        <Card
            class="border-sidebar-border/70 shadow-sm dark:border-sidebar-border"
        >
            <CardHeader class="pb-4">
                <CardTitle class="text-xl">Datos del Terreno</CardTitle>
                <CardDescription>
                    Completa la información básica y traza los límites del
                    terreno.
                </CardDescription>
            </CardHeader>
            <CardContent>
                <TerrenoForm
                    :granjas="props.granjas"
                    action="/terrenos"
                    method="post"
                />
            </CardContent>
        </Card>
    </div>
</template>
