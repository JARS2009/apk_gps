<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $granja_id
 * @property string $nombre
 * @property string $prefijo_codigo
 * @property bool $estado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class TipoEquipo extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'equipment_types';

    protected $fillable = [
        'granja_id',
        'nombre',
        'prefijo_codigo',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'boolean',
        ];
    }

    /**
     * Ejemplo de código: PREFIJO-CCA-001
     */
    public function getEjemploCodigoAttribute(): string
    {
        return "{$this->prefijo_codigo}-CCA-001";
    }

    /**
     * @return BelongsTo<Granja, $this>
     */
    public function granja(): BelongsTo
    {
        return $this->belongsTo(Granja::class, 'granja_id');
    }
}
