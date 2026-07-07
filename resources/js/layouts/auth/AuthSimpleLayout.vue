<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="flex min-h-svh flex-col items-center justify-center bg-white p-6 md:p-10 dark:bg-gray-950"
    >
        <!-- Tarjeta con el contenido del Auth -->
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-6 rounded-3xl bg-white p-8 shadow-md ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-800">
                <div class="flex flex-col items-center gap-4">
                    <Link
                        href="/"
                        class="flex flex-col items-center gap-2 font-medium"
                    >
                        <div
                            class="mb-4 flex h-32 w-auto items-center justify-center"
                        >
                            <img
                                src="/logo.svg"
                                alt="Logo"
                                class="h-full w-auto object-contain drop-shadow-sm"
                            />
                        </div>
                        <span class="sr-only">{{ title }}</span>
                    </Link>
                    <div class="space-y-1 text-center">
                        <h1
                            class="text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100"
                        >
                            {{ title }}
                        </h1>
                        <p
                            class="text-center text-sm text-gray-600 dark:text-gray-400"
                        >
                            {{ description }}
                        </p>
                    </div>
                </div>

                <slot />
            </div>
        </div>
    </div>
</template>
