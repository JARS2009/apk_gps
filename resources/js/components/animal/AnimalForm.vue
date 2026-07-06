<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
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
import type { Granja } from '@/types/models/granja';
import type { Terreno } from '@/types/models/terreno';

const props = defineProps<{
    animal?: Animal;
    granjas: Granja[];
    terrenos: Terreno[];
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
    props.animal?.granja_id
        ? String(props.animal.granja_id)
        : granjaUnica.value
          ? String(granjaUnica.value.id)
          : '',
);
const terrenoIds = ref<number[]>(
    props.animal?.terrenos?.map((t) => t.id) ?? [],
);

// Solo se pueden asignar terrenos que pertenezcan a la granja seleccionada.
const terrenosDeGranja = computed<Terreno[]>(() =>
    props.terrenos.filter((t) => String(t.granja_id) === granjaId.value),
);

// Si el usuario cambia de granja, se quitan de la selección los terrenos que
// ya no pertenezcan a la nueva granja.
watch(granjaId, () => {
    const idsValidos = new Set(terrenosDeGranja.value.map((t) => t.id));
    terrenoIds.value = terrenoIds.value.filter((id) => idsValidos.has(id));
});

function toggleTerreno(id: number, checked: boolean): void {
    if (checked) {
        if (!terrenoIds.value.includes(id)) {
            terrenoIds.value.push(id);
        }
    } else {
        terrenoIds.value = terrenoIds.value.filter((t) => t !== id);
    }
}
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
                :default-value="animal?.nombre"
                required
            />
            <InputError :message="errors.nombre" />
        </div>

        <div class="grid gap-2">
            <Label for="codigo">Código</Label>
            <Input
                id="codigo"
                name="codigo"
                :default-value="animal?.codigo"
                required
            />
            <InputError :message="errors.codigo" />
        </div>

        <div class="grid gap-2">
            <Label for="tipo">Tipo</Label>
            <Input
                id="tipo"
                name="tipo"
                :default-value="animal?.tipo ?? undefined"
                placeholder="Toro, vaca..."
            />
            <InputError :message="errors.tipo" />
        </div>

        <div class="grid gap-2">
            <Label for="raza">Raza</Label>
            <Input
                id="raza"
                name="raza"
                :default-value="animal?.raza ?? undefined"
            />
            <InputError :message="errors.raza" />
        </div>

        <div class="grid gap-2">
            <Label>Terrenos asignados</Label>
            <p class="text-sm text-muted-foreground">
                Un animal puede estar en varios terrenos a la vez (por ejemplo,
                su potrero y su establo).
            </p>
            <input
                v-for="id in terrenoIds"
                :key="id"
                type="hidden"
                name="terreno_ids[]"
                :value="id"
            />
            <div
                v-if="!granjaId"
                class="rounded-md border p-3 text-sm text-muted-foreground"
            >
                Selecciona primero una granja.
            </div>
            <div
                v-else-if="terrenosDeGranja.length === 0"
                class="rounded-md border p-3 text-sm text-muted-foreground"
            >
                Esta granja aún no tiene terrenos registrados.
            </div>
            <div v-else class="space-y-2 rounded-md border p-3">
                <label
                    v-for="terreno in terrenosDeGranja"
                    :key="terreno.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <input
                        type="checkbox"
                        class="size-4 rounded border-input"
                        :checked="terrenoIds.includes(terreno.id)"
                        @change="
                            toggleTerreno(
                                terreno.id,
                                ($event.target as HTMLInputElement).checked,
                            )
                        "
                    />
                    {{ terreno.nombre }}
                </label>
            </div>
            <InputError :message="errors.terreno_ids" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Guardar</Button>
        </div>
    </Form>
</template>
