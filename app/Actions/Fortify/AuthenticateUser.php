<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthenticateUser
{
    /**
     * Attempt to authenticate a user using the given credentials.
     * Accepts either email or num_doc as the login identifier.
     */
    public function authenticate(Request $request): ?User
    {
        $login = $request->input('email');
        $password = $request->input('password');

        if (! $login || ! $password) {
            return null;
        }

        // Determine if the input looks like an email
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'num_doc';

        /** @var User|null $user */
        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            return null;
        }

        return $user;
    }
}
