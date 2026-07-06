<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
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
import type { Granja } from '@/types/models/granja';
import type { Coordenada, Terreno } from '@/types/models/terreno';
import TerrenoMapa from './TerrenoMapa.vue';

const props = defineProps<{
    terreno?: Terreno;
    granjas: Granja[];
    action: string;
    method?: 'post' | 'put';
}>();

const emit = defineEmits<{
    success: [];
}>();

// Si solo hay una granja disponible, se asigna automáticamente sin mostrar
// el selector.
const granjaUnica = computed<Granja | null>(() =>
    props.granjas.length === 1 ? props.granjas[0] : null,
);

const granjaId = ref<string>(
    props.terreno?.granja_id
        ? String(props.terreno.granja_id)
        : granjaUnica.value
          ? String(granjaUnica.value.id)
          : '',
);

const coordenadas = ref<Coordenada[]>(props.terreno?.coordenadas ?? []);
const area = ref<number | null>(props.terreno?.area ?? null);

// Serializa las coordenadas para enviarlas como campo oculto
const coordenadasJson = computed(() => JSON.stringify(coordenadas.value));
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
        <input type="hidden" name="coordenadas" :value="coordenadasJson" />
        <input type="hidden" name="area" :value="area ?? ''" />

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
            <p v-else class="text-sm text-muted-foreground">
                No tienes ninguna granja disponible.
            </p>
            <InputError :message="errors.granja_id" />
        </div>

        <div class="grid gap-2">
            <Label for="nombre">Nombre</Label>
            <Input
                id="nombre"
                name="nombre"
                :default-value="terreno?.nombre"
                required
            />
            <InputError :message="errors.nombre" />
        </div>

        <!-- Mapa interactivo para trazar el terreno -->
        <TerrenoMapa
            v-model="coordenadas"
            @update:area="(val) => (area = val)"
        />
        <InputError :message="errors.coordenadas" />

        <!-- Área calculada automáticamente desde el mapa; editable si se desea ajustar -->
        <div class="grid gap-2">
            <Label for="area">Área (hectáreas)</Label>
            <Input
                id="area"
                type="number"
                step="0.01"
                :model-value="area ?? undefined"
                @update:model-value="(v) => (area = v ? Number(v) : null)"
            />
            <p class="text-xs text-muted-foreground">
                Se calcula automáticamente al dibujar el polígono. Puedes
                ajustarla manualmente.
            </p>
            <InputError :message="errors.area" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Guardar</Button>
            <Button as-child variant="outline" type="button">
                <Link href="/terrenos">Cancelar</Link>
            </Button>
        </div>
    </Form>
</template>
