<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $purchase_id
 * @property string $tipo_documento
 * @property string $serie_documento
 * @property string $correlativo_documento
 * @property Carbon|null $fecha_documento
 * @property string|null $observaciones
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CompraDocumento extends Model
{
    use HasFactory;

    protected $table = 'purchase_documents';

    protected $fillable = [
        'purchase_id',
        'tipo_documento',
        'serie_documento',
        'correlativo_documento',
        'fecha_documento',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'fecha_documento' => 'date',
        ];
    }

    /**
     * Código completo del documento: SERIE-CORRELATIVO.
     */
    public function getCodigoDocumentoAttribute(): string
    {
        return "{$this->serie_documento}-{$this->correlativo_documento}";
    }

    /**
     * @return BelongsTo<Compra, $this>
     */
    public function compra(): BelongsTo
    {
        return $this->belongsTo(Compra::class, 'purchase_id');
    }
}
