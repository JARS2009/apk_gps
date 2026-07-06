<?php

namespace App\Helpers;

class GeoHelper
{
    /**
     * Determina si un punto (lat, lng) está dentro de un polígono.
     * Usa el algoritmo Ray Casting.
     *
     * @param  float  $lat
     * @param  float  $lng
     * @param  array<int, array{lat: float, lng: float}>  $polygon
     */
    public static function puntoEnPoligono(float $lat, float $lng, array $polygon): bool
    {
        $n = count($polygon);
        if ($n < 3) {
            return false;
        }

        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $yi = $polygon[$i]['lat'];
            $xi = $polygon[$i]['lng'];
            $yj = $polygon[$j]['lat'];
            $xj = $polygon[$j]['lng'];

            if ((($yi > $lat) !== ($yj > $lat))
                && ($lng < ($xj - $xi) * ($lat - $yi) / ($yj - $yi) + $xi)) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * Determina si un punto está dentro de ALGUNO de los terrenos dados.
     * Retorna el terreno que contiene el punto, o null si está fuera de todos.
     *
     * @param  float  $lat
     * @param  float  $lng
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models\Terreno>  $terrenos
     */
    public static function encontrarTerrenoContenedor(float $lat, float $lng, $terrenos): ?\App\Models\Terreno
    {
        foreach ($terrenos as $terreno) {
            if (self::puntoEnPoligono($lat, $lng, $terreno->coordenadas ?? [])) {
                return $terreno;
            }
        }

        return null;
    }
}
