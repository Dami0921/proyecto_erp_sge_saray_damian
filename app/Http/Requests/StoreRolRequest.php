<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9_-]+$/', Rule::unique('roles', 'name')],
        ];
    }

    public function attributes(): array
    {
        return ['nombre' => 'nombre del rol'];
    }

    public function messages(): array
    {
        return [
            'nombre.regex' => 'Usa solo minúsculas, números, guion o guion bajo (ej.: contador, jefe-bodega).',
            'nombre.unique' => 'Ya existe un rol con ese nombre.',
        ];
    }
}
