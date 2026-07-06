<?php

namespace App\Services\Granja;

use App\DTOs\Granja\CreateGranjaDTO;
use App\DTOs\Granja\UpdateGranjaDTO;
use App\Models\Configuracion;
use App\Models\Granja;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class GranjaService
{
    /**
     * @return LengthAwarePaginator<int, Granja>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return Granja::query()
            ->with(['creador', 'configuracion'])
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereHas(
                'usuarios',
                fn ($usuarios) => $usuarios->where('users.id', $actor->id)
            ))
            ->when($request->search, fn ($q, $search) => $q->where('nombre', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function crear(CreateGranjaDTO $dto, User $actor): Granja
    {
        return DB::transaction(function () use ($dto, $actor) {
            $granja = Granja::create([
                ...$dto->toArray(),
                'id_usuario_creador' => $actor->id,
            ]);

            $granja->usuarios()->attach($actor->id);

            Configuracion::create([
                'granja_id' => $granja->id,
                'alertas_activas' => false,
            ]);

            return $granja;
        });
    }

    public function actualizar(Granja $granja, UpdateGranjaDTO $dto): Granja
    {
        $granja->update($dto->toArray());

        return $granja;
    }

    public function eliminar(Granja $granja): void
    {
        $granja->delete();
    }
}
