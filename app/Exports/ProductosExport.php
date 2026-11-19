<?php

namespace App\Exports;

use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Exporta el catálogo de productos a Excel, con el stock vigente (lotes sin vencer).
 * FromQuery: lee de la BD por partes (sirve con miles de registros sin agotar la memoria).
 */
class ProductosExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(private ?string $buscar = null) {}

    public function query(): Builder
    {
        return Producto::query()
            ->with('categoria')
            ->conStock()
            ->when($this->buscar, fn ($q) => $q->where('nombre', 'like', "%{$this->buscar}%"))
            ->orderBy('nombre');
    }

    public function headings(): array
    {
        return ['ID', 'Nombre', 'Categoría', 'Unidad', 'Precio de venta', 'IVA %', 'Stock vigente', 'Valor del inventario'];
    }

    public function map(mixed $producto): array
    {
        $stock = (int) $producto->stock;

        return [
            $producto->id,
            $producto->nombre,
            $producto->categoria?->nombre,
            $producto->unidad_medida,
            (float) $producto->precio_venta,
            (float) $producto->iva_porcentaje,
            $stock,
            round((float) $producto->precio_venta * $stock, 2),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]]; // encabezado en negrita
    }
}
