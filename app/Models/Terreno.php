<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $granja_id
 * @property string $nombre
 * @property array<int, array<string, float>> $coordenadas
 * @property float|null $area
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Terreno extends Model
{
    /** @use HasFactory<\Database\Factories\TerrenoFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'lands';

    protected $fillable = [
        'granja_id',
        'nombre',
        'coordenadas',
        'area',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'coordenadas' => 'array',
            'area' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Granja, $this>
     */
    public function granja(): BelongsTo
    {
        return $this->belongsTo(Granja::class, 'granja_id');
    }

    /**
     * @return BelongsToMany<Animal, $this>
     */
    public function animales(): BelongsToMany
    {
        return $this->belongsToMany(Animal::class, 'animal_land', 'terreno_id', 'animal_id');
    }
}
