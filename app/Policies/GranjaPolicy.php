<?php

namespace App\Policies;

use App\Models\Granja;
use App\Models\User;

class GranjaPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Granja $granja): bool
    {
        return $user->isSuperAdmin() || $granja->usuarios()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function update(User $user, Granja $granja): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user, Granja $granja): bool
    {
        return $user->isSuperAdmin();
    }
}
