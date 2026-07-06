<?php

namespace App\Http\Requests\Animal;

use App\DTOs\Animal\CreateAnimalDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalRequest extends FormRequest
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
            'codigo' => ['required', 'string', 'max:100', 'unique:animals,codigo'],
            'tipo' => ['nullable', 'string', 'max:100'],
            'raza' => ['nullable', 'string', 'max:100'],
            'terreno_ids' => ['nullable', 'array'],
            'terreno_ids.*' => ['integer', 'exists:lands,id'],
        ];
    }

    public function toDTO(): CreateAnimalDTO
    {
        return CreateAnimalDTO::from($this->validated());
    }
}
