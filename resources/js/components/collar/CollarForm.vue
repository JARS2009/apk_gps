<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
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
import type { Animal } from '@/types/models/animal';
import type { Collar } from '@/types/models/collar';

const props = defineProps<{
    collar?: Collar;
    animales: Animal[];
    action: string;
    method?: 'post' | 'put';
}>();

const emit = defineEmits<{
    success: [];
}>();

const SIN_ASIGNAR = 'none';

// El animal actualmente asignado a este collar puede no venir en la lista de
// "disponibles" (porque ya está ocupado por este mismo collar), así que se
// agrega manualmente a las opciones para no perderlo del selector al editar.
const opcionesAnimales = computed<Animal[]>(() => {
    const lista = [...props.animales];

    if (
        props.collar?.animal &&
        !lista.some((a) => a.id === props.collar!.animal!.id)
    ) {
        lista.unshift(props.collar.animal);
    }

    return lista;
});

const form = useForm<{
    serie: string;
    modelo: string;
    animal_id: number | null;
    estado: string;
}>({
    serie: props.collar?.serie ?? '',
    modelo: props.collar?.modelo ?? '',
    animal_id: props.collar?.animal_id ?? null,
    estado: props.collar?.estado ?? 'disponible',
});

const animalId = ref<string>(
    props.collar?.animal_id ? String(props.collar.animal_id) : SIN_ASIGNAR,
);

const estado = ref<string>(props.collar?.estado ?? 'disponible');

function submit(): void {
    form.animal_id = animalId.value === SIN_ASIGNAR ? null : Number(animalId.value);
    form.estado = animalId.value === SIN_ASIGNAR ? estado.value : 'asignado';

    const method = props.method ?? 'post';

    form.submit(method, props.action, {
        onSuccess: () => emit('success'),
    });
}
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="serie">Serie</Label>
            <Input
                id="serie"
                v-model="form.serie"
                required
            />
            <InputError :message="form.errors.serie" />
        </div>

        <div class="grid gap-2">
            <Label for="modelo">Modelo</Label>
            <Input
                id="modelo"
                v-model="form.modelo"
                required
            />
            <InputError :message="form.errors.modelo" />
        </div>

        <div class="grid gap-2">
            <Label for="animal_id">Animal asignado</Label>
            <Select v-model="animalId">
                <SelectTrigger id="animal_id" class="w-full">
                    <SelectValue placeholder="Selecciona un animal" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="SIN_ASIGNAR">Sin asignar</SelectItem>
                    <SelectItem
                        v-for="animal in opcionesAnimales"
                        :key="animal.id"
                        :value="String(animal.id)"
                    >
                        {{ animal.codigo }} — {{ animal.nombre }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.animal_id" />
        </div>

        <div v-if="animalId === SIN_ASIGNAR" class="grid gap-2">
            <Label for="estado">Estado</Label>
            <Select v-model="estado">
                <SelectTrigger id="estado" class="w-full">
                    <SelectValue placeholder="Selecciona un estado" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="disponible">Disponible</SelectItem>
                    <SelectItem value="inactivo">Inactivo</SelectItem>
                </SelectContent>
            </Select>
            <InputError :message="form.errors.estado" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="form.processing">Guardar</Button>
        </div>
    </form>
</template>
