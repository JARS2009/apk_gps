<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $granja_id
 * @property string $nombre
 * @property string $codigo
 * @property string|null $tipo
 * @property string|null $raza
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Animal extends Model
{
    /** @use HasFactory<\Database\Factories\AnimalFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'animals';

    protected $fillable = [
        'granja_id',
        'nombre',
        'codigo',
        'tipo',
        'raza',
    ];

    /**
     * @return array<string, string>
     */
    /**
     * @return BelongsTo<Granja, $this>
     */
    public function granja(): BelongsTo
    {
        return $this->belongsTo(Granja::class, 'granja_id');
    }

    /**
     * @return BelongsToMany<Terreno, $this>
     */
    public function terrenos(): BelongsToMany
    {
        return $this->belongsToMany(Terreno::class, 'animal_land', 'animal_id', 'terreno_id');
    }

    /**
     * @return HasOne<Collar, $this>
     */
    public function collar(): HasOne
    {
        return $this->hasOne(Collar::class, 'animal_id');
    }
}
