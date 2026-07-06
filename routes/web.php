<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Tracking\TrackingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

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
