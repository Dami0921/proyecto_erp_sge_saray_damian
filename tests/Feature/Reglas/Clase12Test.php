<?php

namespace Tests\Feature\Reglas;

use App\Models\Compra;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\User;
use App\Services\CompraService;
use App\Services\InventarioService;
use App\Services\SeguridadService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Clase 12: auditoría (activitylog) y kardex de inventario. Las existencias solo cambian por movimientos.
 */
class Clase12Test extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    /** Crea un lote entrando por el kardex (inventario inicial). */
    private function loteConKardex(Producto $p, int $cantidad, string $numero = 'L-1'): Lote
    {
        $mov = app(InventarioService::class)->ingresarLote([
            'producto_id' => $p->id, 'numero_lote' => $numero, 'fecha_ingreso' => today(),
            'fecha_vencimiento' => today()->addMonths(6)->toDateString(),
            'cantidad_inicial' => $cantidad, 'cantidad_actual' => $cantidad, 'costo_unitario' => 5000,
        ], 'inventario_inicial');

        return $mov->lote;
    }

    private function vender(Producto $p, int $cantidad)
    {
        return app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => $cantidad]],
        ], $this->vendedor->id);
    }

    /** K1. Una venta deja una salida en el kardex con el saldo y el documento. */
    public function test_la_venta_deja_una_salida_en_el_kardex(): void
    {
        $p = $this->producto();
        $this->loteConKardex($p, 10);

        $venta = $this->vender($p, 4);

        $mov = MovimientoInventario::where('concepto', 'venta')->firstOrFail();
        $this->assertSame('salida', $mov->tipo);
        $this->assertSame(4, $mov->cantidad);
        $this->assertSame(6, $mov->saldo);
        $this->assertSame($venta->numero_factura, $mov->documento());
    }

    /** K2. Anular una venta devuelve el stock y deja una entrada «anulacion_venta». */
    public function test_anular_una_venta_deja_una_entrada_en_el_kardex(): void
    {
        $p = $this->producto();
        $this->loteConKardex($p, 10);
        $venta = $this->vender($p, 4);

        app(VentaService::class)->anular($venta);

        $mov = MovimientoInventario::where('concepto', 'anulacion_venta')->firstOrFail();
        $this->assertSame('entrada', $mov->tipo);
        $this->assertSame(10, $mov->saldo);
        $this->assertSame(10, $this->stock($p));
    }

    /** K3. Recibir una compra crea el lote y deja una entrada «compra». */
    public function test_recibir_una_compra_deja_una_entrada_en_el_kardex(): void
    {
        $p = $this->producto();
        $service = app(CompraService::class);
        $compra = $service->registrar([
            'proveedor_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 20, 'costo_unitario' => 7000]],
        ], $this->almacenista->id);

        $detalle = $compra->detalles->first();
        $service->recibir($compra, [$detalle->id => [
            'cantidad' => 20, 'fecha_vencimiento' => today()->addYear()->toDateString(), 'numero_lote' => null,
        ]]);

        $mov = MovimientoInventario::where('concepto', 'compra')->firstOrFail();
        $this->assertSame('entrada', $mov->tipo);
        $this->assertSame(20, $mov->saldo);
        $this->assertSame(Compra::first()->numero_orden, $mov->documento());
        $this->assertNotNull($mov->lote_id);
    }

    /** K4. El saldo del kardex siempre cuadra con la suma de los lotes. */
    public function test_el_saldo_del_kardex_cuadra_con_los_lotes(): void
    {
        $p = $this->producto();
        $this->loteConKardex($p, 10, 'L-A');
        $this->loteConKardex($p, 5, 'L-B');
        $venta = $this->vender($p, 12);
        app(VentaService::class)->anular($venta);
        $this->vender($p, 3);

        $ultimo = MovimientoInventario::where('producto_id', $p->id)->orderByDesc('id')->value('saldo');
        $this->assertSame($this->stock($p), $ultimo);
        $this->assertSame(12, $ultimo);
    }

    /** K5. Un ajuste exige motivo: sin él, no se registra nada. */
    public function test_el_ajuste_exige_un_motivo(): void
    {
        $p = $this->producto();
        $lote = $this->loteConKardex($p, 10);

        $this->actingAs($this->almacenista)
            ->post(route('inventario.ajustar.guardar', $p), ['lote_id' => $lote->id, 'cantidad_contada' => 8, 'motivo' => ''])
            ->assertSessionHasErrors('motivo');

        $this->assertSame(10, $this->stock($p));
        $this->assertSame(0, MovimientoInventario::where('concepto', 'ajuste')->count());
    }

    /** K6. Un ajuste con motivo registra solo la diferencia, con el usuario y el motivo. */
    public function test_el_ajuste_registra_la_diferencia_con_el_motivo(): void
    {
        $p = $this->producto();
        $lote = $this->loteConKardex($p, 10);

        $this->actingAs($this->almacenista)
            ->post(route('inventario.ajustar.guardar', $p), [
                'lote_id' => $lote->id, 'cantidad_contada' => 7, 'motivo' => 'Bultos dañados por humedad en bodega',
            ])->assertRedirect();

        $mov = MovimientoInventario::where('concepto', 'ajuste')->firstOrFail();
        $this->assertSame('salida', $mov->tipo);
        $this->assertSame(3, $mov->cantidad);
        $this->assertSame(7, $mov->saldo);
        $this->assertSame($this->almacenista->id, $mov->user_id);
        $this->assertStringContainsString('humedad', $mov->observaciones);
        $this->assertSame(7, $this->stock($p));
    }

    /** K7. El vendedor no puede ajustar inventario ni ver la auditoría (403). */
    public function test_el_vendedor_no_ajusta_ni_ve_la_auditoria(): void
    {
        $p = $this->producto();

        $this->actingAs($this->vendedor)->get(route('inventario.ajustar', $p))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('inventario.kardex'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('auditoria.index'))->assertForbidden();
    }

    /** K8. El almacenista ve el kardex; el admin ve la auditoría. */
    public function test_el_almacenista_ve_el_kardex_y_el_admin_la_auditoria(): void
    {
        $p = $this->producto();
        $this->loteConKardex($p, 10);

        $this->actingAs($this->almacenista)->get(route('inventario.kardex', ['producto' => $p->id]))
            ->assertOk()->assertSee('Inventario inicial');
        $this->actingAs($this->admin)->get(route('auditoria.index'))->assertOk();
    }

    /** A1. Cambiar el precio de un producto queda en la auditoría con el valor anterior y el nuevo. */
    public function test_un_cambio_de_precio_queda_auditado_antes_y_despues(): void
    {
        $p = $this->producto('Urea 46%', 10000);

        $this->actingAs($this->admin);
        $p->update(['precio_venta' => 12500]);

        $actividad = Activity::where('log_name', 'productos')->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($this->admin->id, $actividad->causer_id);
        $this->assertEquals(10000, (float) $actividad->attribute_changes->get('old')['precio_venta']);
        $this->assertEquals(12500, (float) $actividad->attribute_changes->get('attributes')['precio_venta']);
    }

    /** A2. La contraseña nunca se audita. */
    public function test_la_contrasena_no_se_audita(): void
    {
        $this->actingAs($this->admin);
        $usuario = User::factory()->create(['email' => 'nuevo@test.com']);
        $usuario->update(['password' => 'otra-clave-123']);

        $registro = Activity::where('log_name', 'usuarios')->get()->map(fn ($a) => json_encode($a->attribute_changes))->join(' ');
        $this->assertStringNotContainsString('password', $registro);
    }

    /** A3. Los cambios en la matriz de permisos quedan en la bitácora de seguridad. */
    public function test_los_cambios_de_permisos_quedan_en_la_bitacora_de_seguridad(): void
    {
        $this->actingAs($this->admin);
        $vendedor = Role::findByName('vendedor');

        app(SeguridadService::class)->actualizarMatriz([$vendedor->id => ['ver-productos']]);

        $registro = Activity::where('log_name', 'seguridad')->where('description', 'like', '%«vendedor»%')->latest('id')->firstOrFail();
        $this->assertSame($this->admin->id, $registro->causer_id);
        $this->assertStringContainsString('vendedor', $registro->description);
        $this->assertSame(['ver-productos'], $registro->attribute_changes->get('attributes')['permisos']);
    }

    /** A4. Cambiar los roles de un usuario queda en la bitácora de seguridad. */
    public function test_cambiar_los_roles_de_un_usuario_queda_en_la_bitacora(): void
    {
        $this->actingAs($this->admin);

        app(SeguridadService::class)->actualizarUsuario($this->vendedor, [
            'name' => $this->vendedor->name, 'email' => $this->vendedor->email,
            'roles' => ['vendedor', 'almacenista'], 'permisos' => [],
        ], $this->admin);

        $registro = Activity::where('log_name', 'seguridad')->latest('id')->firstOrFail();
        $this->assertSame(['vendedor'], $registro->attribute_changes->get('old')['roles']);
        $this->assertSame(['almacenista', 'vendedor'], $registro->attribute_changes->get('attributes')['roles']);
    }

    /** K9. El comando inicializa el kardex de lotes que se cargaron sin movimiento. */
    public function test_el_comando_inicializa_los_lotes_sin_movimiento(): void
    {
        $p = $this->producto();
        $this->lote($p, 10, null, 'L-VIEJO-1'); // creado a mano, sin kardex
        $this->lote($p, 5, null, 'L-VIEJO-2');

        $this->artisan('inventario:inicializar-kardex')->assertSuccessful();

        $saldos = MovimientoInventario::where('concepto', 'inventario_inicial')->orderBy('id')->pluck('saldo')->all();
        $this->assertSame([10, 15], $saldos);
        $this->artisan('inventario:inicializar-kardex')->assertSuccessful(); // repetirlo no duplica
        $this->assertSame(2, MovimientoInventario::count());
    }
}
