<?php

namespace App\Events;

use App\Models\Compra;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Hecho de negocio: «llegó mercancía de una orden de compra» (recepción total o parcial). */
class CompraRecibida implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public Compra $compra, public bool $completa) {}
}
