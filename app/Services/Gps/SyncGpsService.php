<?php

namespace App\Services\Gps;

use App\Models\Collar;
use App\Models\UbicacionPrueba;
use App\Services\Tracking\TrackingService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class SyncGpsService
{
    public function __construct(
        private readonly TrackingService $trackingService,
    ) {}

    /**
     * Sincroniza registros pendientes de ubicacion_prueba a collar_locations.
     * Ignora eventos login y heartbeat. Acepta ubicacion, sin_fix, alarma, etc.
     *
     * @return array{sincronizados: int, sin_collar: int, ignorados: int}
     */
    public function sincronizar(int $limite = 500): array
    {
        $sincronizados = 0;
        $sinCollar = 0;
        $ignorados = 0;

        // Obtener collares con IMEI asignado (cacheado para el batch)
        $collaresPorImei = Collar::query()
            ->whereNotNull('imei')
            ->where('imei', '!=', '')
            ->with('animal')
            ->get()
            ->keyBy('imei');

        if ($collaresPorImei->isEmpty()) {
            Log::info('[SyncGps] No hay collares con IMEI configurado.');

            return ['sincronizados' => 0, 'sin_collar' => 0, 'ignorados' => 0];
        }

        // Obtener registros pendientes de sincronización
        $pendientes = UbicacionPrueba::query()
            ->whereNull('synced_at')
            ->whereNotNull('imei')
            ->orderBy('id')
            ->limit($limite)
            ->get();

        foreach ($pendientes as $registro) {
            // Ignorar eventos que no contienen coordenadas (login, heartbeat)
            if (in_array($registro->evento, ['login', 'heartbeat'], true)) {
                $registro->update(['synced_at' => Carbon::now()]);
                $ignorados++;

                continue;
            }

            // Ignorar coordenadas inválidas (0,0 o sin fix)
            if (abs((float) $registro->latitud) < 0.001 && abs((float) $registro->longitud) < 0.001) {
                $registro->update(['synced_at' => Carbon::now()]);
                $ignorados++;

                continue;
            }

            $collar = $collaresPorImei->get($registro->imei);

            if (! $collar) {
                $registro->update(['synced_at' => Carbon::now()]);
                $sinCollar++;

                continue;
            }

            // Registrar ubicación usando el TrackingService existente
            // (esto guarda en collar_locations Y verifica geofence/alertas)
            try {
                $fechaMutable = $registro->fecha_gps ? \Illuminate\Support\Carbon::parse($registro->fecha_gps) : null;
                $this->trackingService->registrarUbicacion(
                    $collar,
                    (float) $registro->latitud,
                    (float) $registro->longitud,
                    $fechaMutable,
                );

                $registro->update(['synced_at' => Carbon::now()]);
                $sincronizados++;
            } catch (\Throwable $e) {
                Log::error("[SyncGps] Error sincronizando registro #{$registro->id}: {$e->getMessage()}");
            }
        }

        if ($sincronizados > 0 || $sinCollar > 0) {
            Log::info("[SyncGps] Sync completado: {$sincronizados} sincronizados, {$sinCollar} sin collar, {$ignorados} ignorados.");
        }

        return [
            'sincronizados' => $sincronizados,
            'sin_collar' => $sinCollar,
            'ignorados' => $ignorados,
        ];
    }
}
