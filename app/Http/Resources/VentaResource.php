<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'factura' => $this->numero_factura,
            'fecha' => $this->fecha?->format('Y-m-d'),
            'estado' => $this->estado,
            'cliente' => $this->cliente ? [
                'id' => $this->cliente->id,
                'nombre' => $this->cliente->nombre,
                'documento' => $this->cliente->documento,
            ] : null, // null = consumidor final
            'vendedor' => $this->usuario?->name,
            'detalle' => $this->whenLoaded('detalles', fn () => $this->detalles->map(fn ($d) => [
                'producto_id' => $d->producto_id,
                'producto' => $d->producto?->nombre,
                'lote' => $d->lote?->numero_lote,
                'cantidad' => (int) $d->cantidad,
                'precio_unitario' => (float) $d->precio_unitario,
                'iva_porcentaje' => (float) $d->iva_porcentaje,
                'subtotal' => (float) $d->subtotal,
            ])),
            'subtotal' => (float) $this->subtotal,
            'iva' => (float) $this->iva,
            'total' => (float) $this->total,
        ];
    }
}
