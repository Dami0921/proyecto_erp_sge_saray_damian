<?php

namespace App\Listeners;

use App\Events\CompraRecibida;
use App\Notifications\CompraRecibidaNotification;

/** Cuando llega mercancía, se avisa a quien registró la orden de compra, con el PDF de la orden adjunto. */
class NotificarCompraRecibida
{
    public function handle(CompraRecibida $evento): void
    {
        $evento->compra->usuario?->notify(new CompraRecibidaNotification($evento->compra, $evento->completa));
    }
}
