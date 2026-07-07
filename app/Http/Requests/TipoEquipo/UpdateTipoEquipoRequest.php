<?php

namespace App\Http\Requests\TipoEquipo;

use App\DTOs\TipoEquipo\UpdateTipoEquipoDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTipoEquipoRequest extends FormRequest
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
            'nombre' => ['required', 'string', 'max:150'],
            'prefijo_codigo' => ['required', 'string', 'max:20'],
            'estado' => ['nullable', 'boolean'],
        ];
    }

    public function toDTO(): UpdateTipoEquipoDTO
    {
        return UpdateTipoEquipoDTO::from($this->validated());
    }
}
