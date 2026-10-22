<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCompraRequest extends FormRequest
{
    // Los permisos se validan en el controlador (middleware de Spatie)
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Opcional: permite compras aunque el módulo Proveedores esté apagado
            'proveedor_id' => ['nullable', Rule::exists('proveedores', 'id')->whereNull('deleted_at')],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            // distinct: un producto no puede repetirse en la misma orden
            'items.*.producto_id' => ['required', 'distinct', Rule::exists('productos', 'id')->whereNull('deleted_at')],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.costo_unitario' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
        ];
    }

    public function attributes(): array
    {
        return [
            'proveedor_id' => 'proveedor',
            'fecha' => 'fecha',
            'observaciones' => 'observaciones',
            'items' => 'productos',
            'items.*.producto_id' => 'producto',
            'items.*.cantidad' => 'cantidad',
            'items.*.costo_unitario' => 'costo unitario',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Debe agregar al menos un producto a la orden.',
            'items.min' => 'Debe agregar al menos un producto a la orden.',
            'items.*.producto_id.required' => 'Seleccione un producto en cada fila.',
            'items.*.producto_id.distinct' => 'Hay productos repetidos: se debe usar una sola fila por producto.',
            'items.*.cantidad.min' => 'La cantidad de cada producto debe ser mayor a cero.',
            'items.*.cantidad.integer' => 'La cantidad de cada producto debe ser un número entero.',
            'items.*.costo_unitario.min' => 'El costo unitario debe ser mayor a cero.',
            'fecha.before_or_equal' => 'La fecha de la orden no puede ser futura.',
        ];
    }
}
