<?php

namespace App\Imports;

use App\Imports\Concerns\LimpiaCeldas;
use App\Models\Proveedor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Carga masiva de proveedores desde Excel/CSV.
 * El NIT identifica al proveedor: si ya existe se actualizan sus datos; si no, se crea.
 * Las filas con errores se omiten y se reportan; las demás se guardan.
 */
class ProveedoresImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
{
    use LimpiaCeldas, SkipsFailures;

    public int $creados = 0;

    public int $actualizados = 0;

    public function collection(Collection $filas): void
    {
        foreach ($filas as $fila) {
            $fila = $this->normalizar($fila->toArray());
            $datos = [
                'nombre' => $fila['nombre'],
                'telefono' => $fila['telefono'],
                'direccion' => $fila['direccion'],
            ];

            $proveedor = Proveedor::where('nit', $fila['nit'])->first();

            if ($proveedor) {
                $proveedor->update($datos);
                $this->actualizados++;
            } else {
                Proveedor::create($datos + ['nit' => $fila['nit']]);
                $this->creados++;
            }
        }
    }

    public function prepareForValidation(array $datos, int $indice): array
    {
        return $this->normalizar($datos) + $datos;
    }

    private function normalizar(array $fila): array
    {
        return [
            'nombre' => $this->texto($fila['nombre'] ?? null),
            'nit' => $this->texto($fila['nit'] ?? null),
            'telefono' => $this->texto($fila['telefono'] ?? null),
            'direccion' => $this->texto($fila['direccion'] ?? null),
        ];
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'nit' => ['required', 'string', 'max:30', function (string $atributo, mixed $valor, \Closure $falla) {
                if (Proveedor::onlyTrashed()->where('nit', $valor)->exists() && ! Proveedor::where('nit', $valor)->exists()) {
                    $falla('El proveedor con NIT «'.$valor.'» está en la papelera. Restáurelo desde Proveedores.');
                }
            }],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'nit.required' => 'El NIT es obligatorio: identifica al proveedor.',
            'nit.max' => 'El NIT no puede superar los 30 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
            'direccion.max' => 'La dirección no puede superar los 200 caracteres.',
        ];
    }
}
