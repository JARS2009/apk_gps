<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Tracking\TrackingController;
use App\Http\Controllers\PushNotificationController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

// PWA Manifest
Route::get('/manifest.json', function () {
    return response()->json([
        'name'             => config('app.name', 'Agro-Rastreo'),
        'short_name'       => 'AgroRastreo',
        'description'      => 'Sistema de rastreo GPS y monitoreo ganadero',
        'start_url'        => '/dashboard',
        'display'          => 'standalone',
        'orientation'      => 'portrait-primary',
        'background_color' => '#15521e',
        'theme_color'      => '#19a029',
        'lang'             => 'es',
        'icons'            => [
            ['src' => '/icons/icon-72x72.png',   'sizes' => '72x72',   'type' => 'image/png'],
            ['src' => '/icons/icon-96x96.png',   'sizes' => '96x96',   'type' => 'image/png'],
            ['src' => '/icons/icon-128x128.png', 'sizes' => '128x128', 'type' => 'image/png'],
            ['src' => '/icons/icon-144x144.png', 'sizes' => '144x144', 'type' => 'image/png'],
            ['src' => '/icons/icon-152x152.png', 'sizes' => '152x152', 'type' => 'image/png'],
            ['src' => '/icons/icon-192x192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any'],
            ['src' => '/icons/icon-384x384.png', 'sizes' => '384x384', 'type' => 'image/png'],
            ['src' => '/icons/icon-512x512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
        ],
        'categories' => ['business', 'productivity'],
        'screenshots' => [],
    ])->header('Content-Type', 'application/manifest+json');
})->name('pwa.manifest');

// Push Notification Routes (authenticated)
Route::middleware('auth')->group(function () {
    Route::post('/push/subscribe',   [PushNotificationController::class, 'subscribe'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushNotificationController::class, 'unsubscribe'])->name('push.unsubscribe');
    Route::post('/push/test',        [PushNotificationController::class, 'sendTest'])->name('push.test');
});

Route::inertia('sin-acceso', 'SinAcceso')
    ->name('sin-acceso')
    ->middleware(['auth']);

Route::middleware(['auth', 'verified', 'granja.acceso'])->group(function () {
    // Dashboard con mapa y alertas
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // API endpoints para polling y tracking (JSON)
    Route::prefix('api')->group(function () {
        Route::get('dashboard/datos', [DashboardController::class, 'datos'])->name('api.dashboard.datos');
        Route::post('dashboard/alertas/leidas', [DashboardController::class, 'marcarLeidas'])->name('api.dashboard.alertas.leidas');
        Route::post('tracking/ubicacion', [TrackingController::class, 'registrarUbicacion'])->name('api.tracking.ubicacion');
        Route::get('tracking/collares', [TrackingController::class, 'collaresActivos'])->name('api.tracking.collares');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/gps.php';
