<?php

namespace App\Console\Commands;

use App\Services\Gps\SyncGpsService;
use Illuminate\Console\Command;

class SyncGpsCommand extends Command
{
    protected $signature = 'sync:gps {--limit=500 : Máximo de registros a procesar por ejecución}';

    protected $description = 'Sincroniza ubicaciones GPS del SinoTrack (ubicacion_prueba) a collar_locations';

    public function handle(SyncGpsService $service): int
    {
        $limite = (int) $this->option('limit');

        $resultado = $service->sincronizar($limite);

        $this->info(
            "Sync GPS: {$resultado['sincronizados']} sincronizados, "
            ."{$resultado['sin_collar']} sin collar, "
            ."{$resultado['ignorados']} ignorados."
        );

        return self::SUCCESS;
    }
}
