<?php

namespace App\Services\Configuracion;

use App\DTOs\Configuracion\UpdateConfiguracionDTO;
use App\Models\Configuracion;
use App\Models\Granja;

class ConfiguracionService
{
    public function obtenerPorGranja(Granja $granja): Configuracion
    {
        return $granja->configuracion()->firstOrCreate([
            'granja_id' => $granja->id,
        ]);
    }

    public function actualizar(Granja $granja, UpdateConfiguracionDTO $dto): Configuracion
    {
        $configuracion = $this->obtenerPorGranja($granja);
        $configuracion->update($dto->toArray());

        return $configuracion;
    }
}
