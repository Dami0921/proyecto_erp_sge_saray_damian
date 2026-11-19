<?php

namespace App\Exports;

use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Ventas de un periodo (para el contador o para analizar en Excel). Incluye las anuladas, marcadas como tales.
 */
class VentasExport implements FromQuery, ShouldAutoSize, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private ?string $desde = null, private ?string $hasta = null)
    {
    }

    public function query(): Builder
    {
        return Venta::withTrashed()
            ->with(['cliente', 'usuario'])
            ->when($this->desde, fn ($q) => $q->whereDate('fecha', '>=', $this->desde))
            ->when($this->hasta, fn ($q) => $q->whereDate('fecha', '<=', $this->hasta))
            ->orderBy('fecha')
            ->orderBy('id');
    }

    public function headings(): array
    {
        return ['Comprobante', 'Fecha', 'Cliente', 'Documento', 'Vendedor', 'Subtotal', 'IVA', 'Total', 'Estado'];
    }

    public function map(mixed $venta): array
    {
        return [
            $venta->numero_factura,
            $venta->fecha->format('Y-m-d'),
            $venta->cliente?->nombre ?? 'Consumidor final',
            $venta->cliente?->documento,
            $venta->usuario?->name,
            (float) $venta->subtotal,
            (float) $venta->iva,
            (float) $venta->total,
            $venta->estado,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }

    public function title(): string
    {
        return 'Ventas';
    }
}
