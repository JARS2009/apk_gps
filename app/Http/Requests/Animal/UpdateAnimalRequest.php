<?php

namespace App\Http\Requests\Animal;

use App\DTOs\Animal\UpdateAnimalDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        // La ruta resource 'animales' genera el segmento {animale}.
        /** @var \App\Models\Animal $animal */
        $animal = $this->route('animale');

        return [
            'granja_id' => ['required', 'integer', 'exists:farms,id'],
            'nombre' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'string', 'max:100', Rule::unique('animals', 'codigo')->ignore($animal)],
            'tipo' => ['nullable', 'string', 'max:100'],
            'raza' => ['nullable', 'string', 'max:100'],
            'terreno_ids' => ['nullable', 'array'],
            'terreno_ids.*' => ['integer', 'exists:lands,id'],
        ];
    }

    public function toDTO(): UpdateAnimalDTO
    {
        return UpdateAnimalDTO::from($this->validated());
    }
}
