<?php

namespace App\Services;

use App\Models\Lot;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DispensingService
{
    /**
     * Descuenta unidades de un producto aplicando FEFO.
     * Devuelve el detalle de qué lote aportó cuántas unidades.
     *
     * @return array<int, array{lot_id: int, lot_number: string, quantity: int}>
     */
    public function dispense(Product $product, int $quantity): array
    {
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => 'La cantidad debe ser mayor a cero.',
            ]);
        }

        return DB::transaction(function () use ($product, $quantity) {
            // Solo lotes vigentes con existencias, el que vence antes primero.
            $lots = Lot::where('product_id', $product->id)
                ->whereDate('expiration_date', '>=', today())
                ->where('quantity', '>', 0)
                ->orderBy('expiration_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $available = $lots->sum('quantity');

            if ($available < $quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Stock insuficiente: disponible {$available}, solicitado {$quantity}.",
                ]);
            }

            $remaining = $quantity;
            $taken = [];

            foreach ($lots as $lot) {
                if ($remaining === 0) {
                    break;
                }

                $take = min($lot->quantity, $remaining);
                $lot->decrement('quantity', $take);
                $remaining -= $take;

                $taken[] = [
                    'lot_id' => $lot->id,
                    'lot_number' => $lot->lot_number,
                    'quantity' => $take,
                ];
            }

            $product->decrement('stock', $quantity);

            return $taken;
        });
    }
}
