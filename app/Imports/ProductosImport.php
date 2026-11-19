<?php

namespace App\Imports;

use App\Models\Categoria;
use App\Models\Producto;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Importación masiva de productos desde Excel/CSV.
 *  - WithHeadingRow: la primera fila trae los nombres de columna.
 *  - WithValidation: cada fila se valida con reglas de Laravel.
 *  - SkipsOnFailure + SkipsFailures: las filas con errores se OMITEN y se reportan (fila, columna y error);
 *    las demás se guardan.
 * Regla de negocio: si el producto ya existe (mismo nombre) se actualizan categoría, unidad, precio e IVA.
 * El archivo NUNCA trae existencias: el stock solo entra por compras y ajustes (kardex).
 */
class ProductosImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    public int $creados = 0;

    public int $actualizados = 0;

    public function collection(Collection $filas): void
    {
        foreach ($filas as $fila) {
            $categoria = Categoria::where('nombre', trim((string) $fila['categoria']))->firstOrFail();
            $nombre = trim((string) $fila['nombre']);
            $datos = [
                'categoria_id' => $categoria->id,
                'unidad_medida' => trim((string) $fila['unidad_medida']),
                'precio_venta' => $fila['precio_venta'],
                'iva_porcentaje' => $fila['iva_porcentaje'] ?? 0,
            ];

            $producto = Producto::where('nombre', $nombre)->first();

            if ($producto) {
                $producto->update($datos);
                $this->actualizados++;
            } else {
                Producto::create($datos + ['nombre' => $nombre]);
                $this->creados++;
            }
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'categoria' => ['required', Rule::exists('categorias', 'nombre')->whereNull('deleted_at')],
            'unidad_medida' => ['required', 'string', 'max:30'],
            'precio_venta' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'iva_porcentaje' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'categoria.required' => 'La categoría es obligatoria.',
            'categoria.exists' => 'La categoría «:input» no existe. Créela primero o corrija el nombre.',
            'unidad_medida.required' => 'La unidad de medida es obligatoria.',
            'precio_venta.required' => 'El precio de venta es obligatorio.',
            'precio_venta.numeric' => 'El precio de venta debe ser un número.',
            'precio_venta.min' => 'El precio de venta no puede ser negativo.',
            'iva_porcentaje.numeric' => 'El IVA debe ser un número (0 si no lleva IVA).',
            'iva_porcentaje.max' => 'El IVA no puede superar el 100 %.',
        ];
    }
}
