<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ref } from 'vue';

const props = defineProps<{
    action: string;
}>();

const emit = defineEmits<{
    success: [];
}>();

const tipoDocumento = ref<string>('');

const tiposDisponibles = [
    { value: 'factura', label: 'Factura' },
    { value: 'guia', label: 'Guia de Remision' },
    { value: 'boleta', label: 'Boleta' },
    { value: 'nota_credito', label: 'Nota de Credito' },
    { value: 'nota_debito', label: 'Nota de Debito' },
    { value: 'otro', label: 'Otro' },
];
</script>

<template>
    <Form
        :action="props.action"
        method="post"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <input type="hidden" name="tipo_documento" :value="tipoDocumento" />

        <div class="grid gap-2">
            <Label for="tipo_documento">Tipo de documento</Label>
            <Select v-model="tipoDocumento">
                <SelectTrigger id="tipo_documento" class="w-full">
                    <SelectValue placeholder="Selecciona tipo" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="tipo in tiposDisponibles"
                        :key="tipo.value"
                        :value="tipo.value"
                    >
                        {{ tipo.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="errors.tipo_documento" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="serie_documento">Serie</Label>
                <Input
                    id="serie_documento"
                    name="serie_documento"
                    placeholder="F001"
                    required
                />
                <InputError :message="errors.serie_documento" />
            </div>

            <div class="grid gap-2">
                <Label for="correlativo_documento">Correlativo</Label>
                <Input
                    id="correlativo_documento"
                    name="correlativo_documento"
                    placeholder="001"
                    required
                />
                <InputError :message="errors.correlativo_documento" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="fecha_documento">Fecha del documento</Label>
            <Input
                id="fecha_documento"
                name="fecha_documento"
                type="date"
            />
            <InputError :message="errors.fecha_documento" />
        </div>

        <div class="grid gap-2">
            <Label for="doc_observaciones">Observaciones</Label>
            <Input
                id="doc_observaciones"
                name="observaciones"
            />
            <InputError :message="errors.observaciones" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Agregar documento</Button>
        </div>
    </Form>
</template>
