<?php

namespace App\Listeners;

use App\Events\VentaRegistrada;
use App\Models\User;
use App\Notifications\StockBajoNotification;
use Illuminate\Support\Facades\Notification;

/**
 * Regla automática: cuando una venta deja un producto en stock bajo, se avisa a quienes compran.
 * Laravel conecta este listener con el evento por el tipo del parámetro de handle() (ver EventServiceProvider).
 */
class RevisarStockBajo
{
    public function handle(VentaRegistrada $evento): void
    {
        $umbral = config('erp.umbral_stock_bajo');
        $evento->venta->loadMissing('detalles.producto');

        // Unidades vendidas por producto (un producto puede salir de varios lotes)
        $vendidas = $evento->venta->detalles->groupBy('producto_id')->map->sum('cantidad');

        foreach ($vendidas as $productoId => $cantidad) {
            $producto = $evento->venta->detalles->firstWhere('producto_id', $productoId)?->producto;
            if (! $producto) {
                continue;
            }

            $stock = $producto->stockDisponible();
            $antes = $stock + (int) $cantidad;

            // Solo se alerta cuando el producto CRUZA el umbral (evita repetir la alerta en cada venta)
            if ($antes > $umbral && $stock <= $umbral) {
                Notification::send($this->destinatarios(), new StockBajoNotification($producto, $stock));
            }
        }
    }

    // Usuarios que pueden crear compras (por rol o por permiso directo); los eliminados no reciben nada
    private function destinatarios()
    {
        return User::permission('crear-compras')->get();
    }
}
