<?php

use App\Http\Controllers\Animal\AnimalController;
use App\Http\Controllers\Collar\CollarController;
use App\Http\Controllers\Compra\CompraController;
use App\Http\Controllers\Configuracion\ConfiguracionController;
use App\Http\Controllers\Granja\GranjaController;
use App\Http\Controllers\Terreno\TerrenoController;
use App\Http\Controllers\Usuario\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'granja.acceso'])->group(function () {
    Route::resource('granjas', GranjaController::class)
        ->except(['create', 'store', 'edit', 'update', 'destroy']);

    Route::middleware(['role:super_admin'])->group(function () {
        Route::resource('granjas', GranjaController::class)
            ->only(['store', 'update', 'destroy']);
    });

    Route::get('granjas/{granja}/configuracion', [GranjaController::class, 'configuracion'])
        ->name('granjas.configuracion.edit');
    Route::put('granjas/{granja}/configuracion', [ConfiguracionController::class, 'update'])
        ->name('granjas.configuracion.update');

    Route::get('terrenos/{terreno}/animales', [TerrenoController::class, 'animales'])
        ->name('terrenos.animales');

    Route::resource('terrenos', TerrenoController::class);
    Route::resource('animales', AnimalController::class)->except(['create', 'edit'])->parameters(['animales' => 'animal']);
    Route::resource('collares', CollarController::class)->except(['create', 'edit'])->parameters(['collares' => 'collar']);
    Route::patch('collares/{collar}/asignar', [CollarController::class, 'asignar'])->name('collares.asignar');

    Route::resource('usuarios', UserController::class)->except(['show', 'create', 'edit']);

    Route::resource('compras', CompraController::class)->except(['create', 'edit', 'show'])->parameters(['compras' => 'compra']);
    Route::post('compras/{compra}/documentos', [CompraController::class, 'agregarDocumento'])->name('compras.documentos.store');
    Route::delete('compras/{compra}/documentos/{documento}', [CompraController::class, 'eliminarDocumento'])->name('compras.documentos.destroy');
});
