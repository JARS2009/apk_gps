<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\Configuracion\UpdateConfiguracionRequest;
use App\Models\Granja;
use App\Services\Configuracion\ConfiguracionService;
use Illuminate\Http\RedirectResponse;

class ConfiguracionController extends Controller
{
    public function __construct(
        private readonly ConfiguracionService $configuracionService,
    ) {}

    public function update(UpdateConfiguracionRequest $request, Granja $granja): RedirectResponse
    {
        $this->authorize('update', $granja);

        $this->configuracionService->actualizar($granja, $request->toDTO());

        return redirect()->route('granjas.index')
            ->with('success', 'Configuración actualizada correctamente.');
    }
}
