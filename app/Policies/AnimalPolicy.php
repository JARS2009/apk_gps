<?php

namespace App\Policies;

use App\Models\Animal;
use App\Models\User;

class AnimalPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Animal $animal): bool
    {
        return $this->pertenece($user, $animal);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Animal $animal): bool
    {
        return $this->pertenece($user, $animal);
    }

    public function delete(User $user, Animal $animal): bool
    {
        return $this->pertenece($user, $animal);
    }

    private function pertenece(User $user, Animal $animal): bool
    {
        return $user->isSuperAdmin() || $user->granjas()->where('farms.id', $animal->granja_id)->exists();
    }
}
