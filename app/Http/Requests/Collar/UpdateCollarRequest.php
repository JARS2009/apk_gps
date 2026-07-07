<?php

namespace App\Http\Requests\Collar;

use App\DTOs\Collar\UpdateCollarDTO;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateCollarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $collar = $this->route('collar');

        return [
            'serie' => ['required', 'string', 'max:100', Rule::unique('collars', 'serie')->ignore($collar)],
            'modelo' => ['required', 'string', 'max:100'],
            'animal_id' => ['nullable', 'integer', 'exists:animals,id'],
            'estado' => ['nullable', new Enum(\App\Enums\CollarEstado::class)],
        ];
    }

    public function toDTO(): UpdateCollarDTO
    {
        return UpdateCollarDTO::from($this->validated());
    }
}
