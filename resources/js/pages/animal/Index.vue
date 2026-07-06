<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import AnimalForm from '@/components/animal/AnimalForm.vue';
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
    animales: PaginatedResponse<Animal>;
    filters: { search?: string };
    granjas: Granja[];
    terrenos: Terreno[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Animales', href: '/animales' }],
    },
});

const columns: DataTableColumn<Animal>[] = [
    { key: 'codigo', label: 'Código' },
    { key: 'nombre', label: 'Nombre' },
    { key: 'tipo', label: 'Tipo' },
    {
        key: 'granja',
        label: 'Granja',
        render: (row) => row.granja?.nombre ?? '-',
    },
    {
        key: 'collar',
        label: 'Collar',
        render: (row) => row.collar?.serie ?? 'Sin collar',
    },
    {
        key: 'terrenos',
        label: 'Terrenos',
        render: (row) =>
            row.terrenos && row.terrenos.length > 0
                ? row.terrenos.map((t) => t.nombre).join(', ')
                : 'Sin asignar',
    },
];

const formOpen = ref(false);
const editing = ref<Animal | null>(null);

function abrirCrear(): void {
    editing.value = null;
    formOpen.value = true;
}

function abrirEditar(animal: Animal): void {
    editing.value = animal;
    formOpen.value = true;
}

function cerrarFormulario(): void {
    formOpen.value = false;
    editing.value = null;
}

const confirmOpen = ref(false);
const toDelete = ref<Animal | null>(null);

function pedirEliminar(animal: Animal): void {
    toDelete.value = animal;
    confirmOpen.value = true;
}

function eliminar(): void {
    if (!toDelete.value) {
        return;
    }

    router.delete(`/animales/${toDelete.value.id}`, {
        onFinish: () => {
            confirmOpen.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Animales" />

    <div class="space-y-6 p-4">
        <DataTable
            :data="props.animales"
            :columns="columns"
            :filters="props.filters"
            search-placeholder="Buscar animal..."
        >
            <template #create>
                <Button @click="abrirCrear">
                    <Plus class="size-4" />
                    Nuevo animal
                </Button>
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
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

        <FormModal
            v-model:open="formOpen"
            :title="editing ? 'Editar animal' : 'Nuevo animal'"
            :description="
                editing
                    ? `Editando ${editing.nombre}`
                    : 'Registra un nuevo animal en el sistema.'
            "
        >
            <AnimalForm
                :animal="editing ?? undefined"
                :granjas="props.granjas"
                :terrenos="props.terrenos"
                :action="editing ? `/animales/${editing.id}` : '/animales'"
                :method="editing ? 'put' : 'post'"
                @success="cerrarFormulario"
            />
        </FormModal>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar animal"
            description="Esta acción eliminará el animal de forma lógica."
            @confirm="eliminar"
        />
    </div>
</template>
