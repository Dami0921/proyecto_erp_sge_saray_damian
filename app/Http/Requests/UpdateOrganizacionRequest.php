<?php

namespace App\Http\Requests;

use App\Models\Organizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrganizacionRequest extends FormRequest
{
    // El permiso (gestionar-organizacion) se exige en la ruta
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'nit' => ['nullable', 'string', 'max:30'],
            'direccion' => ['nullable', 'string', 'max:160'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:120'],
            'eslogan' => ['nullable', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:300'],
            'color' => ['required', Rule::in(array_keys(Organizacion::PALETAS))],
            // Solo png/jpg: el SVG puede traer scripts y DomPDF no lee webp
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:300'],
            'quitar_logo' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nombre' => 'nombre de la empresa',
            'nit' => 'NIT',
            'direccion' => 'dirección',
            'telefono' => 'teléfono',
            'email' => 'correo',
            'descripcion' => 'mensaje de bienvenida',
        ];
    }

    public function messages(): array
    {
        return [
            'logo.mimes' => 'El logo debe ser una imagen PNG o JPG.',
            'logo.max' => 'El logo no puede pesar más de 300 KB.',
            'color.in' => 'Elige uno de los colores disponibles.',
        ];
    }
}
