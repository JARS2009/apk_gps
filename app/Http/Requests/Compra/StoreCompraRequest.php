<?php

namespace App\Http\Requests\Compra;

use App\DTOs\Compra\CreateCompraDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompraRequest extends FormRequest
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
            'serie' => ['required', 'string', 'max:20'],
            'correlativo' => ['required', 'string', 'max:20'],
            'proveedor' => ['nullable', 'string', 'max:255'],
            'fecha' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDTO(): CreateCompraDTO
    {
        return CreateCompraDTO::from($this->validated());
    }
}
