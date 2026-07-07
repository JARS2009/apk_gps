import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';
import { fileURLToPath } from 'node:url';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    resolve: {
        alias: {
            // mapbox-gl-draw busca 'mapbox-gl' internamente; lo redirigimos a maplibre-gl
            'mapbox-gl': fileURLToPath(new URL('./node_modules/maplibre-gl/dist/maplibre-gl.js', import.meta.url)),
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        VitePWA({
            strategies: 'generateSW',
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            manifest: false, // served dynamically by Laravel
            devOptions: {
                enabled: false,
            },
            workbox: {
                globPatterns: ['**/*.{js,css,html,ico,png,svg,woff,woff2}'],
                globDirectory: 'public/build',
                swDest: 'public/sw.js',
                runtimeCaching: [
                    {
                        urlPattern: /^https?.*/,
                        handler: 'NetworkFirst',
                        options: {
                            cacheName: 'agro-cache',
                            expiration: { maxEntries: 100, maxAgeSeconds: 86400 },
                            networkTimeoutSeconds: 10,
                        },
                    },
                ],
                // Allow push notification event handling in generated SW
                additionalManifestEntries: [],
            },
        }),

    ],
});
