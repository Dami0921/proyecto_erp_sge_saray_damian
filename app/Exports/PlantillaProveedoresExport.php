<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/** Plantilla de la carga masiva de proveedores: los encabezados deben coincidir con ProveedoresImport. */
class PlantillaProveedoresExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return ['nombre', 'nit', 'telefono', 'direccion'];
    }

    public function array(): array
    {
        return [
            ['Agroquímicos del Sumapaz S.A.S.', '900765432-1', '3201112233', 'Calle 8 # 4-15, Fusagasugá'],
            ['Semillas Andinas Ltda.', '800123789-5', '3157778899', 'Km 3 vía Bogotá'],
        ];
    }
}
