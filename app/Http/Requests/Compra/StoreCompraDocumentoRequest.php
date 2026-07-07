<?php

namespace App\Http\Requests\Compra;

use App\DTOs\Compra\CreateCompraDocumentoDTO;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompraDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'tipo_documento' => ['required', 'string', 'max:50'],
            'serie_documento' => ['required', 'string', 'max:20'],
            'correlativo_documento' => ['required', 'string', 'max:20'],
            'fecha_documento' => ['nullable', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function toDTO(): CreateCompraDocumentoDTO
    {
        return CreateCompraDocumentoDTO::from($this->validated());
    }
}
