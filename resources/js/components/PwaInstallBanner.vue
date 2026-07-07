<script setup lang="ts">
/**
 * PwaInstallBanner.vue
 *
 * Shows an install prompt at the bottom of the screen when:
 * 1. The browser supports PWA installation (Android Chrome, Edge, etc.)
 * 2. The app is NOT already running in standalone mode
 *
 * Also handles iOS Safari instructions (no beforeinstallprompt support).
 */
import { ref, onMounted, computed } from 'vue';

const isVisible = ref(false);
const isIos = ref(false);
const deferredPrompt = ref<any>(null);
const isInstalled = ref(false);

onMounted(() => {
    // Already installed as PWA → don't show
    if (window.matchMedia('(display-mode: standalone)').matches) {
        isInstalled.value = true;
        return;
    }

    // Detect iOS Safari (no beforeinstallprompt → show manual instructions)
    const ua = navigator.userAgent.toLowerCase();
    isIos.value = /iphone|ipad|ipod/.test(ua) && !(window as any).MSStream;

    if (isIos.value) {
        // Show on iOS only if not in standalone mode
        const dismissed = sessionStorage.getItem('pwa-banner-dismissed');
        if (!dismissed) isVisible.value = true;
        return;
    }

    // Android / Chrome / Edge – capture beforeinstallprompt
    window.addEventListener('beforeinstallprompt', (e: Event) => {
        e.preventDefault();
        deferredPrompt.value = e;
        const dismissed = sessionStorage.getItem('pwa-banner-dismissed');
        if (!dismissed) isVisible.value = true;
    });

    // Detect successful installation
    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        isVisible.value = false;
    });
});

async function install() {
    if (!deferredPrompt.value) return;
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
        isVisible.value = false;
    }
    deferredPrompt.value = null;
}

function dismiss() {
    sessionStorage.setItem('pwa-banner-dismissed', '1');
    isVisible.value = false;
}
</script>

<template>
    <Transition
        enter-active-class="transition-all duration-500 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-300 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="isVisible && !isInstalled"
            class="fixed bottom-0 left-0 right-0 z-50 safe-bottom"
        >
            <!-- Main Banner -->
            <div class="mx-3 mb-3 rounded-2xl overflow-hidden shadow-2xl border border-white/10"
                 style="background: linear-gradient(135deg, #15521e 0%, #19a029 50%, #3cd74e 100%);">
                
                <!-- iOS instructions -->
                <div v-if="isIos" class="p-4">
                    <div class="flex items-start gap-3">
                        <img src="/logo.svg" alt="Agro-Rastreo" class="w-12 h-12 rounded-xl flex-shrink-0 shadow-md bg-white p-1" />
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-semibold text-sm">Instalar Agro-Rastreo</p>
                            <p class="text-green-100 text-xs mt-0.5 leading-relaxed">
                                Toca 
                                <span class="inline-flex items-center gap-0.5 bg-white/20 rounded px-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a.75.75 0 01.75.75v8.69l2.47-2.47a.75.75 0 111.06 1.06l-3.75 3.75a.75.75 0 01-1.06 0L5.72 10.03a.75.75 0 111.06-1.06L9.25 11.44V2.75A.75.75 0 0110 2z"/>
                                        <path d="M3 15.75A2.75 2.75 0 015.75 13h8.5A2.75 2.75 0 0117 15.75v1A2.75 2.75 0 0114.25 19.5h-8.5A2.75 2.75 0 013 16.75v-1z"/>
                                    </svg>
                                    Compartir
                                </span> 
                                y luego <strong class="text-white">"Agregar a pantalla de inicio"</strong>
                            </p>
                        </div>
                        <button @click="dismiss" class="text-white/70 hover:text-white p-1 rounded-full hover:bg-white/10 transition-colors flex-shrink-0">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Android / Desktop install -->
                <div v-else class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-white rounded-xl p-1.5 flex-shrink-0 shadow-md">
                            <img src="/logo.svg" alt="Agro-Rastreo" class="w-10 h-10" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-bold text-sm">Instalar Agro-Rastreo</p>
                            <p class="text-green-100 text-xs">GPS · Alertas · Acceso sin internet</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button
                                @click="dismiss"
                                class="text-white/60 hover:text-white text-xs px-2 py-1 rounded-lg hover:bg-white/10 transition-colors"
                            >
                                Ahora no
                            </button>
                            <button
                                @click="install"
                                class="bg-white text-green-800 font-bold text-sm px-4 py-2 rounded-xl shadow-md hover:bg-green-50 active:scale-95 transition-all"
                            >
                                Instalar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>
