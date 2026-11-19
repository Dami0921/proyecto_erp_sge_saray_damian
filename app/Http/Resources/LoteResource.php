<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numero_lote' => $this->numero_lote,
            'producto' => $this->whenLoaded('producto', fn () => [
                'id' => $this->producto?->id,
                'nombre' => $this->producto?->nombre,
            ]),
            'fecha_vencimiento' => $this->fecha_vencimiento->format('Y-m-d'),
            'dias_para_vencer' => (int) today()->diffInDays($this->fecha_vencimiento, false),
            'cantidad_actual' => (int) $this->cantidad_actual,
        ];
    }
}
