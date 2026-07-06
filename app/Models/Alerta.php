<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $granja_id
 * @property int $collar_id
 * @property int|null $animal_id
 * @property int|null $terreno_id
 * @property string $tipo
 * @property float $latitud
 * @property float $longitud
 * @property string|null $mensaje
 * @property bool $leida
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Alerta extends Model
{
    use HasFactory;

    protected $table = 'alertas';

    protected $fillable = [
        'granja_id',
        'collar_id',
        'animal_id',
        'terreno_id',
        'tipo',
        'latitud',
        'longitud',
        'mensaje',
        'leida',
    ];

    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'leida' => 'boolean',
        ];
    }

    public function granja(): BelongsTo
    {
        return $this->belongsTo(Granja::class, 'granja_id');
    }

    public function collar(): BelongsTo
    {
        return $this->belongsTo(Collar::class, 'collar_id');
    }

    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    public function terreno(): BelongsTo
    {
        return $this->belongsTo(Terreno::class, 'terreno_id');
    }
}
