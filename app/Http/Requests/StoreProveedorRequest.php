<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProveedorRequest extends FormRequest
{
    // Los permisos se validan en el controlador (middleware de Spatie)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            // NIT único entre proveedores activos; al editar se ignora el propio registro
            'nit' => [
                'required', 'string', 'max:30',
                Rule::unique('proveedores', 'nit')
                    ->ignore($this->route('proveedor'))
                    ->whereNull('deleted_at'),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'nit' => 'NIT',
            'telefono' => 'teléfono',
            'direccion' => 'dirección',
        ];
    }

    public function messages(): array
    {
        return [
            'nit.unique' => 'Ya existe un proveedor con ese NIT.',
        ];
    }
}
