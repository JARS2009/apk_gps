<?php

namespace App\Services\Tracking;

use App\Helpers\GeoHelper;
use App\Models\Alerta;
use App\Models\Collar;
use App\Models\Terreno;
use App\Models\UbicacionCollar;
use Illuminate\Support\Carbon;

class TrackingService
{
    /**
     * Registra una ubicación GPS para un collar y verifica si está dentro de sus terrenos.
     * Si está fuera de rango, crea una alerta.
     *
     * @return array{ubicacion: UbicacionCollar, dentro: bool, alerta: Alerta|null}
     */
    public function registrarUbicacion(Collar $collar, float $latitud, float $longitud): array
    {
        // Guardar ubicación
        $ubicacion = UbicacionCollar::create([
            'collar_id' => $collar->id,
            'latitud' => $latitud,
            'longitud' => $longitud,
            'recibido_en' => Carbon::now(),
        ]);

        // Obtener animal y sus terrenos asignados
        $animal = $collar->animal;
        if (! $animal) {
            return ['ubicacion' => $ubicacion, 'dentro' => true, 'alerta' => null];
        }

        $animal->load('terrenos');
        $terrenosAsignados = $animal->terrenos;

        if ($terrenosAsignados->isEmpty()) {
            // Si no tiene terrenos asignados, obtener todos los de la granja
            $terrenosAsignados = Terreno::where('granja_id', $animal->granja_id)->get();
        }

        if ($terrenosAsignados->isEmpty()) {
            return ['ubicacion' => $ubicacion, 'dentro' => true, 'alerta' => null];
        }

        // Verificar si está dentro de algún terreno
        $terrenoContenedor = GeoHelper::encontrarTerrenoContenedor($latitud, $longitud, $terrenosAsignados);

        $alerta = null;
        if ($terrenoContenedor === null) {
            // Está fuera de todos los terrenos - verificar que no haya alerta reciente (últimos 5 min)
            $alertaReciente = Alerta::query()
                ->where('collar_id', $collar->id)
                ->where('tipo', 'fuera_de_rango')
                ->where('created_at', '>=', Carbon::now()->subMinutes(5))
                ->exists();

            if (! $alertaReciente) {
                $alerta = Alerta::create([
                    'granja_id' => $animal->granja_id,
                    'collar_id' => $collar->id,
                    'animal_id' => $animal->id,
                    'terreno_id' => null,
                    'tipo' => 'fuera_de_rango',
                    'latitud' => $latitud,
                    'longitud' => $longitud,
                    'mensaje' => "El animal '{$animal->nombre}' ({$animal->codigo}) está fuera del terreno asignado.",
                ]);
            }
        }

        return [
            'ubicacion' => $ubicacion,
            'dentro' => $terrenoContenedor !== null,
            'alerta' => $alerta,
        ];
    }
}
