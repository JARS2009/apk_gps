<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $collar_id
 * @property float $latitud
 * @property float $longitud
 * @property Carbon $recibido_en
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class UbicacionCollar extends Model
{
    /** @use HasFactory<\Database\Factories\UbicacionCollarFactory> */
    use HasFactory;

    protected $table = 'collar_locations';

    protected $fillable = [
        'collar_id',
        'latitud',
        'longitud',
        'recibido_en',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitud' => 'decimal:7',
            'longitud' => 'decimal:7',
            'recibido_en' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Collar, $this>
     */
    public function collar(): BelongsTo
    {
        return $this->belongsTo(Collar::class, 'collar_id');
    }
}
