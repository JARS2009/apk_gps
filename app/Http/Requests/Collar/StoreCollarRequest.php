<?php

namespace App\Http\Requests\Collar;

use App\DTOs\Collar\CreateCollarDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreCollarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'serie' => ['required', 'string', 'max:100', 'unique:collars,serie'],
            'modelo' => ['required', 'string', 'max:100'],
            'imei' => ['nullable', 'string', 'max:20', 'unique:collars,imei'],
            'animal_id' => ['nullable', 'integer', 'exists:animals,id'],
            'estado' => ['nullable', new Enum(\App\Enums\CollarEstado::class)],
        ];
    }

    public function toDTO(): CreateCollarDTO
    {
        return CreateCollarDTO::from($this->validated());
    }
}
