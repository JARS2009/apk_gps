<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ContactoUsuario;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContactoPerfilController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tipo'  => ['required', Rule::in(['correo', 'telefono'])],
            'valor' => ['required', 'string', 'max:255'],
        ]);

        $request->user()->contactos()->create($validated);

        return back()->with('success', 'Contacto agregado correctamente.');
    }

    public function destroy(Request $request, ContactoUsuario $contacto): RedirectResponse
    {
        abort_unless($contacto->user_id === $request->user()->id, 403);

        $contacto->delete();

        return back()->with('success', 'Contacto eliminado correctamente.');
    }
}
