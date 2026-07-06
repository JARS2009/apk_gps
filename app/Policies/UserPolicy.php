<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return $this->puedeGestionar($user, $model);
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin() || $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $this->puedeGestionar($user, $model);
    }

    public function delete(User $user, User $model): bool
    {
        return $this->puedeGestionar($user, $model);
    }

    private function puedeGestionar(User $user, User $model): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->id === $model->id) {
            return true;
        }

        $granjasActor = $user->granjas()->pluck('farms.id');
        $granjasModel = $model->granjas()->pluck('farms.id');

        return $granjasActor->intersect($granjasModel)->isNotEmpty();
    }
}
