<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAjusteRequest extends FormRequest
{
    // El permiso ajustar-kardex ya lo exige el middleware del controlador
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // El lote debe pertenecer al producto que se está ajustando
            'lote_id' => ['required', 'integer', Rule::exists('lotes', 'id')->where('producto_id', $this->route('producto')->id)],
            'cantidad_contada' => ['required', 'integer', 'min:0'],
            // Regla: todo ajuste debe explicar por qué se hace (auditoría)
            'motivo' => ['required', 'string', 'min:10', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'lote_id.required' => 'Elija el lote que va a ajustar.',
            'lote_id.exists' => 'El lote elegido no pertenece a este producto.',
            'cantidad_contada.required' => 'Digite la cantidad contada en bodega.',
            'cantidad_contada.integer' => 'La cantidad contada debe ser un número entero.',
            'cantidad_contada.min' => 'La cantidad contada no puede ser negativa.',
            'motivo.required' => 'El motivo del ajuste es obligatorio.',
            'motivo.min' => 'El motivo del ajuste debe tener al menos 10 caracteres.',
            'motivo.max' => 'El motivo del ajuste no puede superar los 255 caracteres.',
        ];
    }
}
