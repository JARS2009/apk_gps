<?php

namespace App\Http\Requests\Configuracion;

use App\DTOs\Configuracion\UpdateConfiguracionDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'telefono_policia' => ['nullable', 'string', 'max:30'],
            'telefono_emergencia' => ['nullable', 'string', 'max:30'],
            'mensaje_alerta' => ['nullable', 'string'],
            'alertas_activas' => ['boolean'],
        ];
    }

    public function toDTO(): UpdateConfiguracionDTO
    {
        return UpdateConfiguracionDTO::from($this->validated());
    }
}
