<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

import ShapeHeroBackground from '@/components/ShapeHeroBackground.vue';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center p-6 md:p-10"
    >
        <!-- Background animado interactivo -->
        <ShapeHeroBackground />

        <!-- Tarjeta "Glassmorphism" con el contenido del Auth -->
        <div class="relative z-20 w-full max-w-sm">
            <div
                class="flex flex-col gap-6 rounded-3xl bg-white/60 p-8 shadow-[0_8px_40px_-12px_rgba(0,0,0,0.2)] ring-1 ring-white/50 backdrop-blur-2xl transition-all dark:bg-black/40 dark:ring-white/10"
            >
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
