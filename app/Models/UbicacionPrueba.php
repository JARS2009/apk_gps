<?php

namespace App\Models;

use App\Observers\UbicacionPruebaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $imei
 * @property string $ubicacion
 * @property float $latitud
 * @property float $longitud
 * @property float|null $velocidad
 * @property float|null $rumbo
 * @property string $evento
 * @property string|null $trama_raw
 * @property Carbon|null $fecha_gps
 * @property Carbon|null $synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[ObservedBy(UbicacionPruebaObserver::class)]
class UbicacionPrueba extends Model
{
    protected $table = 'ubicacion_prueba';

    protected $fillable = [
        'imei',
        'ubicacion',
        'latitud',
        'longitud',
        'velocidad',
        'rumbo',
        'evento',
        'trama_raw',
        'fecha_gps',
        'synced_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'velocidad' => 'decimal:2',
            'rumbo' => 'decimal:2',
            'fecha_gps' => 'datetime',
            'synced_at' => 'datetime',
        ];
    }
}
