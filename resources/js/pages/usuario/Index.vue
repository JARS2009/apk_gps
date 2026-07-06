<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { DataTableColumn } from '@/components/shared/DataTable.vue';
import FormModal from '@/components/shared/FormModal.vue';
import { Button } from '@/components/ui/button';
import UserForm from '@/components/usuario/UserForm.vue';
import type { Granja } from '@/types/models/granja';
import type { PaginatedResponse } from '@/types/models/pagination';
import type { Usuario } from '@/types/models/user';

const props = defineProps<{
    usuarios: PaginatedResponse<Usuario>;
    filters: { search?: string };
    granjas: Granja[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Usuarios', href: '/usuarios' }],
    },
});

const columns: DataTableColumn<Usuario>[] = [
    { key: 'name', label: 'Nombre' },
    { key: 'email', label: 'Correo' },
    {
        key: 'role',
        label: 'Rol',
        render: (row) =>
            row.role === 'super_admin'
                ? 'Super Administrador'
                : 'Administrador',
    },
];

const formOpen = ref(false);
const editing = ref<Usuario | null>(null);

function abrirCrear(): void {
    editing.value = null;
    formOpen.value = true;
}

function abrirEditar(usuario: Usuario): void {
    editing.value = usuario;
    formOpen.value = true;
}

function cerrarFormulario(): void {
    formOpen.value = false;
    editing.value = null;
}

const confirmOpen = ref(false);
const toDelete = ref<Usuario | null>(null);

function pedirEliminar(usuario: Usuario): void {
    toDelete.value = usuario;
    confirmOpen.value = true;
}

function eliminar(): void {
    if (!toDelete.value) {
        return;
    }

    router.delete(`/usuarios/${toDelete.value.id}`, {
        onFinish: () => {
            confirmOpen.value = false;
            toDelete.value = null;
        },
    });
}
</script>

<template>
    <Head title="Usuarios" />

    <div class="space-y-6 p-4">
        <DataTable
            :data="props.usuarios"
            :columns="columns"
            :filters="props.filters"
            search-placeholder="Buscar usuario..."
        >
            <template #create>
                <Button @click="abrirCrear">
                    <Plus class="size-4" />
                    Nuevo usuario
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
            :title="editing ? 'Editar usuario' : 'Nuevo usuario'"
            :description="
                editing
                    ? `Editando ${editing.name}`
                    : 'Crea un nuevo usuario del sistema.'
            "
        >
            <UserForm
                :usuario="editing ?? undefined"
                :granjas="props.granjas"
                :action="editing ? `/usuarios/${editing.id}` : '/usuarios'"
                :method="editing ? 'put' : 'post'"
                @success="cerrarFormulario"
            />
        </FormModal>

        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar usuario"
            description="Esta acción eliminará el usuario de forma lógica."
            @confirm="eliminar"
        />
    </div>
</template>
