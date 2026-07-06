<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $granja_id
 * @property string|null $telefono_policia
 * @property string|null $telefono_emergencia
 * @property string|null $mensaje_alerta
 * @property bool $alertas_activas
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Configuracion extends Model
{
    /** @use HasFactory<\Database\Factories\ConfiguracionFactory> */
    use HasFactory;

    protected $table = 'farm_settings';

    protected $fillable = [
        'granja_id',
        'telefono_policia',
        'telefono_emergencia',
        'mensaje_alerta',
        'alertas_activas',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'alertas_activas' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Granja, $this>
     */
    public function granja(): BelongsTo
    {
        return $this->belongsTo(Granja::class, 'granja_id');
    }
}
