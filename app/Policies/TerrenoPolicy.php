<?php

namespace App\Policies;

use App\Models\Terreno;
use App\Models\User;

class TerrenoPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Terreno $terreno): bool
    {
        return $this->pertenece($user, $terreno);
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Terreno $terreno): bool
    {
        return $this->pertenece($user, $terreno);
    }

    public function delete(User $user, Terreno $terreno): bool
    {
        return $this->pertenece($user, $terreno);
    }

    private function pertenece(User $user, Terreno $terreno): bool
    {
        return $user->isSuperAdmin() || $user->granjas()->where('farms.id', $terreno->granja_id)->exists();
    }
}
