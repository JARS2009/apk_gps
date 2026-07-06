<script setup lang="ts">
import { Form, usePage } from '@inertiajs/vue3';
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
import type { Usuario } from '@/types/models/user';

const props = defineProps<{
    usuario?: Usuario;
    granjas: Granja[];
    action: string;
    method?: 'post' | 'put';
}>();

const emit = defineEmits<{
    success: [];
}>();

const page = usePage();
const actorEsSuperAdmin = computed(
    () => page.props.auth.user.role === 'super_admin',
);

const role = ref<string>(props.usuario?.role ?? 'admin');

// Si solo hay una granja disponible, se asigna automáticamente sin mostrar
// el selector.
const granjaUnica = computed<Granja | null>(() =>
    props.granjas.length === 1 ? props.granjas[0] : null,
);

const granjaIdsSeleccionadas = ref<number[]>(
    props.usuario?.granjas?.map((g) => g.id) ??
        (granjaUnica.value ? [granjaUnica.value.id] : []),
);

function toggleGranja(id: number, checked: boolean): void {
    if (checked) {
        if (!granjaIdsSeleccionadas.value.includes(id)) {
            granjaIdsSeleccionadas.value.push(id);
        }
    } else {
        granjaIdsSeleccionadas.value = granjaIdsSeleccionadas.value.filter(
            (g) => g !== id,
        );
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
        <div class="grid gap-2">
            <Label for="name">Nombre</Label>
            <Input
                id="name"
                name="name"
                :default-value="usuario?.name"
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="email">Correo electrónico</Label>
            <Input
                id="email"
                name="email"
                type="email"
                :default-value="usuario?.email"
                required
            />
            <InputError :message="errors.email" />
        </div>

        <div class="grid gap-2">
            <Label for="num_doc">Número de documento</Label>
            <Input
                id="num_doc"
                name="num_doc"
                :default-value="usuario?.num_doc ?? undefined"
                placeholder="Opcional"
            />
            <InputError :message="errors.num_doc" />
        </div>

        <div class="grid gap-2">
            <Label for="password">Contraseña</Label>
            <Input
                id="password"
                name="password"
                type="password"
                placeholder="Dejar en blanco para no cambiar"
            />
            <InputError :message="errors.password" />
        </div>

        <div v-if="actorEsSuperAdmin" class="grid gap-2">
            <Label for="role">Rol</Label>
            <input type="hidden" name="role" :value="role" />
            <Select v-model="role">
                <SelectTrigger id="role" class="w-full">
                    <SelectValue placeholder="Selecciona un rol" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="admin">Administrador</SelectItem>
                    <SelectItem value="super_admin"
                        >Super Administrador</SelectItem
                    >
                </SelectContent>
            </Select>
            <InputError :message="errors.role" />
        </div>
        <input v-else type="hidden" name="role" value="admin" />

        <div v-if="granjas.length > 0" class="grid gap-2">
            <Label v-if="granjas.length > 1">Granjas asignadas</Label>
            <input
                v-for="id in granjaIdsSeleccionadas"
                :key="id"
                type="hidden"
                name="granja_ids[]"
                :value="id"
            />
            <div
                v-if="granjas.length > 1"
                class="space-y-2 rounded-md border p-3"
            >
                <label
                    v-for="granja in granjas"
                    :key="granja.id"
                    class="flex items-center gap-2 text-sm"
                >
                    <input
                        type="checkbox"
                        class="size-4 rounded border-input"
                        :checked="granjaIdsSeleccionadas.includes(granja.id)"
                        @change="
                            toggleGranja(
                                granja.id,
                                ($event.target as HTMLInputElement).checked,
                            )
                        "
                    />
                    {{ granja.nombre }}
                </label>
            </div>
            <p v-else-if="granjaUnica" class="text-sm text-muted-foreground">
                Se asignará automáticamente a la granja:
                <span class="font-medium text-foreground">{{
                    granjaUnica.nombre
                }}</span>
            </p>
            <InputError :message="errors.granja_ids" />
        </div>

        <div class="flex items-center gap-4">
            <Button type="submit" :disabled="processing">Guardar</Button>
        </div>
    </Form>
</template>
