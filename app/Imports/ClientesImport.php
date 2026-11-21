<?php

namespace App\Imports;

use App\Imports\Concerns\LimpiaCeldas;
use App\Models\Cliente;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

/**
 * Carga masiva de clientes desde Excel/CSV (por ejemplo, la cartera de una empresa que empieza a usar el ERP).
 * El DOCUMENTO identifica al cliente: si ya existe se actualizan sus datos; si no, se crea.
 * Las filas con errores se omiten y se reportan (fila, columna y error); las demás se guardan.
 */
class ClientesImport implements SkipsOnFailure, ToCollection, WithHeadingRow, WithValidation
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
                'tipo_cliente' => $fila['tipo_cliente'],
                'telefono' => $fila['telefono'],
                'municipio' => $fila['municipio'],
            ];

            $cliente = Cliente::where('documento', $fila['documento'])->first();

            if ($cliente) {
                $cliente->update($datos);
                $this->actualizados++;
            } else {
                Cliente::create($datos + ['documento' => $fila['documento']]);
                $this->creados++;
            }
        }
    }

    /** Se aplica antes de validar: así «1234567890» numérico o «finca» en minúsculas también son válidos. */
    public function prepareForValidation(array $datos, int $indice): array
    {
        return $this->normalizar($datos) + $datos;
    }

    private function normalizar(array $fila): array
    {
        $tipo = $this->texto($fila['tipo_cliente'] ?? null);

        return [
            'nombre' => $this->texto($fila['nombre'] ?? null),
            // «finca», «FINCA» y «Finca» son el mismo tipo
            'tipo_cliente' => $tipo === null ? null : mb_convert_case($tipo, MB_CASE_TITLE),
            'documento' => $this->texto($fila['documento'] ?? null),
            'telefono' => $this->texto($fila['telefono'] ?? null),
            'municipio' => $this->texto($fila['municipio'] ?? null),
        ];
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:150'],
            'tipo_cliente' => ['required', Rule::in(Cliente::TIPOS)],
            'documento' => ['required', 'string', 'max:30', function (string $atributo, mixed $valor, \Closure $falla) {
                // Un cliente en la papelera no se duplica ni se revive en silencio: se restaura desde Clientes
                if (Cliente::onlyTrashed()->where('documento', $valor)->exists() && ! Cliente::where('documento', $valor)->exists()) {
                    $falla('El cliente con documento «'.$valor.'» está en la papelera. Restáurelo desde Clientes.');
                }
            }],
            'telefono' => ['nullable', 'string', 'max:20'],
            'municipio' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 150 caracteres.',
            'tipo_cliente.required' => 'El tipo de cliente es obligatorio.',
            'tipo_cliente.in' => 'El tipo «:input» no es válido. Use: '.implode(', ', Cliente::TIPOS).'.',
            'documento.required' => 'El documento es obligatorio: identifica al cliente.',
            'documento.max' => 'El documento no puede superar los 30 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 20 caracteres.',
            'municipio.max' => 'El municipio no puede superar los 100 caracteres.',
        ];
    }
}
