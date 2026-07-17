<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { MapPin, Plus } from '@lucide/vue';
import { ref } from 'vue';
import CollarForm from '@/components/collar/CollarForm.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { DataTableColumn } from '@/components/shared/DataTable.vue';
import FormModal from '@/components/shared/FormModal.vue';
import { Button } from '@/components/ui/button';
import type { Animal } from '@/types/models/animal';
import type { Collar } from '@/types/models/collar';
import type { PaginatedResponse } from '@/types/models/pagination';

const props = defineProps<{
    collares: PaginatedResponse<Collar>;
    filters: { search?: string };
    animales: Animal[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Collares', href: '/collares' }],
    },
});

const columns: DataTableColumn<Collar>[] = [
    { key: 'serie', label: 'Serie' },
    { key: 'modelo', label: 'Modelo' },
    {
        key: 'imei',
        label: 'IMEI',
        render: (row) => row.imei || '—',
    },
    { key: 'estado', label: 'Estado' },
    {
        key: 'animal',
        label: 'Animal asignado',
        render: (row) => row.animal?.nombre ?? 'Sin asignar',
    },
];

const formOpen = ref(false);
const editing = ref<Collar | null>(null);

function abrirCrear(): void {
    editing.value = null;
    formOpen.value = true;
}

function abrirEditar(collar: Collar): void {
    editing.value = collar;
    formOpen.value = true;
}

function cerrarFormulario(): void {
    formOpen.value = false;
    editing.value = null;
}

const confirmOpen = ref(false);
const toDelete = ref<Collar | null>(null);

function pedirEliminar(collar: Collar): void {
    toDelete.value = collar;
    confirmOpen.value = true;
}

function eliminar(): void {
    if (!toDelete.value) {
        return;
    }

    router.delete(`/collares/${toDelete.value.id}`, {
        onFinish: () => {
            confirmOpen.value = false;
            toDelete.value = null;
        },
    });
}

function desasignar(collar: Collar): void {
    router.patch(`/collares/${collar.id}/asignar`, { animal_id: null });
}
</script>

<template>
    <Head title="Collares" />

    <div class="space-y-6 p-4">
        <DataTable
            :data="props.collares"
            :columns="columns"
            :filters="props.filters"
            search-placeholder="Buscar collar..."
        >
            <template #create>
                <Button @click="abrirCrear">
                    <Plus class="size-4" />
                    Nuevo collar
                </Button>
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Link v-if="row.imei" :href="`/collares/${row.id}/ruta`">
                        <Button size="sm" variant="outline">
                            <MapPin class="mr-1 size-3.5" />
                            Ruta
                        </Button>
                    </Link>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="abrirEditar(row)"
                    >
                        Editar
                    </Button>
                    <Button
                        v-if="row.animal_id"
                        size="sm"
                        variant="outline"
                        @click="desasignar(row)"
                    >
                        Desasignar
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
            :title="editing ? 'Editar collar' : 'Nuevo collar'"
            :description="
                editing
                    ? `Editando ${editing.serie}`
                    : 'Registra un nuevo collar GPS.'
            "
        >
            <CollarForm
                :collar="editing ?? undefined"
                :animales="props.animales"
                :action="editing ? `/collares/${editing.id}` : '/collares'"
                :method="editing ? 'put' : 'post'"
                @success="cerrarFormulario"
            />
        </FormModal>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar collar"
            description="Esta acción eliminará el collar de forma lógica."
            @confirm="eliminar"
        />
    </div>
</template>
