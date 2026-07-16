<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Mail, Phone, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
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
import type { ContactoTipo, ContactoUsuario } from '@/types/models/user';

const props = defineProps<{
    contactos: ContactoUsuario[];
    storeUrl: string;
    destroyUrl: (id: number) => string;
}>();

const form = useForm({
    tipo: 'correo' as ContactoTipo,
    valor: '',
});

const deleting = ref<number | null>(null);

function agregar(): void {
    form.post(props.storeUrl, {
        preserveScroll: true,
        onSuccess: () => form.reset('valor'),
    });
}

function eliminar(id: number): void {
    deleting.value = id;
    router.delete(props.destroyUrl(id), {
        preserveScroll: true,
        onFinish: () => (deleting.value = null),
    });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Lista de contactos existentes -->
        <div v-if="contactos.length > 0" class="space-y-2">
            <div
                v-for="contacto in contactos"
                :key="contacto.id"
                class="flex items-center gap-3 rounded-md border px-3 py-2"
            >
                <component
                    :is="contacto.tipo === 'correo' ? Mail : Phone"
                    class="size-4 shrink-0 text-muted-foreground"
                />
                <span class="flex-1 text-sm">{{ contacto.valor }}</span>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                    :class="
                        contacto.tipo === 'correo'
                            ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300'
                            : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                    "
                >
                    {{ contacto.tipo === 'correo' ? 'Correo' : 'Teléfono' }}
                </span>
                <Button
                    variant="ghost"
                    size="icon"
                    class="size-7 text-destructive hover:text-destructive"
                    :disabled="deleting === contacto.id"
                    @click="eliminar(contacto.id)"
                >
                    <Trash2 class="size-4" />
                </Button>
            </div>
        </div>

        <p v-else class="text-sm text-muted-foreground">
            No hay contactos registrados.
        </p>

        <!-- Formulario para agregar -->
        <form class="flex flex-col gap-3 sm:flex-row sm:items-end" @submit.prevent="agregar">
            <div class="grid gap-1.5 flex-1">
                <Label>Tipo</Label>
                <Select v-model="form.tipo">
                    <SelectTrigger class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="correo">Correo</SelectItem>
                        <SelectItem value="telefono">Teléfono</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.tipo" />
            </div>

            <div class="grid gap-1.5 flex-1">
                <Label>{{ form.tipo === 'correo' ? 'Dirección de correo' : 'Número de teléfono' }}</Label>
                <Input
                    v-model="form.valor"
                    :type="form.tipo === 'correo' ? 'email' : 'tel'"
                    :placeholder="form.tipo === 'correo' ? 'ejemplo@correo.com' : '+57 300 000 0000'"
                    required
                />
                <InputError :message="form.errors.valor" />
            </div>

            <Button type="submit" :disabled="form.processing" class="shrink-0">
                Agregar
            </Button>
        </form>
    </div>
</template>
