<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $id_usuario_creador
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $activa
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Granja extends Model
{
    /** @use HasFactory<\Database\Factories\GranjaFactory> */
    use HasFactory, SoftDeletes;

    protected $table = 'farms';

    protected $fillable = [
        'id_usuario_creador',
        'nombre',
        'descripcion',
        'activa',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_creador');
    }

    /**
     * @return BelongsToMany<User, $this>
     */
    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'farm_user', 'granja_id', 'user_id');
    }

    /**
     * @return HasMany<Terreno, $this>
     */
    public function terrenos(): HasMany
    {
        return $this->hasMany(Terreno::class, 'granja_id');
    }

    /**
     * @return HasMany<Animal, $this>
     */
    public function animales(): HasMany
    {
        return $this->hasMany(Animal::class, 'granja_id');
    }

    /**
     * @return HasOne<Configuracion, $this>
     */
    public function configuracion(): HasOne
    {
        return $this->hasOne(Configuracion::class, 'granja_id');
    }
}
