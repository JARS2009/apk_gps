<?php

namespace App\Http\Controllers\Usuario;

use App\Http\Controllers\Controller;
use App\Models\ContactoUsuario;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactoUsuarioController extends Controller
{
    public function store(Request $request, User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);

        $validated = $request->validate([
            'tipo'  => ['required', Rule::in(['correo', 'telefono'])],
            'valor' => ['required', 'string', 'max:255'],
        ]);

        $usuario->contactos()->create($validated);

        return back()->with('success', 'Contacto agregado correctamente.');
    }

    public function destroy(User $usuario, ContactoUsuario $contacto): RedirectResponse
    {
        $this->authorize('update', $usuario);

        abort_unless($contacto->user_id === $usuario->id, 403);

        $contacto->delete();

        return back()->with('success', 'Contacto eliminado correctamente.');
    }
}
