<?php

namespace App\Http\Controllers\Animal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Animal\StoreAnimalRequest;
use App\Http\Requests\Animal\UpdateAnimalRequest;
use App\Models\Animal;
use App\Models\Granja;
use App\Models\Terreno;
use App\Services\Animal\AnimalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class AnimalController extends Controller
{
    public function __construct(
        private readonly AnimalService $animalService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Animal::class);

        return Inertia::render('animal/Index', [
            'animales' => $this->animalService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'granjas' => $this->granjasDisponibles($request),
            'terrenos' => $this->terrenosDisponibles($request),
        ]);
    }

    public function store(StoreAnimalRequest $request): RedirectResponse
    {
        $this->authorize('create', Animal::class);

        $this->animalService->crear($request->toDTO(), $request->user());

        return redirect()->route('animales.index')->with('success', 'Animal creado correctamente.');
    }

    public function update(UpdateAnimalRequest $request, Animal $animale): RedirectResponse
    {
        $this->authorize('update', $animale);

        $this->animalService->actualizar($animale, $request->toDTO(), $request->user());

        return redirect()->route('animales.index')->with('success', 'Animal actualizado correctamente.');
    }

    public function destroy(Animal $animale): RedirectResponse
    {
        $this->authorize('delete', $animale);

        $this->animalService->eliminar($animale);

        return redirect()->route('animales.index')->with('success', 'Animal eliminado correctamente.');
    }

    /**
     * @return Collection<int, Granja>
     */
    private function granjasDisponibles(Request $request): Collection
    {
        $user = $request->user();

        return $user->isSuperAdmin() ? Granja::query()->get() : $user->granjas()->get();
    }

    /**
     * @return Collection<int, Terreno>
     */
    private function terrenosDisponibles(Request $request): Collection
    {
        $user = $request->user();

        return Terreno::query()
            ->when(! $user->isSuperAdmin(), fn ($q) => $q->whereIn('granja_id', $user->granjas()->pluck('farms.id')))
            ->orderBy('nombre')
            ->get();
    }
}
