<?php

namespace App\Observers;

use App\Models\Collar;
use App\Models\UbicacionPrueba;
use App\Services\Tracking\TrackingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class UbicacionPruebaObserver
{
    /**
     * Cada vez que se crea un registro en ubicacion_prueba,
     * sincroniza automáticamente a collar_locations si hay un collar con ese IMEI.
     */
    public function created(UbicacionPrueba $ubicacion): void
    {
        $trackingService = app(TrackingService::class);
        // Ignorar eventos sin coordenadas
        if (in_array($ubicacion->evento, ['login', 'heartbeat'], true)) {
            $ubicacion->updateQuietly(['synced_at' => Carbon::now()]);

            return;
        }

        // Ignorar coordenadas inválidas (0,0)
        if (abs((float) $ubicacion->latitud) < 0.001 && abs((float) $ubicacion->longitud) < 0.001) {
            $ubicacion->updateQuietly(['synced_at' => Carbon::now()]);

            return;
        }

        if (! $ubicacion->imei) {
            return;
        }

        $collar = Collar::where('imei', $ubicacion->imei)->with('animal')->first();

        if (! $collar) {
            $ubicacion->updateQuietly(['synced_at' => Carbon::now()]);

            return;
        }

        try {
            $fechaMutable = $ubicacion->fecha_gps ? \Illuminate\Support\Carbon::parse($ubicacion->fecha_gps) : null;
            $trackingService->registrarUbicacion(
                $collar,
                (float) $ubicacion->latitud,
                (float) $ubicacion->longitud,
                $fechaMutable,
            );

            $ubicacion->updateQuietly(['synced_at' => Carbon::now()]);
        } catch (\Throwable $e) {
            Log::error("[SyncGps Observer] Error sincronizando #{$ubicacion->id}: {$e->getMessage()}");
        }
    }
}
