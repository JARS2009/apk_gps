<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import type { Compra } from '@/types/models/compra';
import type { Granja } from '@/types/models/granja';

const props = defineProps<{
    compra?: Compra;
    granjas: Granja[];
    action: string;
    method?: 'post' | 'put';
}>();

const emit = defineEmits<{
    success: [];
}>();

const granjaUnica = computed<Granja | null>(() =>
    props.granjas.length === 1 ? props.granjas[0] : null,
);

const granjaId = ref<string>(
    props.compra?.granja_id
        ? String(props.compra.granja_id)
        : granjaUnica.value
          ? String(granjaUnica.value.id)
          : '',
);
</script>

<template>
    <Form
        :action="props.action"
        :method="props.method ?? 'post'"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <input type="hidden" name="granja_id" :value="granjaId" />

        <div class="grid gap-2">
            <Label v-if="granjas.length > 1" for="granja_id">Granja</Label>
            <Select v-if="granjas.length > 1" v-model="granjaId">
                <SelectTrigger id="granja_id" class="w-full">
                    <SelectValue placeholder="Selecciona una granja" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="granja in granjas"
                        :key="granja.id"
                        :value="String(granja.id)"
                    >
                        {{ granja.nombre }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <p v-else-if="granjaUnica" class="text-sm text-muted-foreground">
                Granja:
                <span class="font-medium text-foreground">{{
                    granjaUnica.nombre
                }}</span>
            </p>
            <InputError :message="errors.granja_id" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div class="grid gap-2">
                <Label for="serie">Serie</Label>
                <Input
                    id="serie"
                    name="serie"
                    :default-value="compra?.serie"
                    placeholder="COM"
                    required
                />
                <InputError :message="errors.serie" />
            </div>

            <div class="grid gap-2">
                <Label for="correlativo">Correlativo</Label>
                <Input
                    id="correlativo"
                    name="correlativo"
                    :default-value="compra?.correlativo"
                    placeholder="0034"
                    required
                />
                <InputError :message="errors.correlativo" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="proveedor">Proveedor</Label>
            <Input
                id="proveedor"
                name="proveedor"
                :default-value="compra?.proveedor ?? undefined"
                placeholder="Nombre del proveedor"
            />
            <InputError :message="errors.proveedor" />
        </div>

        <div class="grid gap-2">
            <Label for="fecha">Fecha</Label>
            <Input
                id="fecha"
                name="fecha"
                type="date"
                :default-value="compra?.fecha?.substring(0, 10)"
                required
            />
            <InputError :message="errors.fecha" />
        </div>

        <div class="grid gap-2">
            <Label for="observaciones">Observaciones</Label>
            <Input
                id="observaciones"
                name="observaciones"
                :default-value="compra?.observaciones ?? undefined"
            />
            <InputError :message="errors.observaciones" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Guardar</Button>
        </div>
    </Form>
</template>
