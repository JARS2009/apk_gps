<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfiguracionForm from '@/components/configuracion/ConfiguracionForm.vue';
import GranjaForm from '@/components/granja/GranjaForm.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { DataTableColumn } from '@/components/shared/DataTable.vue';
import FormModal from '@/components/shared/FormModal.vue';
import { Button } from '@/components/ui/button';
import type { Configuracion } from '@/types/models/configuracion';
import type { Granja } from '@/types/models/granja';
import type { PaginatedResponse } from '@/types/models/pagination';

const props = defineProps<{
    granjas: PaginatedResponse<Granja>;
    filters: { search?: string };
    configuracion?: Configuracion | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Granjas', href: '/granjas' }],
    },
});

const page = usePage();
const isSuperAdmin = computed(
    () => page.props.auth.user.role === 'super_admin',
);

const columns: DataTableColumn<Granja>[] = [
    { key: 'nombre', label: 'Nombre' },
    { key: 'descripcion', label: 'Descripción' },
    {
        key: 'activa',
        label: 'Activa',
        render: (row) => (row.activa ? 'Sí' : 'No'),
    },
];

const formOpen = ref(false);
const editing = ref<Granja | null>(null);

function abrirCrear(): void {
    editing.value = null;
    formOpen.value = true;
}

function abrirEditar(granja: Granja): void {
    editing.value = granja;
    formOpen.value = true;
}

function cerrarFormulario(): void {
    formOpen.value = false;
    editing.value = null;
}

const confirmOpen = ref(false);
const toDelete = ref<Granja | null>(null);

function pedirEliminar(granja: Granja): void {
    toDelete.value = granja;
    confirmOpen.value = true;
}

function eliminar(): void {
    if (!toDelete.value) {
        return;
    }

    router.delete(`/granjas/${toDelete.value.id}`, {
        onFinish: () => {
            confirmOpen.value = false;
            toDelete.value = null;
        },
    });
}

// Configuración: se abre como modal pidiendo solo la prop `configuracion` de
// forma parcial (Inertia `only`) sin navegar de página, reutilizando la misma
// vista granja/Index. Ver GranjaController::configuracion().
const configuracionOpen = ref(false);
const configuracionGranja = ref<Granja | null>(null);

function abrirConfiguracion(granja: Granja): void {
    configuracionGranja.value = granja;

    router.get(
        `/granjas/${granja.id}/configuracion`,
        {},
        {
            only: ['configuracion'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                configuracionOpen.value = true;
            },
        },
    );
}

function cerrarConfiguracion(): void {
    configuracionOpen.value = false;
    configuracionGranja.value = null;
}
</script>

<template>
    <Head title="Granjas" />

    <div class="space-y-6 p-4">
        <DataTable
            :data="props.granjas"
            :columns="columns"
            :filters="props.filters"
            search-placeholder="Buscar granja..."
        >
            <template v-if="isSuperAdmin" #create>
                <Button @click="abrirCrear">
                    <Plus class="size-4" />
                    Nueva granja
                </Button>
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Button
                        v-if="isSuperAdmin"
                        size="sm"
                        variant="outline"
                        @click="abrirEditar(row)"
                    >
                        Editar
                    </Button>
                    <Button
                        size="sm"
                        variant="outline"
                        @click="abrirConfiguracion(row)"
                    >
                        Configuración
                    </Button>
                    <Button
                        v-if="isSuperAdmin"
                        size="sm"
                        variant="destructive"
                        @click="pedirEliminar(row)"
                    >
                        Eliminar
                    </Button>
                </div>
            </template>
        </DataTable>

        <FormModal
            v-model:open="formOpen"
            :title="editing ? 'Editar granja' : 'Nueva granja'"
            :description="
                editing
                    ? `Editando ${editing.nombre}`
                    : 'Crea una nueva granja en el sistema.'
            "
        >
            <GranjaForm
                :granja="editing ?? undefined"
                :action="editing ? `/granjas/${editing.id}` : '/granjas'"
                :method="editing ? 'put' : 'post'"
                @success="cerrarFormulario"
            />
        </FormModal>

        <FormModal
            v-model:open="configuracionOpen"
            :title="`Configuración de ${configuracionGranja?.nombre ?? ''}`"
            description="Datos de contacto y alertas."
        >
            <ConfiguracionForm
                v-if="configuracionGranja && props.configuracion"
                :configuracion="props.configuracion"
                :action="`/granjas/${configuracionGranja.id}/configuracion`"
                @success="cerrarConfiguracion"
            />
        </FormModal>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar granja"
            description="Esta acción eliminará la granja de forma lógica."
            @confirm="eliminar"
        />
    </div>
</template>
