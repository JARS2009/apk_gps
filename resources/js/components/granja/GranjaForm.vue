<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Granja } from '@/types/models/granja';

const props = defineProps<{
    granja?: Granja;
    action: string;
    method?: 'post' | 'put';
}>();

const emit = defineEmits<{
    success: [];
}>();
</script>

<template>
    <Form
        :action="props.action"
        :method="props.method ?? 'post'"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <div class="grid gap-2">
            <Label for="nombre">Nombre</Label>
            <Input
                id="nombre"
                name="nombre"
                :default-value="granja?.nombre"
                required
            />
            <InputError :message="errors.nombre" />
        </div>

        <div class="grid gap-2">
            <Label for="descripcion">Descripción</Label>
            <Input
                id="descripcion"
                name="descripcion"
                :default-value="granja?.descripcion ?? undefined"
            />
            <InputError :message="errors.descripcion" />
        </div>

        <div class="flex items-center gap-2">
            <input
                id="activa"
                type="checkbox"
                name="activa"
                value="1"
                :checked="granja?.activa ?? true"
                class="size-4 rounded border-input"
            />
            <Label for="activa">Granja activa</Label>
            <InputError :message="errors.activa" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Guardar</Button>
        </div>
    </Form>
</template>
