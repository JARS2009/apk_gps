<?php

namespace App\Providers;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class NativeAppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any NativePHP Mobile services.
     * Este provider se ejecuta cuando la app arranca en el dispositivo móvil.
     *
     * La APK se conecta al mismo MySQL del VPS (no usa SQLite local),
     * así los datos están sincronizados entre web y app.
     * Las credenciales de MySQL se configuran en el .env de la APK.
     */
    public function boot(): void
    {
        // La APK usa MySQL del VPS directamente (configurado en .env)
        // No se fuerza SQLite — web y app comparten la misma base de datos.

        // Ejecutar migraciones al arrancar (por si hay nuevas)
        $this->runMigrations();

        // Ejecutar seeders solo si la base de datos está vacía (primera instalación)
        $this->runSeedersIfNeeded();
    }

    /**
     * Ejecuta todas las migraciones pendientes.
     */
    protected function runMigrations(): void
    {
        try {
            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
        } catch (\Throwable $e) {
            // Silenciar errores de migración para no bloquear el arranque
            logger()->error('Migration error: ' . $e->getMessage());
        }
    }

    /**
     * Ejecuta los seeders solo en la primera instalación (cuando users está vacía).
     */
    protected function runSeedersIfNeeded(): void
    {
        try {
            // Siempre corre el seeder — usa firstOrCreate internamente, no duplica datos
            if (Schema::hasTable('users') && \Illuminate\Support\Facades\DB::table('users')->count() === 0) {
                Artisan::call('db:seed', [
                    '--class' => DatabaseSeeder::class,
                    '--force' => true,
                    '--no-interaction' => true,
                ]);
            }
        } catch (\Throwable $e) {
            logger()->error('Seeder error: ' . $e->getMessage());
        }
    }
}
