<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService,
    ) {}

    /**
     * Vista principal del dashboard con mapa y alertas.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Dashboard', [
            'terrenos' => $this->dashboardService->terrenosDelUsuario($user),
            'animales' => $this->dashboardService->animalesConUbicacion($user),
            'alertas' => $this->dashboardService->alertasRecientes($user),
            'alertasNoLeidas' => $this->dashboardService->contarAlertasNoLeidas($user),
        ]);
    }

    /**
     * Endpoint JSON para polling (auto-refresh cada minuto).
     */
    public function datos(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'animales' => $this->dashboardService->animalesConUbicacion($user),
            'alertas' => $this->dashboardService->alertasRecientes($user),
            'alertasNoLeidas' => $this->dashboardService->contarAlertasNoLeidas($user),
        ]);
    }

    /**
     * Marca alertas como leídas.
     */
    public function marcarLeidas(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['integer', 'exists:alertas,id'],
        ]);

        $count = $this->dashboardService->marcarAlertasLeidas(
            $request->user(),
            $request->input('ids'),
        );

        return response()->json(['marcadas' => $count]);
    }
}
