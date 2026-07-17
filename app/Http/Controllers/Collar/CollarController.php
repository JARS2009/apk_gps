<?php

namespace App\Http\Controllers\Collar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collar\StoreCollarRequest;
use App\Http\Requests\Collar\UpdateCollarRequest;
use App\Models\Collar;
use App\Models\UbicacionPrueba;
use App\Services\Collar\CollarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CollarController extends Controller
{
    public function __construct(
        private readonly CollarService $collarService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Collar::class);

        return Inertia::render('collar/Index', [
            'collares' => $this->collarService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'animales' => $this->collarService->animalesDisponibles($request->user()),
        ]);
    }

    public function store(StoreCollarRequest $request): RedirectResponse
    {
        $this->authorize('create', Collar::class);

        $this->collarService->crear($request->toDTO());

        return redirect()->route('collares.index')->with('success', 'Collar creado correctamente.');
    }

    public function update(UpdateCollarRequest $request, Collar $collar): RedirectResponse
    {
        $this->authorize('update', $collar);

        $this->collarService->actualizar($collar, $request->toDTO());

        return redirect()->route('collares.index')->with('success', 'Collar actualizado correctamente.');
    }

    public function destroy(Collar $collar): RedirectResponse
    {
        $this->authorize('delete', $collar);

        $this->collarService->eliminar($collar);

        return redirect()->route('collares.index')->with('success', 'Collar eliminado correctamente.');
    }

    /**
     * Vista de ruta/recorrido de un collar.
     * GET /collares/{collar}/ruta
     */
    public function ruta(Collar $collar): Response
    {
        $this->authorize('view', $collar);

        $collar->load(['animal.granja', 'animal.terrenos']);

        return Inertia::render('collar/Ruta', [
            'collar' => $collar,
            'terrenos' => $collar->animal
                ? \App\Models\Terreno::where('granja_id', $collar->animal->granja_id)
                    ->select(['id', 'nombre', 'coordenadas', 'area'])
                    ->get()
                : [],
        ]);
    }

    /**
     * API: ubicaciones del collar para dibujar la ruta.
     * GET /api/collares/{collar}/ubicaciones
     */
    public function ubicaciones(Request $request, Collar $collar): JsonResponse
    {
        $this->authorize('view', $collar);

        if (! $collar->imei) {
            return response()->json(['ubicaciones' => [], 'total' => 0]);
        }

        $desde = $request->query('desde');
        $hasta = $request->query('hasta');
        $limit = min((int) $request->query('limit', 500), 2000);

        $query = UbicacionPrueba::where('imei', $collar->imei)
            ->whereNotIn('evento', ['login', 'heartbeat'])
            ->where(function ($q) {
                $q->where(fn ($sub) => $sub->whereRaw('ABS(latitud) > 0.001 OR ABS(longitud) > 0.001'));
            })
            ->orderBy('fecha_gps');

        if ($desde) {
            $query->where('fecha_gps', '>=', $desde);
        }
        if ($hasta) {
            $query->where('fecha_gps', '<=', $hasta);
        }

        $ubicaciones = $query->limit($limit)
            ->select(['id', 'latitud', 'longitud', 'velocidad', 'rumbo', 'evento', 'fecha_gps', 'created_at'])
            ->get();

        return response()->json([
            'ubicaciones' => $ubicaciones,
            'total' => $ubicaciones->count(),
        ]);
    }

    public function asignar(Request $request, Collar $collar): RedirectResponse
    {
        $this->authorize('update', $collar);

        $request->validate(['animal_id' => ['nullable', 'integer', 'exists:animals,id']]);

        $this->collarService->asignarAAnimal($collar, $request->integer('animal_id') ?: null);

        return redirect()->route('collares.index')->with('success', 'Collar asignado correctamente.');
    }
}
