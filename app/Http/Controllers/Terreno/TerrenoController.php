<?php

namespace App\Http\Controllers\Terreno;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terreno\StoreTerrenoRequest;
use App\Http\Requests\Terreno\UpdateTerrenoRequest;
use App\Models\Granja;
use App\Models\Terreno;
use App\Services\Terreno\TerrenoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TerrenoController extends Controller
{
    public function __construct(
        private readonly TerrenoService $terrenoService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Terreno::class);

        return Inertia::render('terreno/Index', [
            'terrenos' => $this->terrenoService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'granjas' => $this->granjasDisponibles($request),
        ]);
    }

    /**
     * Reutiliza la misma vista de listado, agregando los animales asignados
     * a un terreno puntual. El frontend pide esta ruta con Inertia
     * `only: ['animalesTerreno']` para abrir el modal sin navegar de página.
     */
    public function animales(Request $request, Terreno $terreno): Response
    {
        $this->authorize('view', $terreno);

        return Inertia::render('terreno/Index', [
            'terrenos' => $this->terrenoService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'granjas' => $this->granjasDisponibles($request),
            'animalesTerreno' => $this->terrenoService->animalesDe($terreno),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', Terreno::class);

        return Inertia::render('terreno/Create', [
            'granjas' => $this->granjasDisponibles($request),
        ]);
    }

    public function edit(Request $request, Terreno $terreno): Response
    {
        $this->authorize('update', $terreno);

        return Inertia::render('terreno/Edit', [
            'terreno' => $terreno,
            'granjas' => $this->granjasDisponibles($request),
        ]);
    }

    public function store(StoreTerrenoRequest $request): RedirectResponse
    {
        $this->authorize('create', Terreno::class);

        $this->terrenoService->crear($request->toDTO(), $request->user());

        return redirect()->route('terrenos.index')->with('success', 'Terreno creado correctamente.');
    }

    public function update(UpdateTerrenoRequest $request, Terreno $terreno): RedirectResponse
    {
        $this->authorize('update', $terreno);

        $this->terrenoService->actualizar($terreno, $request->toDTO(), $request->user());

        return redirect()->route('terrenos.index')->with('success', 'Terreno actualizado correctamente.');
    }

    public function destroy(Terreno $terreno): RedirectResponse
    {
        $this->authorize('delete', $terreno);

        $this->terrenoService->eliminar($terreno);

        return redirect()->route('terrenos.index')->with('success', 'Terreno eliminado correctamente.');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Granja>
     */
    private function granjasDisponibles(Request $request): \Illuminate\Support\Collection
    {
        $user = $request->user();

        return $user->isSuperAdmin() ? Granja::query()->get() : $user->granjas()->get();
    }
}
