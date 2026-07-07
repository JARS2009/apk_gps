// composable: usePushNotifications.ts
// Handles Web Push API subscription, GPS permission, and PWA install prompt

import { ref, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';

// ─────────────────────────────────────────────────
// Push Notifications
// ─────────────────────────────────────────────────
const VAPID_PUBLIC_KEY = import.meta.env.VITE_VAPID_PUBLIC_KEY as string;

function urlBase64ToUint8Array(base64String: string): Uint8Array {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}

export function usePushNotifications() {
    const isSupported = ref(false);
    const isSubscribed = ref(false);
    const isLoading = ref(false);
    const permissionState = ref<NotificationPermission>('default');

    onMounted(async () => {
        isSupported.value = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
        if (!isSupported.value) return;
        permissionState.value = Notification.permission;

        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        isSubscribed.value = !!sub;
    });

    async function subscribe() {
        if (!isSupported.value || !VAPID_PUBLIC_KEY) return;
        isLoading.value = true;
        try {
            const permission = await Notification.requestPermission();
            permissionState.value = permission;
            if (permission !== 'granted') return;

            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });

            await fetch('/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify(sub.toJSON()),
            });

            isSubscribed.value = true;
        } catch (err) {
            console.error('Push subscription error:', err);
        } finally {
            isLoading.value = false;
        }
    }

    async function unsubscribe() {
        isLoading.value = true;
        try {
            const reg = await navigator.serviceWorker.ready;
            const sub = await reg.pushManager.getSubscription();
            if (!sub) return;

            await fetch('/push/unsubscribe', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({ endpoint: sub.endpoint }),
            });

            await sub.unsubscribe();
            isSubscribed.value = false;
        } catch (err) {
            console.error('Unsubscribe error:', err);
        } finally {
            isLoading.value = false;
        }
    }

    function getCsrfToken(): string {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    return { isSupported, isSubscribed, isLoading, permissionState, subscribe, unsubscribe };
}

// ─────────────────────────────────────────────────
// GPS / Geolocation
// ─────────────────────────────────────────────────
export function useGeolocation() {
    const coords = ref<{ lat: number; lng: number; accuracy: number } | null>(null);
    const error = ref<string | null>(null);
    const isTracking = ref(false);
    let watchId: number | null = null;

    function startTracking() {
        if (!('geolocation' in navigator)) {
            error.value = 'Geolocalización no disponible en este dispositivo.';
            return;
        }
        isTracking.value = true;
        watchId = navigator.geolocation.watchPosition(
            (position) => {
                coords.value = {
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                    accuracy: position.coords.accuracy,
                };
                error.value = null;
            },
            (err) => {
                error.value = err.message;
                isTracking.value = false;
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
        );
    }

    function stopTracking() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }
        isTracking.value = false;
    }

    return { coords, error, isTracking, startTracking, stopTracking };
}
