<?php

namespace App\Http\Requests\Usuario;

use App\DTOs\Usuario\UpdateUserDTO;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Validator;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $model = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($model)],
            'num_doc' => ['nullable', 'string', 'max:20', Rule::unique('users', 'num_doc')->ignore($model)],
            'password' => ['nullable', 'string', 'min:8'],
            'role' => ['required', new Enum(UserRole::class)],
            'granja_ids' => ['nullable', 'array'],
            'granja_ids.*' => ['integer', 'exists:farms,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $actor = $this->user();

            if (! $actor) {
                return;
            }

            if ($actor->isAdmin() && $this->input('role') === UserRole::SuperAdmin->value) {
                $validator->errors()->add('role', 'No tienes permisos para asignar el rol de super administrador.');
            }

            if ($actor->isAdmin() && ! empty($this->input('granja_ids'))) {
                $granjasActor = $actor->granjas()->pluck('farms.id')->all();
                $solicitadas = $this->input('granja_ids', []);

                if (array_diff($solicitadas, $granjasActor)) {
                    $validator->errors()->add('granja_ids', 'Solo puedes asignar granjas a las que perteneces.');
                }
            }
        });
    }

    public function toDTO(): UpdateUserDTO
    {
        return UpdateUserDTO::from($this->validated());
    }
}
