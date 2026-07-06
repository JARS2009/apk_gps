<?php

namespace App\Http\Requests\Terreno;

use App\DTOs\Terreno\CreateTerrenoDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreTerrenoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * El frontend envía `coordenadas` como un string JSON (viene de un
     * textarea). Lo decodificamos antes de validar para que la regla
     * `array` funcione sobre datos reales.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('coordenadas'))) {
            $this->merge([
                'coordenadas' => json_decode($this->input('coordenadas'), true) ?? [],
            ]);
        }
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'granja_id' => ['required', 'integer', 'exists:farms,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'coordenadas' => ['required', 'array'],
            'area' => ['nullable', 'numeric'],
        ];
    }

    public function toDTO(): CreateTerrenoDTO
    {
        return CreateTerrenoDTO::from($this->validated());
    }
}
