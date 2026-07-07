<?php

namespace App\Services\Compra;

use App\DTOs\Compra\CreateCompraDTO;
use App\DTOs\Compra\CreateCompraDocumentoDTO;
use App\DTOs\Compra\UpdateCompraDTO;
use App\Models\Compra;
use App\Models\CompraDocumento;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraService
{
    /**
     * Lista paginada de compras con búsqueda por serie/correlativo
     * de la compra O de sus documentos relacionados.
     *
     * @return LengthAwarePaginator<int, Compra>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return Compra::query()
            ->with(['granja', 'documentos'])
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereIn('granja_id', $actor->granjas()->pluck('farms.id')))
            ->when($request->search, function ($q, $search) {
                $q->where(function ($sub) use ($search) {
                    // Buscar por serie-correlativo de la compra
                    $sub->where(DB::raw("CONCAT(serie, '-', correlativo)"), 'like', "%{$search}%")
                        ->orWhere('serie', 'like', "%{$search}%")
                        ->orWhere('correlativo', 'like', "%{$search}%")
                        ->orWhere('proveedor', 'like', "%{$search}%")
                        // Buscar por serie-correlativo de documentos relacionados
                        ->orWhereHas('documentos', function ($docQuery) use ($search) {
                            $docQuery->where(DB::raw("CONCAT(serie_documento, '-', correlativo_documento)"), 'like', "%{$search}%")
                                ->orWhere('serie_documento', 'like', "%{$search}%")
                                ->orWhere('correlativo_documento', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function crear(CreateCompraDTO $dto, User $actor): Compra
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        return DB::transaction(function () use ($dto) {
            return Compra::create($dto->toArray());
        });
    }

    public function actualizar(Compra $compra, UpdateCompraDTO $dto, User $actor): Compra
    {
        $this->validarGranjaAccesible($dto->granja_id, $actor);

        return DB::transaction(function () use ($compra, $dto) {
            $compra->update($dto->toArray());

            return $compra;
        });
    }

    public function eliminar(Compra $compra): void
    {
        $compra->delete();
    }

    /**
     * Agrega un documento a una compra existente.
     */
    public function agregarDocumento(Compra $compra, CreateCompraDocumentoDTO $dto): CompraDocumento
    {
        return $compra->documentos()->create($dto->toArray());
    }

    /**
     * Elimina un documento de una compra.
     */
    public function eliminarDocumento(CompraDocumento $documento): void
    {
        $documento->delete();
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
