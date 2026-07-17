<?php

namespace App\Services\Collar;

use App\DTOs\Collar\CreateCollarDTO;
use App\DTOs\Collar\UpdateCollarDTO;
use App\Enums\CollarEstado;
use App\Models\Animal;
use App\Models\Collar;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class CollarService
{
    /**
     * @return LengthAwarePaginator<int, Collar>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return Collar::query()
            ->with(['animal.granja'])
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereHas(
                'animal',
                fn ($animal) => $animal->whereIn('granja_id', $actor->granjas()->pluck('farms.id'))
            ))
            ->when($request->search, fn ($q, $search) => $q->where(function ($sub) use ($search) {
                $sub->where('serie', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('imei', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * Animales que aún no tienen collar asignado, dentro de las granjas
     * accesibles para el actor. Se usan como opciones al asignar un collar
     * desde el formulario.
     *
     * @return Collection<int, Animal>
     */
    public function animalesDisponibles(User $actor): Collection
    {
        return Animal::query()
            ->with('granja')
            ->whereDoesntHave('collar')
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereIn('granja_id', $actor->granjas()->pluck('farms.id')))
            ->orderBy('nombre')
            ->get();
    }

    public function crear(CreateCollarDTO $dto): Collar
    {
        if ($dto->animal_id !== null) {
            $this->validarAnimalDisponible($dto->animal_id);
        }

        return Collar::create([
            'serie' => $dto->serie,
            'modelo' => $dto->modelo,
            'imei' => $dto->imei,
            'animal_id' => $dto->animal_id,
            'estado' => $dto->animal_id !== null ? CollarEstado::Asignado : $dto->estado,
        ]);
    }

    public function actualizar(Collar $collar, UpdateCollarDTO $dto): Collar
    {
        if ($dto->animal_id !== null && $dto->animal_id != $collar->animal_id) {
            $this->validarAnimalDisponible($dto->animal_id);
        }

        $collar->update([
            'serie' => $dto->serie,
            'modelo' => $dto->modelo,
            'imei' => $dto->imei,
            'animal_id' => $dto->animal_id,
            'estado' => $dto->animal_id !== null ? CollarEstado::Asignado : $dto->estado,
        ]);

        return $collar;
    }

    public function eliminar(Collar $collar): void
    {
        $collar->delete();
    }

    public function asignarAAnimal(Collar $collar, ?int $animalId): Collar
    {
        if ($animalId !== null) {
            $this->validarAnimalDisponible($animalId, excluirCollarId: $collar->id);
        }

        $collar->update([
            'animal_id' => $animalId,
            'estado' => $animalId !== null ? CollarEstado::Asignado : CollarEstado::Disponible,
        ]);

        return $collar;
    }

    private function validarAnimalDisponible(int $animalId, ?int $excluirCollarId = null): void
    {
        $ocupado = Collar::query()
            ->where('animal_id', $animalId)
            ->when($excluirCollarId, fn ($q) => $q->whereKeyNot($excluirCollarId))
            ->exists();

        if ($ocupado) {
            throw ValidationException::withMessages([
                'animal_id' => 'El animal seleccionado ya tiene un collar asignado.',
            ]);
        }
    }
}
