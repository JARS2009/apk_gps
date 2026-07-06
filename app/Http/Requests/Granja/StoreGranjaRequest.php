<?php

namespace App\Http\Requests\Granja;

use App\DTOs\Granja\CreateGranjaDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreGranjaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'descripcion' => ['nullable', 'string'],
            'activa' => ['boolean'],
        ];
    }

    public function toDTO(): CreateGranjaDTO
    {
        return CreateGranjaDTO::from($this->validated());
    }
}
