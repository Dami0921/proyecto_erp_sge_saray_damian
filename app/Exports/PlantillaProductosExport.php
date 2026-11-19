<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Plantilla para la importación masiva: se descarga, se llena y se vuelve a subir.
 * Los encabezados deben coincidir con las claves que espera ProductosImport.
 */
class PlantillaProductosExport implements FromArray, ShouldAutoSize, WithHeadings
{
    public function headings(): array
    {
        return ['nombre', 'categoria', 'unidad_medida', 'precio_venta', 'iva_porcentaje'];
    }

    public function array(): array
    {
        return [
            ['Abono orgánico compostado', 'Fertilizantes', 'bulto', 38000, 0],
            ['Guantes de labranza', 'Herramientas', 'par', 12000, 19],
        ];
    }
}
