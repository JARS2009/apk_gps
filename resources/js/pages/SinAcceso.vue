<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { logout } from '@/routes';
import { Button } from '@/components/ui/button';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Sin acceso', href: '/sin-acceso' }],
    },
});

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <Head title="Sin acceso" />

    <div
        class="flex flex-1 flex-col items-center justify-center gap-4 p-8 text-center"
    >
        <h1 class="text-2xl font-semibold">
            No tienes ninguna granja asignada
        </h1>
        <p class="max-w-md text-muted-foreground">
            No tienes ninguna granja asignada. Contacta a un administrador para
            que te asigne una granja y puedas acceder al sistema.
        </p>
        <Button as-child>
            <Link :href="logout()" as="button" @click="handleLogout">
                Cerrar sesión
            </Link>
        </Button>
    </div>
</template>
