<?php

namespace App\Events;

use App\Models\Venta;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Hecho de negocio: «se registró una venta». VentaService no sabe quién reacciona (stock bajo, contabilidad...).
 * ShouldDispatchAfterCommit: el evento sale solo si la transacción se confirmó; si la venta falla y hace
 * rollback, nadie recibe una alerta falsa.
 */
class VentaRegistrada implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Venta $venta) {}
}
