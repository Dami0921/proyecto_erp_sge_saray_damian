<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarProductosRequest extends FormRequest
{
    // El permiso importar-productos ya lo exige el middleware del controlador
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Solo Excel o CSV y máximo 2 MB
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'archivo.required' => 'Seleccione el archivo que desea importar.',
            'archivo.file' => 'El archivo no se pudo subir. Inténtelo de nuevo.',
            'archivo.mimes' => 'El archivo debe ser .xlsx, .xls o .csv.',
            'archivo.max' => 'El archivo no puede pesar más de 2 MB.',
        ];
    }
}
