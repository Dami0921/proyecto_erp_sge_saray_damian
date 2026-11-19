<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Define QUÉ datos de un producto salen por la API y con qué nombres (no se expone deleted_at ni columnas internas).
 */
class ProductoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $stock = (int) ($this->stock ?? $this->stockDisponible());

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'unidad_medida' => $this->unidad_medida,
            'precio_venta' => (float) $this->precio_venta,
            'iva_porcentaje' => (float) $this->iva_porcentaje,
            'stock' => $stock, // solo lotes vigentes (sin vencer)
            'disponible' => $stock > 0,
            'categoria' => $this->whenLoaded('categoria', fn () => [
                'id' => $this->categoria?->id,
                'nombre' => $this->categoria?->nombre,
            ]),
        ];
    }
}
