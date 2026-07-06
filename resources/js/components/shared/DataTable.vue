<script setup lang="ts" generic="T extends { id: number | string }">
import { Link, router } from '@inertiajs/vue3';
import { ChevronDown, ChevronUp, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { PaginatedResponse } from '@/types/models/pagination';

export interface DataTableColumn<T> {
    key: string;
    label: string;
    render?: (row: T) => string;
}

const props = defineProps<{
    data: PaginatedResponse<T>;
    columns: DataTableColumn<T>[];
    filters?: { search?: string };
    createHref?: string;
    createLabel?: string;
    searchPlaceholder?: string;
}>();

const search = ref(props.filters?.search ?? '');

function buscar(): void {
    router.get(
        window.location.pathname,
        { search: search.value },
        { preserveState: true, replace: true },
    );
}

function irA(url: string | null): void {
    if (!url) {
        return;
    }

    router.get(url, {}, { preserveState: true });
}

function valorCelda(row: T, column: DataTableColumn<T>): string {
    if (column.render) {
        return column.render(row);
    }

    const value = (row as Record<string, unknown>)[column.key];

    return value === null || value === undefined ? '-' : String(value);
}

const expandedRows = ref<Record<string | number, boolean>>({});

function toggleRow(rowId: string | number): void {
    expandedRows.value[rowId] = !expandedRows.value[rowId];
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div class="relative w-full max-w-sm">
                <Search
                    class="absolute top-1/2 left-2.5 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    :placeholder="searchPlaceholder ?? 'Buscar...'"
                    class="pl-8"
                    @keyup.enter="buscar"
                />
            </div>
            <slot name="create">
                <Button v-if="createHref" as-child>
                    <Link :href="createHref">
                        <Plus class="size-4" />
                        {{ createLabel ?? 'Crear' }}
                    </Link>
                </Button>
            </slot>
        </div>

        <!-- Desktop Table View -->
        <div class="hidden overflow-x-auto rounded-md border md:block">
            <table class="w-full text-sm">
                <thead class="border-b bg-muted/50">
                    <tr>
                        <th
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-2 text-left font-medium text-muted-foreground"
                        >
                            {{ column.label }}
                        </th>
                        <th
                            class="px-4 py-2 text-right font-medium text-muted-foreground"
                        >
                            <span class="sr-only">Acciones</span>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="data.data.length === 0">
                        <td
                            :colspan="columns.length + 1"
                            class="px-4 py-8 text-center text-muted-foreground"
                        >
                            No hay registros para mostrar.
                        </td>
                    </tr>
                    <tr
                        v-for="row in data.data"
                        :key="String(row.id)"
                        class="border-b last:border-0 hover:bg-muted/30"
                    >
                        <td
                            v-for="column in columns"
                            :key="column.key"
                            class="px-4 py-2"
                        >
                            {{ valorCelda(row, column) }}
                        </td>
                        <td class="px-4 py-2 text-right">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Accordion View -->
        <div class="space-y-3 md:hidden">
            <div
                v-if="data.data.length === 0"
                class="rounded-md border px-4 py-8 text-center text-muted-foreground"
            >
                No hay registros para mostrar.
            </div>
            <div
                v-for="row in data.data"
                :key="String(row.id)"
                class="overflow-hidden rounded-md border bg-card text-card-foreground shadow-sm"
            >
                <!-- Card Header (Toggle Button) -->
                <button
                    type="button"
                    class="flex w-full items-center justify-between p-4 text-left font-medium transition-colors hover:bg-muted/30"
                    @click="toggleRow(row.id)"
                >
                    <div class="flex flex-col gap-1">
                        <span class="text-sm font-semibold text-foreground">
                            {{ valorCelda(row, columns[0]) }}
                        </span>
                        <span
                            v-if="columns[1]"
                            class="text-xs text-muted-foreground"
                        >
                            {{ columns[1].label }}:
                            {{ valorCelda(row, columns[1]) }}
                        </span>
                    </div>
                    <component
                        :is="expandedRows[row.id] ? ChevronUp : ChevronDown"
                        class="size-4 shrink-0 text-muted-foreground transition-transform duration-200"
                    />
                </button>

                <!-- Card Body (Collapsible details) -->
                <div
                    v-show="expandedRows[row.id]"
                    class="space-y-3 border-t bg-muted/10 p-4 text-sm transition-all duration-200"
                >
                    <div class="space-y-2">
                        <div
                            v-for="column in columns"
                            :key="column.key"
                            class="flex items-start justify-between gap-4 border-b border-muted/50 py-1.5 last:border-0"
                        >
                            <span
                                class="shrink-0 font-medium text-muted-foreground"
                                >{{ column.label }}</span
                            >
                            <span
                                class="text-right font-medium break-all text-foreground"
                            >
                                {{ valorCelda(row, column) }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="$slots.actions"
                        class="flex justify-end gap-2 border-t border-muted/30 pt-3"
                    >
                        <slot name="actions" :row="row" />
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="data.last_page > 1"
            class="flex flex-wrap items-center justify-center gap-1"
        >
            <Button
                v-for="link in data.links"
                :key="link.label"
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                @click="irA(link.url)"
            >
                <span v-html="link.label" />
            </Button>
        </div>
    </div>
</template>
