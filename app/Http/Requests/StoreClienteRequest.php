<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClienteRequest extends FormRequest
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
            'tipo_cliente' => ['required', Rule::in(Cliente::TIPOS)],
            // Documento único entre clientes activos; al editar se ignora el propio registro
            'documento' => [
                'nullable', 'string', 'max:30',
                Rule::unique('clientes', 'documento')
                    ->ignore($this->route('cliente'))
                    ->whereNull('deleted_at'),
            ],
            'telefono' => ['nullable', 'string', 'max:20'],
            'municipio' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'tipo_cliente' => 'tipo de cliente',
            'documento' => 'documento',
            'telefono' => 'teléfono',
            'municipio' => 'municipio',
        ];
    }
}
