<?php

namespace App\Policies;

use App\Models\Collar;
use App\Models\User;

class CollarPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Collar $collar): bool
    {
        return $this->pertenece($user, $collar);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Collar $collar): bool
    {
        return $this->pertenece($user, $collar);
    }

    public function delete(User $user, Collar $collar): bool
    {
        return $this->pertenece($user, $collar);
    }

    private function pertenece(User $user, Collar $collar): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($collar->animal_id === null) {
            return true;
        }

        $granjaId = $collar->animal?->granja_id;

        return $granjaId !== null && $user->granjas()->where('farms.id', $granjaId)->exists();
    }
}
