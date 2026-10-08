<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoriaRequest extends FormRequest
{
    // Los permisos se validan en el controlador (middleware de Spatie)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required', 'string', 'max:100',
                // Nombre único entre las categorías que no están en la papelera
                Rule::unique('categorias', 'nombre')->withoutTrashed()->ignore($this->route('categoria')),
            ],
        ];
    }
}
