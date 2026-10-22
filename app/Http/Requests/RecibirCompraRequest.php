<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecibirCompraRequest extends FormRequest
{
    // El permiso recibir-compras se exige en el controlador
    public function authorize(): bool
    {
        return true;
    }

    // Solo formato: las reglas de negocio (más de lo pedido, vencidos, etc.) las valida CompraService en el servidor
    public function rules(): array
    {
        return [
            'recepcion' => ['required', 'array'],
            'recepcion.*.cantidad' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'recepcion.*.fecha_vencimiento' => ['nullable', 'date'],
            'recepcion.*.numero_lote' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'recepcion.required' => 'Debe ingresar al menos una cantidad mayor que cero.',
            'recepcion.*.cantidad.integer' => 'Las cantidades recibidas deben ser números enteros.',
            'recepcion.*.cantidad.min' => 'Las cantidades recibidas no pueden ser negativas.',
            'recepcion.*.fecha_vencimiento.date' => 'La fecha de vencimiento no es válida.',
        ];
    }
}
