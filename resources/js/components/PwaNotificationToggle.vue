<script setup lang="ts">
/**
 * PwaNotificationToggle.vue
 * Button to enable/disable push notifications — used in the app header/sidebar
 */
import { usePushNotifications } from '@/composables/usePwa';
import { Bell, BellOff } from '@lucide/vue';

const { isSupported, isSubscribed, isLoading, permissionState, subscribe, unsubscribe } = usePushNotifications();

function toggle() {
    if (isSubscribed.value) {
        unsubscribe();
    } else {
        subscribe();
    }
}
</script>

<template>
    <button
        v-if="isSupported && permissionState !== 'denied'"
        @click="toggle"
        :disabled="isLoading"
        :title="isSubscribed ? 'Desactivar alertas push' : 'Activar alertas push'"
        class="relative inline-flex items-center justify-center w-9 h-9 rounded-lg transition-all duration-200
               hover:bg-primary/10 active:scale-95 disabled:opacity-50 disabled:cursor-wait"
        :class="isSubscribed ? 'text-primary' : 'text-muted-foreground'"
    >
        <!-- Loading spinner -->
        <span v-if="isLoading" class="absolute inset-0 flex items-center justify-center">
            <svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
        </span>
        <Bell v-else-if="isSubscribed" class="w-5 h-5" />
        <BellOff v-else class="w-5 h-5" />

        <!-- Active indicator dot -->
        <span
            v-if="isSubscribed && !isLoading"
            class="absolute top-1 right-1 w-2 h-2 bg-primary rounded-full ring-1 ring-background"
        />
    </button>
</template>
