<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $granja_id
 * @property string $serie
 * @property string $correlativo
 * @property string|null $proveedor
 * @property Carbon $fecha
 * @property string|null $observaciones
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Compra extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'purchases';

    protected $fillable = [
        'granja_id',
        'serie',
        'correlativo',
        'proveedor',
        'fecha',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    /**
     * Código completo de la compra: SERIE-CORRELATIVO.
     */
    public function getCodigoCompletoAttribute(): string
    {
        return "{$this->serie}-{$this->correlativo}";
    }

    /**
     * @return BelongsTo<Granja, $this>
     */
    public function granja(): BelongsTo
    {
        return $this->belongsTo(Granja::class, 'granja_id');
    }

    /**
     * @return HasMany<CompraDocumento, $this>
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(CompraDocumento::class, 'purchase_id');
    }
}
