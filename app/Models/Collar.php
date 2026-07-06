<?php

namespace App\Models;

use App\Enums\CollarEstado;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $animal_id
 * @property string $serie
 * @property string $modelo
 * @property CollarEstado $estado
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Collar extends Model
{
    /** @use HasFactory<\Database\Factories\CollarFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'collars';

    protected $fillable = [
        'animal_id',
        'serie',
        'modelo',
        'estado',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'estado' => CollarEstado::class,
        ];
    }

    /**
     * @return BelongsTo<Animal, $this>
     */
    public function animal(): BelongsTo
    {
        return $this->belongsTo(Animal::class, 'animal_id');
    }

    /**
     * @return HasMany<UbicacionCollar, $this>
     */
    public function ubicaciones(): HasMany
    {
        return $this->hasMany(UbicacionCollar::class, 'collar_id');
    }
}
