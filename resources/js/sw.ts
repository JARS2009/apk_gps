// Agro-Rastreo Service Worker
// Handles: PWA caching, push notifications, background sync

import { precacheAndRoute, cleanupOutdatedCaches } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { NetworkFirst, CacheFirst } from 'workbox-strategies';
import { ExpirationPlugin } from 'workbox-expiration';
import { CacheableResponsePlugin } from 'workbox-cacheable-response';

declare let self: ServiceWorkerGlobalScope;

cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

// Cache first: static assets (JS, CSS, fonts, images)
registerRoute(
    ({ request }) =>
        request.destination === 'script' ||
        request.destination === 'style' ||
        request.destination === 'font' ||
        request.destination === 'image',
    new CacheFirst({
        cacheName: 'agro-static-assets',
        plugins: [
            new ExpirationPlugin({ maxEntries: 100, maxAgeSeconds: 60 * 60 * 24 * 30 }),
            new CacheableResponsePlugin({ statuses: [0, 200] }),
        ],
    })
);

// Network first: HTML pages
registerRoute(
    ({ request }) => request.mode === 'navigate',
    new NetworkFirst({
        cacheName: 'agro-pages',
        plugins: [
            new ExpirationPlugin({ maxEntries: 30, maxAgeSeconds: 60 * 60 * 24 }),
            new CacheableResponsePlugin({ statuses: [200] }),
        ],
        networkTimeoutSeconds: 10,
    })
);

// =====================================================
// Push Notification Handler
// =====================================================
self.addEventListener('push', (event: PushEvent) => {
    if (!event.data) return;

    let payload: { title?: string; body?: string; icon?: string; url?: string; tag?: string };
    try {
        payload = event.data.json();
    } catch {
        payload = { title: 'Agro-Rastreo', body: event.data.text() };
    }

    const title = payload.title ?? 'Agro-Rastreo';
    const options: NotificationOptions = {
        body: payload.body ?? '',
        icon: '/icons/icon-192x192.png',
        badge: '/icons/icon-96x96.png',
        vibrate: [200, 100, 200],
        tag: payload.tag ?? 'agro-notification',
        data: { url: payload.url ?? '/' },
        requireInteraction: false,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Notification click → open the app at the correct URL
self.addEventListener('notificationclick', (event: NotificationEvent) => {
    event.notification.close();
    const url = event.notification.data?.url ?? '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                for (const client of clientList) {
                    if ('focus' in client) {
                        (client as WindowClient).navigate(url);
                        return client.focus();
                    }
                }
                return self.clients.openWindow(url);
            })
    );
});

// Skip waiting on message
self.addEventListener('message', (event: ExtendableMessageEvent) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
