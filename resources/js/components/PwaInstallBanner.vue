<script setup lang="ts">
/**
 * PwaInstallBanner.vue
 *
 * Muestra un banner de instalación en dispositivos móviles.
 * Estrategia dual:
 *  1. Android Chrome con HTTPS válido: usa beforeinstallprompt
 *  2. Fallback: muestra instrucciones manuales para todos los móviles
 */
import { ref, onMounted, computed } from 'vue';

const isVisible = ref(false);
const isIos = ref(false);
const isAndroid = ref(false);
const deferredPrompt = ref<any>(null);
const isInstalled = ref(false);
const canNativeInstall = ref(false);

onMounted(() => {
    // Si ya está instalada como PWA -> no mostrar
    if (window.matchMedia('(display-mode: standalone)').matches ||
        (window.navigator as any).standalone === true) {
        isInstalled.value = true;
        return;
    }

    const ua = navigator.userAgent.toLowerCase();
    isIos.value = /iphone|ipad|ipod/.test(ua) && !(window as any).MSStream;
    isAndroid.value = /android/.test(ua);

    // Solo mostrar en dispositivos móviles
    const isMobile = isIos.value || isAndroid.value;
    if (!isMobile) return;

    // Verificar si ya fue descartado en esta sesión
    const dismissed = sessionStorage.getItem('pwa-banner-dismissed');
    if (dismissed) return;

    // Capturar beforeinstallprompt si el navegador lo soporta
    window.addEventListener('beforeinstallprompt', (e: Event) => {
        e.preventDefault();
        deferredPrompt.value = e;
        canNativeInstall.value = true;
        isVisible.value = true;
    });

    // Detectar instalación exitosa
    window.addEventListener('appinstalled', () => {
        isInstalled.value = true;
        isVisible.value = false;
    });

    // Fallback: mostrar instrucciones manuales en móvil aunque no haya beforeinstallprompt
    // (cubre HTTPS autofirmado, Safari iOS, navegadores alternativos)
    setTimeout(() => {
        if (!isInstalled.value && !isVisible.value) {
            isVisible.value = true;
        }
    }, 3000);
});

async function install() {
    if (deferredPrompt.value) {
        // Instalación nativa via Chrome
        deferredPrompt.value.prompt();
        const { outcome } = await deferredPrompt.value.userChoice;
        if (outcome === 'accepted') {
            isVisible.value = false;
        }
        deferredPrompt.value = null;
    }
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
            class="fixed bottom-20 left-0 right-0 z-50 px-3 md:hidden"
        >
            <div class="rounded-2xl overflow-hidden shadow-2xl border border-white/10"
                 style="background: linear-gradient(135deg, #15521e 0%, #19a029 60%, #3cd74e 100%);">

                <!-- Instalacion nativa (Chrome Android con HTTPS valido) -->
                <div v-if="canNativeInstall" class="p-4">
                    <div class="flex items-center gap-3">
                        <div class="bg-white rounded-xl p-1.5 flex-shrink-0 shadow-md">
                            <img src="/logo.svg" alt="Agro-Rastreo" class="w-10 h-10" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-bold text-sm">Instalar Agro-Rastreo</p>
                            <p class="text-green-100 text-xs">GPS · Alertas · Sin internet</p>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            <button @click="dismiss"
                                class="text-white/60 hover:text-white text-xs px-2 py-1 rounded-lg hover:bg-white/10 transition-colors">
                                No
                            </button>
                            <button @click="install"
                                class="bg-white text-green-800 font-bold text-sm px-4 py-2 rounded-xl shadow-md hover:bg-green-50 active:scale-95 transition-all">
                                Instalar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- iOS Safari: instrucciones manuales -->
                <div v-else-if="isIos" class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="bg-white rounded-xl p-1.5 flex-shrink-0 shadow-md">
                            <img src="/logo.svg" alt="Agro-Rastreo" class="w-9 h-9" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-bold text-sm mb-1">Instalar Agro-Rastreo</p>
                            <p class="text-green-100 text-xs leading-relaxed">
                                Toca
                                <span class="inline-flex items-center bg-white/20 rounded px-1 py-0.5 mx-0.5">
                                    <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a.75.75 0 01.75.75v8.69l2.47-2.47a.75.75 0 111.06 1.06l-3.75 3.75a.75.75 0 01-1.06 0L5.72 10.03a.75.75 0 111.06-1.06L9.25 11.44V2.75A.75.75 0 0110 2z"/>
                                    </svg>
                                    Compartir
                                </span>
                                y luego <strong class="text-white">"Agregar a inicio"</strong>
                            </p>
                        </div>
                        <button @click="dismiss"
                            class="text-white/60 hover:text-white p-1 flex-shrink-0 rounded-full hover:bg-white/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Android: instrucciones manuales (Chrome sin beforeinstallprompt / cert autofirmado) -->
                <div v-else class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="bg-white rounded-xl p-1.5 flex-shrink-0 shadow-md">
                            <img src="/logo.svg" alt="Agro-Rastreo" class="w-9 h-9" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-white font-bold text-sm mb-1">Instalar Agro-Rastreo</p>
                            <p class="text-green-100 text-xs leading-relaxed">
                                Toca el menú
                                <span class="inline-flex items-center bg-white/20 rounded px-1 py-0.5 mx-0.5 font-bold">⋮</span>
                                de Chrome y selecciona
                                <strong class="text-white">"Añadir a pantalla de inicio"</strong>
                            </p>
                        </div>
                        <button @click="dismiss"
                            class="text-white/60 hover:text-white p-1 flex-shrink-0 rounded-full hover:bg-white/10 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </Transition>
</template>
