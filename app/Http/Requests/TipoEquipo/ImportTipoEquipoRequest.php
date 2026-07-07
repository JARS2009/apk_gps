<?php

namespace App\Http\Requests\TipoEquipo;

use Illuminate\Foundation\Http\FormRequest;

class ImportTipoEquipoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'granja_id' => ['required', 'integer', 'exists:farms,id'],
            'tipos' => ['required', 'array', 'min:1'],
            'tipos.*.nombre' => ['required', 'string', 'max:150'],
            'tipos.*.prefijo_codigo' => ['required', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipos.required' => 'No se encontraron tipos de equipo para importar.',
            'tipos.min' => 'Debe haber al menos un tipo de equipo para importar.',
        ];
    }
}
