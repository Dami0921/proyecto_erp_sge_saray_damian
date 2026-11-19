<?php

namespace App\Console\Commands;

use App\Services\InventarioService;
use Illuminate\Console\Command;

class InicializarKardex extends Command
{
    protected $signature = 'inventario:inicializar-kardex';

    protected $description = 'Registra como «inventario inicial» los lotes que existían antes del kardex';

    public function handle(InventarioService $inventario): int
    {
        $total = $inventario->abrirInventario();

        $this->info($total === 0
            ? 'No hay lotes pendientes: el kardex ya los tiene todos.'
            : "Se registraron {$total} lote(s) como inventario inicial.");

        return self::SUCCESS;
    }
}
