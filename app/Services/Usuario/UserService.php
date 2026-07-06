<?php

namespace App\Services\Usuario;

use App\DTOs\Usuario\CreateUserDTO;
use App\DTOs\Usuario\UpdateUserDTO;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * @return LengthAwarePaginator<int, User>
     */
    public function listarPaginado(Request $request, User $actor): LengthAwarePaginator
    {
        return User::query()
            ->with('granjas')
            ->when(! $actor->isSuperAdmin(), fn ($q) => $q->whereHas(
                'granjas',
                fn ($granjas) => $granjas->whereIn('farms.id', $actor->granjas()->pluck('farms.id'))
            ))
            ->when($request->search, fn ($q, $search) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();
    }

    public function crear(CreateUserDTO $dto, User $actor): User
    {
        return DB::transaction(function () use ($dto, $actor) {
            $role = $actor->isAdmin() ? UserRole::Admin : $dto->role;
            $granjaIds = $this->resolverGranjas($dto, $actor);

            $user = User::create([
                'name' => $dto->name,
                'email' => $dto->email,
                'num_doc' => $dto->num_doc,
                'password' => Hash::make($dto->password ?? str()->random(16)),
                'role' => $role,
            ]);

            $user->granjas()->sync($granjaIds);

            return $user;
        });
    }

    public function actualizar(User $user, UpdateUserDTO $dto, User $actor): User
    {
        return DB::transaction(function () use ($user, $dto, $actor) {
            $role = $actor->isAdmin() ? UserRole::Admin : $dto->role;
            $granjaIds = $this->resolverGranjas($dto, $actor);

            $user->update([
                'name' => $dto->name,
                'email' => $dto->email,
                'num_doc' => $dto->num_doc,
                'role' => $role,
                ...($dto->password ? ['password' => Hash::make($dto->password)] : []),
            ]);

            $user->granjas()->sync($granjaIds);

            return $user;
        });
    }

    public function eliminar(User $user): void
    {
        $user->delete();
    }

    /**
     * @return array<int, int>
     */
    private function resolverGranjas(CreateUserDTO|UpdateUserDTO $dto, User $actor): array
    {
        if ($actor->isSuperAdmin()) {
            return $dto->granja_ids;
        }

        $granjasActor = $actor->granjas()->pluck('farms.id')->all();

        if (! empty($dto->granja_ids)) {
            $seleccionadas = array_values(array_intersect($dto->granja_ids, $granjasActor));

            if (count($seleccionadas) !== count($dto->granja_ids)) {
                throw ValidationException::withMessages([
                    'granja_ids' => 'No puedes asignar granjas a las que no perteneces.',
                ]);
            }

            return $seleccionadas;
        }

        if (count($granjasActor) === 1) {
            return $granjasActor;
        }

        return [];
    }
}
