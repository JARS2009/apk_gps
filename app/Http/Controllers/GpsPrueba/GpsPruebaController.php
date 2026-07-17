<?php

namespace App\Http\Controllers\GpsPrueba;

use App\Http\Controllers\Controller;
use App\Models\UbicacionPrueba;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GpsPruebaController extends Controller
{
    /**
     * Lista las últimas ubicaciones de prueba recibidas.
     * GET /api/gps-prueba/ubicaciones
     */
    public function index(Request $request): JsonResponse
    {
        $imei = $request->query('imei');
        $limit = min((int) $request->query('limit', 50), 200);

        $query = UbicacionPrueba::query()
            ->where('evento', '!=', 'login')
            ->orderByDesc('created_at');

        if ($imei) {
            $query->where('imei', $imei);
        }

        return response()->json([
            'total' => $query->count(),
            'ubicaciones' => $query->limit($limit)->get(),
        ]);
    }

    /**
     * Última ubicación conocida por IMEI.
     * GET /api/gps-prueba/ultima/{imei}
     */
    public function ultima(string $imei): JsonResponse
    {
        $ubicacion = UbicacionPrueba::where('imei', $imei)
            ->whereIn('evento', ['ubicacion', 'sin_fix', 'alarma'])
            ->orderByDesc('created_at')
            ->first();

        if (! $ubicacion) {
            return response()->json(['error' => 'Sin ubicaciones para este IMEI'], 404);
        }

        return response()->json($ubicacion);
    }
}
