<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { FileText, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import CompraForm from '@/components/compra/CompraForm.vue';
import DocumentoForm from '@/components/compra/DocumentoForm.vue';
import ConfirmDialog from '@/components/shared/ConfirmDialog.vue';
import DataTable from '@/components/shared/DataTable.vue';
import type { DataTableColumn } from '@/components/shared/DataTable.vue';
import FormModal from '@/components/shared/FormModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Compra } from '@/types/models/compra';
import type { Granja } from '@/types/models/granja';
import type { PaginatedResponse } from '@/types/models/pagination';

const props = defineProps<{
    compras: PaginatedResponse<Compra>;
    filters: { search?: string };
    granjas: Granja[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Compras', href: '/compras' }],
    },
});

const columns: DataTableColumn<Compra>[] = [
    {
        key: 'codigo',
        label: 'Codigo',
        render: (row) => `${row.serie}-${row.correlativo}`,
    },
    { key: 'proveedor', label: 'Proveedor' },
    {
        key: 'fecha',
        label: 'Fecha',
        render: (row) => row.fecha?.substring(0, 10) ?? '-',
    },
    {
        key: 'granja',
        label: 'Granja',
        render: (row) => row.granja?.nombre ?? '-',
    },
    {
        key: 'documentos',
        label: 'Documentos',
        render: (row) =>
            row.documentos && row.documentos.length > 0
                ? row.documentos
                      .map(
                          (d) =>
                              `${d.tipo_documento}: ${d.serie_documento}-${d.correlativo_documento}`,
                      )
                      .join(', ')
                : 'Sin documentos',
    },
];

// --- Modal crear/editar compra ---
const formOpen = ref(false);
const editing = ref<Compra | null>(null);

function abrirCrear(): void {
    editing.value = null;
    formOpen.value = true;
}

function abrirEditar(compra: Compra): void {
    editing.value = compra;
    formOpen.value = true;
}

function cerrarFormulario(): void {
    formOpen.value = false;
    editing.value = null;
}

// --- Modal agregar documento ---
const docFormOpen = ref(false);
const compraParaDoc = ref<Compra | null>(null);

function abrirAgregarDocumento(compra: Compra): void {
    compraParaDoc.value = compra;
    docFormOpen.value = true;
}

function cerrarDocFormulario(): void {
    docFormOpen.value = false;
    compraParaDoc.value = null;
}

// --- Confirmar eliminacion compra ---
const confirmOpen = ref(false);
const toDelete = ref<Compra | null>(null);

function pedirEliminar(compra: Compra): void {
    toDelete.value = compra;
    confirmOpen.value = true;
}

function eliminar(): void {
    if (!toDelete.value) {
        return;
    }

    router.delete(`/compras/${toDelete.value.id}`, {
        onFinish: () => {
            confirmOpen.value = false;
            toDelete.value = null;
        },
    });
}

// --- Eliminar documento ---
const confirmDocOpen = ref(false);
const docToDelete = ref<{ compraId: number; docId: number } | null>(null);

function pedirEliminarDocumento(compraId: number, docId: number): void {
    docToDelete.value = { compraId, docId };
    confirmDocOpen.value = true;
}

function eliminarDocumento(): void {
    if (!docToDelete.value) {
        return;
    }

    router.delete(
        `/compras/${docToDelete.value.compraId}/documentos/${docToDelete.value.docId}`,
        {
            onFinish: () => {
                confirmDocOpen.value = false;
                docToDelete.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Compras" />

    <div class="space-y-6 p-4">
        <DataTable
            :data="props.compras"
            :columns="columns"
            :filters="props.filters"
            search-placeholder="Buscar por codigo de compra, factura, guia..."
        >
            <template #create>
                <Button @click="abrirCrear">
                    <Plus class="size-4" />
                    Nueva compra
                </Button>
            </template>
            <template #actions="{ row }">
                <div class="flex justify-end gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        @click="abrirAgregarDocumento(row)"
                        title="Agregar documento"
                    >
                        <FileText class="size-4" />
                        Doc
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
                    >
                        Eliminar
                    </Button>
                </div>
            </template>
        </DataTable>

        <!-- Detalle de documentos por compra (vista mobile-friendly debajo de la tabla) -->
        <div
            v-for="compra in props.compras.data.filter(
                (c) => c.documentos && c.documentos.length > 0,
            )"
            :key="compra.id"
            class="rounded-md border p-4"
        >
            <h3 class="mb-2 text-sm font-semibold">
                Documentos de {{ compra.serie }}-{{ compra.correlativo }}
            </h3>
            <div class="space-y-2">
                <div
                    v-for="doc in compra.documentos"
                    :key="doc.id"
                    class="flex items-center justify-between rounded bg-muted/30 px-3 py-2 text-sm"
                >
                    <div class="flex items-center gap-2">
                        <Badge variant="secondary">{{
                            doc.tipo_documento
                        }}</Badge>
                        <span class="font-medium"
                            >{{ doc.serie_documento }}-{{
                                doc.correlativo_documento
                            }}</span
                        >
                        <span
                            v-if="doc.fecha_documento"
                            class="text-muted-foreground"
                        >
                            {{ doc.fecha_documento.substring(0, 10) }}
                        </span>
                    </div>
                    <Button
                        size="sm"
                        variant="ghost"
                        @click="
                            pedirEliminarDocumento(compra.id, doc.id)
                        "
                    >
                        <Trash2 class="size-4 text-destructive" />
                    </Button>
                </div>
            </div>
        </div>

        <!-- Modal crear/editar compra -->
        <FormModal
            v-model:open="formOpen"
            :title="editing ? 'Editar compra' : 'Nueva compra'"
            :description="
                editing
                    ? `Editando ${editing.serie}-${editing.correlativo}`
                    : 'Registra una nueva compra.'
            "
        >
            <CompraForm
                :compra="editing ?? undefined"
                :granjas="props.granjas"
                :action="
                    editing ? `/compras/${editing.id}` : '/compras'
                "
                :method="editing ? 'put' : 'post'"
                @success="cerrarFormulario"
            />
        </FormModal>

        <!-- Modal agregar documento -->
        <FormModal
            v-model:open="docFormOpen"
            title="Agregar documento"
            :description="
                compraParaDoc
                    ? `Agregando documento a ${compraParaDoc.serie}-${compraParaDoc.correlativo}`
                    : ''
            "
        >
            <DocumentoForm
                v-if="compraParaDoc"
                :action="`/compras/${compraParaDoc.id}/documentos`"
                @success="cerrarDocFormulario"
            />
        </FormModal>

        <!-- Confirmar eliminar compra -->
        <ConfirmDialog
            v-model:open="confirmOpen"
            title="Eliminar compra"
            description="Esta accion eliminara la compra y todos sus documentos asociados."
            @confirm="eliminar"
        />

        <!-- Confirmar eliminar documento -->
        <ConfirmDialog
            v-model:open="confirmDocOpen"
            title="Eliminar documento"
            description="Esta accion eliminara el documento de la compra."
            @confirm="eliminarDocumento"
        />
    </div>
</template>
