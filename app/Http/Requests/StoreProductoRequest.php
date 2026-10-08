<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductoRequest extends FormRequest
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
            // La categoría debe existir y NO estar en la papelera
            'categoria_id' => ['required', Rule::exists('categorias', 'id')->whereNull('deleted_at')],
            'proveedor_id' => ['required', 'exists:proveedores,id'],
            'unidad_medida' => ['required', 'string', 'max:30'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'categoria_id' => 'categoría',
            'proveedor_id' => 'proveedor',
            'unidad_medida' => 'unidad de medida',
        ];
    }
}
