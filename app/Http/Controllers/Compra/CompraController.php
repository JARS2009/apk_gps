<?php

namespace App\Http\Controllers\Compra;

use App\Http\Controllers\Controller;
use App\Http\Requests\Compra\StoreCompraDocumentoRequest;
use App\Http\Requests\Compra\StoreCompraRequest;
use App\Http\Requests\Compra\UpdateCompraRequest;
use App\Models\Compra;
use App\Models\CompraDocumento;
use App\Models\Granja;
use App\Services\Compra\CompraService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class CompraController extends Controller
{
    public function __construct(
        private readonly CompraService $compraService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('compra/Index', [
            'compras' => $this->compraService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'granjas' => $this->granjasDisponibles($request),
        ]);
    }

    public function store(StoreCompraRequest $request): RedirectResponse
    {
        $this->compraService->crear($request->toDTO(), $request->user());

        return redirect()->route('compras.index')->with('success', 'Compra creada correctamente.');
    }

    public function update(UpdateCompraRequest $request, Compra $compra): RedirectResponse
    {
        $this->compraService->actualizar($compra, $request->toDTO(), $request->user());

        return redirect()->route('compras.index')->with('success', 'Compra actualizada correctamente.');
    }

    public function destroy(Compra $compra): RedirectResponse
    {
        $this->compraService->eliminar($compra);

        return redirect()->route('compras.index')->with('success', 'Compra eliminada correctamente.');
    }

    /**
     * Agrega un documento a una compra existente.
     */
    public function agregarDocumento(StoreCompraDocumentoRequest $request, Compra $compra): RedirectResponse
    {
        $this->compraService->agregarDocumento($compra, $request->toDTO());

        return redirect()->route('compras.index')->with('success', 'Documento agregado correctamente.');
    }

    /**
     * Elimina un documento de una compra.
     */
    public function eliminarDocumento(Compra $compra, CompraDocumento $documento): RedirectResponse
    {
        $this->compraService->eliminarDocumento($documento);

        return redirect()->route('compras.index')->with('success', 'Documento eliminado correctamente.');
    }

    /**
     * @return Collection<int, Granja>
     */
    private function granjasDisponibles(Request $request): Collection
    {
        $user = $request->user();

        return $user->isSuperAdmin() ? Granja::query()->get() : $user->granjas()->get();
    }
}
