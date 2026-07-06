<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { DataTableColumn } from '@/components/shared/DataTable.vue';
import FormModal from '@/components/shared/FormModal.vue';
import { Button } from '@/components/ui/button';
import type { Animal } from '@/types/models/animal';
import type { Granja } from '@/types/models/granja';
import type { PaginatedResponse } from '@/types/models/pagination';
import type { Terreno } from '@/types/models/terreno';

const props = defineProps<{
    terrenos: PaginatedResponse<Terreno>;
    filters: { search?: string };
    granjas: Granja[];
    animalesTerreno?: Animal[] | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Terrenos', href: '/terrenos' }],
    },
});

const columns: DataTableColumn<Terreno>[] = [
    { key: 'nombre', label: 'Nombre' },
    {
        key: 'granja',
        label: 'Granja',
        render: (row) => row.granja?.nombre ?? '-',
    },
    {
        key: 'area',
        label: 'Área (ha)',
        render: (row) => (row.area != null ? String(row.area) : '-'),
    },
];

function abrirCrear(): void {
    router.visit('/terrenos/create');
}

function abrirEditar(terreno: Terreno): void {
    router.visit(`/terrenos/${terreno.id}/edit`);
}

const confirmOpen = ref(false);
const toDelete = ref<Terreno | null>(null);

function pedirEliminar(terreno: Terreno): void {
    toDelete.value = terreno;
    confirmOpen.value = true;
}

function eliminar(): void {
    if (!toDelete.value) {
        return;
    }

    router.delete(`/terrenos/${toDelete.value.id}`, {
        onFinish: () => {
            confirmOpen.value = false;
            toDelete.value = null;
        },
    });
}

// Ver animales: se pide la prop `animalesTerreno` de forma parcial (Inertia
// `only`) sin navegar de página, reutilizando la misma vista terreno/Index.
// Ver GranjaController::configuracion() para el mismo patrón.
const animalesOpen = ref(false);
const terrenoConsultado = ref<Terreno | null>(null);

function abrirAnimales(terreno: Terreno): void {
    terrenoConsultado.value = terreno;

    router.get(
        `/terrenos/${terreno.id}/animales`,
        {},
        {
            only: ['animalesTerreno'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                animalesOpen.value = true;
            },
        },
    );
}

function cerrarAnimales(): void {
    animalesOpen.value = false;
    terrenoConsultado.value = null;
}
</script>

<template>
    <Head title="Terrenos" />

    <div class="space-y-6 p-4">
        <DataTable
            :data="props.terrenos"
            :columns="columns"
            :filters="props.filters"
            search-placeholder="Buscar terreno..."
        >
            <template #create>
                <Button @click="abrirCrear">
                    <Plus class="size-4" />
                    Nuevo terreno
                </Button>
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="abrirAnimales(row)"
                    >
                        Ver animales
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="abrirEditar(row)"
                    >
                        Editar
                    </Button>
                    <Button
                        size="sm"
                        variant="destructive"
                        @click="pedirEliminar(row)"
                        >Eliminar</Button
                    >
                </div>
            </template>
        </DataTable>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar terreno"
            description="Esta acción eliminará el terreno de forma lógica."
            @confirm="eliminar"
        />

        <FormModal
            v-model:open="animalesOpen"
            :title="`Animales en ${terrenoConsultado?.nombre ?? ''}`"
            description="Animales que tienen este terreno asignado (un mismo animal puede estar en varios terrenos, como su potrero y su establo)."
        >
            <div
                v-if="
                    !props.animalesTerreno || props.animalesTerreno.length === 0
                "
                class="text-sm text-muted-foreground"
            >
                Este terreno no tiene animales asignados todavía.
            </div>
            <ul v-else class="space-y-2">
                <li
                    v-for="animal in props.animalesTerreno"
                    :key="animal.id"
                    class="flex items-center justify-between rounded-md border p-3 text-sm"
                >
                    <span>
                        <span class="font-medium">{{ animal.codigo }}</span>
                        — {{ animal.nombre }}
                        <span v-if="animal.tipo" class="text-muted-foreground"
                            >({{ animal.tipo }})</span
                        >
                    </span>
                    <span class="text-muted-foreground">
                        {{ animal.collar?.serie ?? 'Sin collar' }}
                    </span>
                </li>
            </ul>
            <div class="flex justify-end pt-2">
                <Button variant="outline" @click="cerrarAnimales"
                    >Cerrar</Button
                >
            </div>
        </FormModal>
    </div>
</template>
