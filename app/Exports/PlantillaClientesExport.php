<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Plantilla de la carga masiva de clientes: los encabezados deben coincidir con ClientesImport. */
class PlantillaClientesExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return ['nombre', 'tipo_cliente', 'documento', 'telefono', 'municipio'];
    }

    public function array(): array
    {
        return [
            ['Finca La Esperanza', 'Finca', '900123456-7', '3001234567', 'Fusagasugá'],
            ['Juan Pérez', 'Natural', '1012345678', '3109876543', 'Silvania'],
        ];
    }
}
