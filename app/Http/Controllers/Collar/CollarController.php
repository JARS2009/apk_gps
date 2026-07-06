<?php

namespace App\Http\Controllers\Collar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Collar\StoreCollarRequest;
use App\Http\Requests\Collar\UpdateCollarRequest;
use App\Models\Collar;
use App\Services\Collar\CollarService;
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

    public function asignar(Request $request, Collar $collar): RedirectResponse
    {
        $this->authorize('update', $collar);

        $request->validate(['animal_id' => ['nullable', 'integer', 'exists:animals,id']]);

        $this->collarService->asignarAAnimal($collar, $request->integer('animal_id') ?: null);

        return redirect()->route('collares.index')->with('success', 'Collar asignado correctamente.');
    }
}
