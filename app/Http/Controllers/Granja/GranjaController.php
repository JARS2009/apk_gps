<?php

namespace App\Http\Controllers\Granja;

use App\Http\Controllers\Controller;
use App\Http\Requests\Granja\StoreGranjaRequest;
use App\Http\Requests\Granja\UpdateGranjaRequest;
use App\Models\Granja;
use App\Services\Configuracion\ConfiguracionService;
use App\Services\Granja\GranjaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GranjaController extends Controller
{
    public function __construct(
        private readonly GranjaService $granjaService,
        private readonly ConfiguracionService $configuracionService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Granja::class);

        return Inertia::render('granja/Index', [
            'granjas' => $this->granjaService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
        ]);
    }

    /**
     * Reutiliza la misma vista de listado, agregando la configuración de una
     * granja puntual. El frontend pide esta ruta con Inertia `only: ['configuracion']`
     * para abrir el modal de configuración sin navegar de página.
     */
    public function configuracion(Request $request, Granja $granja): Response
    {
        $this->authorize('update', $granja);

        return Inertia::render('granja/Index', [
            'granjas' => $this->granjaService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'configuracion' => $this->configuracionService->obtenerPorGranja($granja),
        ]);
    }

    public function store(StoreGranjaRequest $request): RedirectResponse
    {
        $this->authorize('create', Granja::class);

        $this->granjaService->crear($request->toDTO(), $request->user());

        return redirect()->route('granjas.index')->with('success', 'Granja creada correctamente.');
    }

    public function update(UpdateGranjaRequest $request, Granja $granja): RedirectResponse
    {
        $this->authorize('update', $granja);

        $this->granjaService->actualizar($granja, $request->toDTO());

        return redirect()->route('granjas.index')->with('success', 'Granja actualizada correctamente.');
    }

    public function destroy(Granja $granja): RedirectResponse
    {
        $this->authorize('delete', $granja);

        $this->granjaService->eliminar($granja);

        return redirect()->route('granjas.index')->with('success', 'Granja eliminada correctamente.');
    }
}
