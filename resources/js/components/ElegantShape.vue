<script setup lang="ts">
import { computed } from 'vue';

import { cn } from '@/lib/utils';

interface Props {
    className?: string;
    delay?: number;
    width?: number;
    height?: number;
    rotate?: number;
    gradient?: string;
    borderRadius?: number;
}

const props = withDefaults(defineProps<Props>(), {
    delay: 0,
    width: 400,
    height: 100,
    rotate: 0,
    gradient: 'from-white/[0.08]',
    borderRadius: 16,
});

const wrapperStyle = computed(() => {
    return {
        '--delay': `${props.delay}s`,
        '--rotate-start': `${props.rotate - 15}deg`,
        '--rotate-end': `${props.rotate}deg`,
    };
});
</script>

<template>
    <div
        :class="cn('fade-in-down absolute z-0', className)"
        :style="wrapperStyle"
    >
        <div
            class="float-animation relative"
            :style="{ width: `${width}px`, height: `${height}px` }"
        >
            <div
                :class="
                    cn(
                        'absolute inset-0',
                        'bg-linear-to-r to-transparent',
                        gradient,
                        'backdrop-blur-[1px]',
                        'ring-1 ring-white/[0.03] dark:ring-white/[0.02]',
                        'shadow-[0_2px_16px_-2px_rgba(255,255,255,0.04)]',
                        'after:absolute after:inset-0',
                        'after:bg-[radial-gradient(circle_at_50%_50%,rgba(255,255,255,0.12),transparent_70%)]',
                        'after:rounded-[inherit]',
                    )
                "
                :style="{ borderRadius: `${borderRadius}px` }"
            />
        </div>
    </div>
</template>

<style scoped>
@keyframes fadeInDownShape {
    0% {
        opacity: 0;
        transform: translateY(-150px) rotate(var(--rotate-start));
    }
    100% {
        opacity: 1;
        transform: translateY(0) rotate(var(--rotate-end));
    }
}

.fade-in-down {
    opacity: 0;
    animation: fadeInDownShape 2.4s cubic-bezier(0.23, 1, 0.32, 1) forwards;
    animation-delay: var(--delay);
}

@keyframes floatShape {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(15px);
    }
}

.float-animation {
    animation: floatShape 12s ease-in-out infinite;
}
</style>
