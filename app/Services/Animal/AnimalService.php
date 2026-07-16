<?php

namespace App\Services\Animal;

use App\DTOs\Animal\CreateAnimalDTO;
use App\DTOs\Animal\UpdateAnimalDTO;
use App\Models\Animal;
use App\Models\Terreno;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AnimalService
{
    /**
     * @return LengthAwarePaginator<int, Animal>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return Animal::query()
            ->with(['granja', 'collar', 'terrenos'])
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereIn('granja_id', $actor->granjas()->pluck('farms.id')))
            ->when($request->search, fn ($q, $search) => $q->where(function ($sub) use ($search) {
                $sub->where('nombre', 'like', "%{$search}%")
                    ->orWhere('codigo', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function crear(CreateAnimalDTO $dto, User $actor): Animal
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        return DB::transaction(function () use ($dto) {
            $animal = Animal::create([
                'granja_id' => $dto->granja_id,
                'nombre' => $dto->nombre,
                'codigo' => $dto->codigo,
                'tipo' => $dto->tipo,
                'raza' => $dto->raza,
            ]);

            $this->sincronizarTerrenos($animal, $dto->terreno_ids, $dto->granja_id);

            return $animal;
        });
    }

    public function actualizar(Animal $animal, UpdateAnimalDTO $dto, User $actor): Animal
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        return DB::transaction(function () use ($animal, $dto) {
            $animal->update([
                'granja_id' => $dto->granja_id,
                'nombre' => $dto->nombre,
                'codigo' => $dto->codigo,
                'tipo' => $dto->tipo,
                'raza' => $dto->raza,
            ]);

            $this->sincronizarTerrenos($animal, $dto->terreno_ids, $dto->granja_id);

            return $animal;
        });
    }

    public function eliminar(Animal $animal): void
    {
        $animal->delete();
    }

    /**
     * @param  array<int, int>  $terrenoIds
     */
    private function sincronizarTerrenos(Animal $animal, array $terrenoIds, int $granjaId): void
    {
        if (empty($terrenoIds)) {
            $animal->terrenos()->sync([]);

            return;
        }

        $validos = Terreno::query()
            ->where('granja_id', $granjaId)
            ->whereIn('id', $terrenoIds)
            ->pluck('id')
            ->all();

        $animal->terrenos()->sync($validos);
    }

    private function validarGranjaAccesible(int $granjaId, User $actor): void
    {
        if ($actor->isSuperAdmin()) {
            return;
        }

        if (! $actor->granjas()->where('farms.id', $granjaId)->exists()) {
            throw ValidationException::withMessages([
                'granja_id' => 'No tienes acceso a esta granja.',
            ]);
        }
    }
}
