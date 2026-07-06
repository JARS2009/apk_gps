<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Configuracion } from '@/types/models/configuracion';

const props = defineProps<{
    configuracion: Configuracion;
    action: string;
}>();

const emit = defineEmits<{
    success: [];
}>();
</script>

<template>
    <Form
        :action="props.action"
        method="put"
        class="space-y-6"
        v-slot="{ errors, processing }"
        @success="emit('success')"
    >
        <div class="grid gap-2">
            <Label for="telefono_policia">Teléfono de policía</Label>
            <Input
                id="telefono_policia"
                name="telefono_policia"
                :default-value="configuracion.telefono_policia ?? undefined"
            />
            <InputError :message="errors.telefono_policia" />
        </div>

        <div class="grid gap-2">
            <Label for="telefono_emergencia">Teléfono de emergencia</Label>
            <Input
                id="telefono_emergencia"
                name="telefono_emergencia"
                :default-value="configuracion.telefono_emergencia ?? undefined"
            />
            <InputError :message="errors.telefono_emergencia" />
        </div>

        <div class="grid gap-2">
            <Label for="mensaje_alerta">Mensaje de alerta</Label>
            <Input
                id="mensaje_alerta"
                name="mensaje_alerta"
                :default-value="configuracion.mensaje_alerta ?? undefined"
            />
            <InputError :message="errors.mensaje_alerta" />
        </div>

        <div class="flex items-center gap-2">
            <input
                id="alertas_activas"
                type="checkbox"
                name="alertas_activas"
                value="1"
                :checked="configuracion.alertas_activas"
                class="size-4 rounded border-input"
            />
            <Label for="alertas_activas">Alertas activas</Label>
            <InputError :message="errors.alertas_activas" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Guardar</Button>
        </div>
    </Form>
</template>
