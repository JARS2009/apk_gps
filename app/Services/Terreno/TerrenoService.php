<?php

namespace App\Services\Terreno;

use App\DTOs\Terreno\CreateTerrenoDTO;
use App\DTOs\Terreno\UpdateTerrenoDTO;
use App\Models\Animal;
use App\Models\Terreno;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class TerrenoService
{
    /**
     * @return LengthAwarePaginator<int, Terreno>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return Terreno::query()
            ->with('granja')
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereIn('granja_id', $actor->granjas()->pluck('farms.id')))
            ->when($request->search, fn ($q, $search) => $q->where('nombre', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function crear(CreateTerrenoDTO $dto, User $actor): Terreno
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        return Terreno::create($dto->toArray());
    }

    public function actualizar(Terreno $terreno, UpdateTerrenoDTO $dto, User $actor): Terreno
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        $terreno->update($dto->toArray());

        return $terreno;
    }

    public function eliminar(Terreno $terreno): void
    {
        $terreno->delete();
    }

    /**
     * Animales asignados a este terreno (un animal puede estar en varios
     * terrenos a la vez, por ejemplo su potrero y su establo).
     *
     * @return Collection<int, Animal>
     */
    public function animalesDe(Terreno $terreno): Collection
    {
        return $terreno->animales()->with('collar')->orderBy('nombre')->get();
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
