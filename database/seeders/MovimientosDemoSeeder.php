<?php

namespace Database\Seeders;

use App\Models\Compra;
use App\Models\Producto;
use App\Models\Proveedor;
use App\Models\User;
use App\Models\Venta;
use App\Services\CompraService;
use App\Services\VentaService;
use Illuminate\Database\Seeder;

/**
 * Movimientos de ejemplo para que el Dashboard (KPIs y Top 5), los PDF y las listas tengan datos
 * desde el primer inicio de sesión. Usa los MISMOS servicios que la aplicación (con sus reglas y
 * transacciones). Requiere haber ejecutado antes los demás seeders. No se debe repetir sobre una BD ya sembrada.
 */
class MovimientosDemoSeeder extends Seeder
{
    public function run(CompraService $compras, VentaService $ventas): void
    {
        if (Compra::withTrashed()->exists() || Venta::withTrashed()->exists()) {
            return; // ya hay movimientos: no duplicar
        }

        $admin = User::where('email', 'admin@test.com')->firstOrFail();
        $p = Producto::orderBy('id')->get()->keyBy('nombre');
        $prov = Proveedor::orderBy('id')->get();
        $vence = now()->addMonths(8)->toDateString();

        // IVA 19 % en herramientas y riego (los demás quedan sin IVA) para mostrar ambos casos
        Producto::whereHas('categoria', fn ($q) => $q->whereIn('nombre', ['Herramientas', 'Riego']))->update(['iva_porcentaje' => 19]);

        // --- Compras -------------------------------------------------------
        // OC-00001: recibida completa
        $oc1 = $compras->registrar([
            'proveedor_id' => $prov[0]->id, 'fecha' => today()->toDateString(), 'observaciones' => 'Reposición mensual de agroquímicos',
            'items' => [
                ['producto_id' => $p['Glifosato 480']->id, 'cantidad' => 40, 'costo_unitario' => 41000],
                ['producto_id' => $p['Insecticida sistémico']->id, 'cantidad' => 30, 'costo_unitario' => 39000],
            ],
        ], $admin->id);
        $compras->recibir($oc1, $oc1->detalles->mapWithKeys(fn ($d) => [$d->id => [
            'cantidad' => $d->cantidad, 'fecha_vencimiento' => $vence, 'numero_lote' => null,
        ]])->all());

        // OC-00002: recepción PARCIAL (llegó la mitad del primer producto, nada del segundo)
        $oc2 = $compras->registrar([
            'proveedor_id' => $prov[2]->id, 'fecha' => today()->toDateString(),
            'items' => [
                ['producto_id' => $p['Urea 46%']->id, 'cantidad' => 60, 'costo_unitario' => 70000],
                ['producto_id' => $p['Fertilizante triple 15']->id, 'cantidad' => 40, 'costo_unitario' => 75000],
            ],
        ], $admin->id);
        $primera = $oc2->detalles->first();
        $compras->recibir($oc2, [$primera->id => ['cantidad' => 30, 'fecha_vencimiento' => $vence, 'numero_lote' => null]]);

        // OC-00003: pendiente (aún no llega nada)
        $compras->registrar([
            'proveedor_id' => $prov[1]->id, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p['Semilla de maíz híbrido']->id, 'cantidad' => 50, 'costo_unitario' => 12000]],
        ], $admin->id);

        // --- Ventas --------------------------------------------------------
        $clientes = \App\Models\Cliente::orderBy('id')->get();
        $vender = fn (int $cli, array $items) => $ventas->registrar([
            'cliente_id' => $clientes[$cli]->id, 'fecha' => today()->toDateString(),
            'items' => collect($items)->map(fn ($c, $n) => ['producto_id' => $p[$n]->id, 'cantidad' => $c])->values()->all(),
        ], $admin->id);

        $vender(0, ['Semilla de frijol' => 12, 'Azadón reforzado' => 3]);
        $vender(1, ['Urea 46%' => 10, 'Semilla de maíz híbrido' => 20]);
        $vender(2, ['Aspersor giratorio' => 6, 'Manguera de riego 25m' => 4]);
        $vender(3, ['Fertilizante triple 15' => 15, 'Urea 46%' => 5]);
        $vender(4, ['Machete forjado 18"' => 8]);
        $vender(5, ['Semilla de maíz híbrido' => 25, 'Semilla de frijol' => 10]);
        // Deja a Glifosato con poco stock (aparece en «Productos para reabastecer»)
        $vender(6, ['Glifosato 480' => 135]);

        // Una venta anulada (el stock vuelve a los lotes y la venta queda en la papelera)
        $anulable = $vender(7, ['Insecticida sistémico' => 5]);
        $ventas->anular($anulable);
    }
}
