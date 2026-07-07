<?php

namespace App\Services\TipoEquipo;

use App\DTOs\TipoEquipo\CreateTipoEquipoDTO;
use App\DTOs\TipoEquipo\UpdateTipoEquipoDTO;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TipoEquipoService
{
    /**
     * @return LengthAwarePaginator<int, TipoEquipo>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return TipoEquipo::query()
            ->with(['granja'])
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereIn('granja_id', $actor->granjas()->pluck('farms.id')))
            ->when($request->search, fn ($q, $search) => $q->where('nombre', 'like', "%{$search}%")
                ->orWhere('prefijo_codigo', 'like', "%{$search}%"))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();
    }

    public function crear(CreateTipoEquipoDTO $dto, User $actor): TipoEquipo
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        return TipoEquipo::create($dto->toArray());
    }

    public function actualizar(TipoEquipo $tipo, UpdateTipoEquipoDTO $dto, User $actor): TipoEquipo
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        $tipo->update($dto->toArray());

        return $tipo;
    }

    public function eliminar(TipoEquipo $tipo): void
    {
        $tipo->delete();
    }

    /**
     * Importación masiva: recibe un array de tipos únicos y los inserta
     * ignorando duplicados por prefijo_codigo dentro de la misma granja.
     *
     * @param  int  $granjaId
     * @param  array<int, array{nombre: string, prefijo_codigo: string}>  $tipos
     * @return array{creados: int, omitidos: int}
     */
    public function importarMasivo(int $granjaId, array $tipos, User $actor): array
    {
        $this->validarGranjaAccesible($granjaId, $actor);

        $existentes = TipoEquipo::query()
            ->where('granja_id', $granjaId)
            ->pluck('prefijo_codigo')
            ->map(fn ($p) => mb_strtoupper($p))
            ->toArray();

        $creados = 0;
        $omitidos = 0;

        DB::transaction(function () use ($granjaId, $tipos, $existentes, &$creados, &$omitidos) {
            foreach ($tipos as $tipo) {
                $prefijo = mb_strtoupper(trim($tipo['prefijo_codigo']));

                if (in_array($prefijo, $existentes, true)) {
                    $omitidos++;

                    continue;
                }

                TipoEquipo::create([
                    'granja_id' => $granjaId,
                    'nombre' => trim($tipo['nombre']),
                    'prefijo_codigo' => $prefijo,
                    'estado' => true,
                ]);

                $existentes[] = $prefijo;
                $creados++;
            }
        });

        return ['creados' => $creados, 'omitidos' => $omitidos];
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
