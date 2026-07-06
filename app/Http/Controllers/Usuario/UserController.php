<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Http\Requests\Usuario\StoreUserRequest;
use App\Http\Requests\Usuario\UpdateUserRequest;
use App\Models\Granja;
use App\Models\User;
use App\Services\Usuario\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        return Inertia::render('usuario/Index', [
            'usuarios' => $this->userService->listarPaginado($request, $request->user()),
            'filters' => $request->only('search'),
            'granjas' => $this->granjasDisponibles($request),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $this->userService->crear($request->toDTO(), $request->user());

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $this->userService->actualizar($user, $request->toDTO(), $request->user());

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->userService->eliminar($user);

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado correctamente.');
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
