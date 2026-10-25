<?php

namespace Tests\Feature\Reglas;

use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Reglas de negocio de VENTAS (VentaService + DB::transaction).
 */
class VentasTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    private function vender(array $items): Venta
    {
        return app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(), 'items' => $items,
        ], $this->vendedor->id);
    }

    /** R1. Una venta descuenta el stock, primero del lote que vence antes (FEFO). */
    public function test_la_venta_descuenta_stock_del_lote_que_vence_primero(): void
    {
        $p = $this->producto();
        $tarde = $this->lote($p, 10, today()->addMonths(6)->toDateString(), 'L-TARDE');
        $pronto = $this->lote($p, 10, today()->addMonths(1)->toDateString(), 'L-PRONTO');

        $this->vender([['producto_id' => $p->id, 'cantidad' => 4]]);

        $this->assertSame(6, $pronto->fresh()->cantidad_actual);   // salió del que vence antes
        $this->assertSame(10, $tarde->fresh()->cantidad_actual);   // el otro intacto
    }

    /** R2. El número de documento es secuencial (CV-00001, CV-00002...). */
    public function test_el_numero_de_factura_es_secuencial(): void
    {
        $p = $this->producto();
        $this->lote($p, 50);

        $v1 = $this->vender([['producto_id' => $p->id, 'cantidad' => 1]]);
        $v2 = $this->vender([['producto_id' => $p->id, 'cantidad' => 1]]);

        $this->assertSame('CV-00001', $v1->numero_factura);
        $this->assertSame('CV-00002', $v2->numero_factura);
    }

    /** R3. No se vende más de lo disponible y, al fallar, la transacción no deja NADA guardado. */
    public function test_sin_stock_suficiente_la_venta_falla_y_no_guarda_nada(): void
    {
        $a = $this->producto('Urea 46%');
        $b = $this->producto('Glifosato 480');
        $this->lote($a, 10, null, 'LA');
        $this->lote($b, 2, null, 'LB');

        try {
            // La línea de «a» se puede cumplir, la de «b» no: debe deshacerse todo
            $this->vender([['producto_id' => $a->id, 'cantidad' => 5], ['producto_id' => $b->id, 'cantidad' => 3]]);
            $this->fail('Debió lanzar ValidationException por stock insuficiente.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('Stock insuficiente', $e->errors()['items'][0]);
        }

        $this->assertSame(10, $this->stock($a));     // rollback: el stock de «a» no cambió
        $this->assertSame(0, Venta::withTrashed()->count());
    }

    /** R4. Un lote vencido no cuenta como stock vendible. */
    public function test_no_se_vende_mercancia_vencida(): void
    {
        $p = $this->producto();
        $this->lote($p, 20, today()->subDay()->toDateString(), 'VENCIDO');

        $this->expectException(ValidationException::class);
        $this->vender([['producto_id' => $p->id, 'cantidad' => 1]]);
    }

    /** R5. Anular una venta devuelve el stock a los mismos lotes y deja la venta en la papelera. */
    public function test_anular_una_venta_devuelve_el_stock(): void
    {
        $p = $this->producto();
        $this->lote($p, 10);
        $venta = $this->vender([['producto_id' => $p->id, 'cantidad' => 7]]);
        $this->assertSame(3, $this->stock($p));

        $unidades = app(VentaService::class)->anular($venta);

        $this->assertSame(7, $unidades);
        $this->assertSame(10, $this->stock($p));
        $this->assertSoftDeleted('ventas', ['id' => $venta->id, 'estado' => Venta::ANULADA]);
    }

    /** R6. Una venta anulada no se puede anular otra vez (no se duplica la devolución de stock). */
    public function test_no_se_puede_anular_dos_veces(): void
    {
        $p = $this->producto();
        $this->lote($p, 10);
        $venta = $this->vender([['producto_id' => $p->id, 'cantidad' => 4]]);
        app(VentaService::class)->anular($venta);

        $this->expectException(ValidationException::class);
        app(VentaService::class)->anular($venta);
    }

    /** R7. El IVA se calcula por producto y se guarda en cada línea. */
    public function test_el_iva_se_calcula_por_producto(): void
    {
        $conIva = $this->producto('Azadón', 100000, 19);
        $sinIva = $this->producto('Urea', 50000, 0);
        $this->lote($conIva, 5, null, 'A');
        $this->lote($sinIva, 5, null, 'B');

        $venta = $this->vender([
            ['producto_id' => $conIva->id, 'cantidad' => 2],  // 200.000 + 38.000 de IVA
            ['producto_id' => $sinIva->id, 'cantidad' => 1],  //  50.000 sin IVA
        ]);

        $this->assertEquals(250000, $venta->subtotal);
        $this->assertEquals(38000, $venta->iva);
        $this->assertEquals(288000, $venta->total);
    }
}
