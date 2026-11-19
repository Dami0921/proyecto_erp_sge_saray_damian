<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Producto;
use App\Services\InventarioService;

class LoteSeeder extends Seeder
{
    public function run(InventarioService $inventario): void
    {
        $productos = Producto::all();

        foreach ($productos as $producto) {
            $inventario->ingresarLote([
                'producto_id' => $producto->id,
                'numero_lote' => 'L-' . str_pad($producto->id, 4, '0', STR_PAD_LEFT),
                'fecha_ingreso' => now()->subDays(10),
                'fecha_vencimiento' => now()->addMonths(6),
                'cantidad_inicial' => 100,
                'cantidad_actual' => 100,
                'costo_unitario' => 15000,
            ], 'inventario_inicial', null, 'Saldo inicial del lote');
        }
    }
}
