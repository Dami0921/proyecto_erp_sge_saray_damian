<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaRequest extends FormRequest
{
    // Los permisos se validan en el controlador (middleware de Spatie)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Opcional: sin cliente se registra como "Consumidor final"
            'cliente_id' => ['nullable', Rule::exists('clientes', 'id')->whereNull('deleted_at')],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.producto_id' => ['required', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'cliente',
            'fecha' => 'fecha',
            'observaciones' => 'observaciones',
            'items' => 'productos',
            'items.*.producto_id' => 'producto',
            'items.*.cantidad' => 'cantidad',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe agregar al menos un producto a la venta.',
            'items.min' => 'Debe agregar al menos un producto a la venta.',
            'items.*.producto_id.required' => 'Seleccione un producto en cada fila.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser mayor a cero.',
            'items.*.cantidad.integer' => 'La cantidad de cada producto debe ser un número entero.',
            'fecha.before_or_equal' => 'La fecha de la venta no puede ser futura.',
        ];
    }
}
