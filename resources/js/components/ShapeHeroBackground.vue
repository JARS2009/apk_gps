<script setup lang="ts">
interface Props {
    title1?: string;
    title2?: string;
}

withDefaults(defineProps<Props>(), {
    title1: 'Sistema',
    title2: 'Inteligente',
});
</script>

<template>
    <div
        class="pointer-events-none absolute inset-0 z-0 overflow-hidden bg-white dark:bg-[#030303]"
    >
        <div
            class="absolute inset-0 bg-linear-to-br from-indigo-500/[0.02] via-transparent to-rose-500/[0.02] blur-3xl dark:from-indigo-500/[0.05] dark:via-transparent dark:to-rose-500/[0.05]"
        />

        <div class="absolute inset-0 z-0 overflow-hidden">
            <!-- Tall rectangle - top left -->
            <ElegantShape
                :border-radius="24"
                class-name="top-[-10%] left-[-15%]"
                :delay="0.3"
                gradient="from-indigo-500/[0.25] dark:from-indigo-500/[0.35]"
                :height="500"
                :rotate="-8"
                :width="300"
            />

            <!-- Wide rectangle - bottom right -->
            <ElegantShape
                :border-radius="20"
                class-name="right-[-20%] bottom-[-5%]"
                :delay="0.5"
                gradient="from-rose-500/[0.25] dark:from-rose-500/[0.35]"
                :height="200"
                :rotate="15"
                :width="600"
            />

            <!-- Square - middle left -->
            <ElegantShape
                :border-radius="32"
                class-name="top-[40%] left-[-5%]"
                :delay="0.4"
                gradient="from-violet-500/[0.25] dark:from-violet-500/[0.35]"
                :height="300"
                :rotate="24"
                :width="300"
            />

            <!-- Small rectangle - top right -->
            <ElegantShape
                :border-radius="12"
                class-name="top-[5%] right-[10%]"
                :delay="0.6"
                gradient="from-amber-500/[0.25] dark:from-amber-500/[0.35]"
                :height="100"
                :rotate="-20"
                :width="250"
            />

            <!-- Medium rectangle - center right -->
            <ElegantShape
                :border-radius="16"
                class-name="top-[45%] right-[-10%]"
                :delay="0.7"
                gradient="from-emerald-500/[0.25] dark:from-emerald-500/[0.35]"
                :height="150"
                :rotate="35"
                :width="400"
            />

            <!-- Small square - bottom left -->
            <ElegantShape
                :border-radius="28"
                class-name="bottom-[10%] left-[20%]"
                :delay="0.2"
                gradient="from-blue-500/[0.25] dark:from-blue-500/[0.35]"
                :height="200"
                :rotate="-25"
                :width="200"
            />

            <!-- Tiny rectangle - top center -->
            <ElegantShape
                :border-radius="10"
                class-name="top-[15%] left-[40%]"
                :delay="0.8"
                gradient="from-purple-500/[0.25] dark:from-purple-500/[0.35]"
                :height="80"
                :rotate="45"
                :width="150"
            />

            <!-- Wide rectangle - middle -->
            <ElegantShape
                :border-radius="18"
                class-name="top-[60%] left-[25%]"
                :delay="0.9"
                gradient="from-teal-500/[0.25] dark:from-teal-500/[0.35]"
                :height="120"
                :rotate="-12"
                :width="450"
            />
        </div>

        <div
            class="pointer-events-none absolute inset-0 z-10 bg-linear-to-t from-white via-transparent to-white/80 dark:from-[#030303] dark:via-transparent dark:to-[#030303]/80"
        />
    </div>
</template>

<script lang="ts">
import ElegantShape from './ElegantShape.vue';

export default {
    components: {
        ElegantShape,
    },
};
</script>
