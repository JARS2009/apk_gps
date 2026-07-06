<?php

namespace App\Http\Controllers\Tracking;

use App\Http\Controllers\Controller;
use App\Models\Collar;
use App\Services\Tracking\TrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct(
        private readonly TrackingService $trackingService,
    ) {}

    /**
     * Recibe la ubicación GPS del celular (emulando collar).
     * POST /api/tracking/ubicacion
     */
    public function registrarUbicacion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'collar_id' => ['required', 'integer', 'exists:collars,id'],
            'latitud' => ['required', 'numeric', 'between:-90,90'],
            'longitud' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $collar = Collar::with('animal')->findOrFail($validated['collar_id']);

        $resultado = $this->trackingService->registrarUbicacion(
            $collar,
            (float) $validated['latitud'],
            (float) $validated['longitud'],
        );

        return response()->json([
            'success' => true,
            'dentro_de_terreno' => $resultado['dentro'],
            'alerta' => $resultado['alerta'] ? [
                'id' => $resultado['alerta']->id,
                'mensaje' => $resultado['alerta']->mensaje,
                'tipo' => $resultado['alerta']->tipo,
            ] : null,
        ]);
    }

    /**
     * Obtiene los collares activos del usuario (para seleccionar cuál emular).
     * GET /api/tracking/collares
     */
    public function collaresActivos(Request $request): JsonResponse
    {
        $user = $request->user();

        $granjaIds = $user->isSuperAdmin()
            ? \App\Models\Granja::pluck('id')
            : $user->granjas()->pluck('farms.id');

        $collares = Collar::query()
            ->where('estado', 'asignado')
            ->whereHas('animal', fn ($q) => $q->whereIn('granja_id', $granjaIds))
            ->with('animal:id,nombre,codigo')
            ->select(['id', 'animal_id', 'serie', 'modelo', 'estado'])
            ->get();

        return response()->json($collares);
    }
}
