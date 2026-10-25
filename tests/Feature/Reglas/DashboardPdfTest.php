<?php

namespace Tests\Feature\Reglas;

use App\Services\CompraService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Dashboard (KPIs filtrados por permisos + Top 5) y documentos PDF.
 */
class DashboardPdfTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    public function test_cada_rol_ve_solo_los_kpis_de_sus_permisos(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard'))
            ->assertOk()->assertSee('Ventas − Compras del mes')->assertSee('Compras del mes')->assertSee('Ventas del mes');

        // El vendedor ve ventas pero no compras ni el reporte Ventas − Compras
        $this->actingAs($this->vendedor)->get(route('dashboard'))
            ->assertOk()->assertSee('Ventas del mes')->assertDontSee('Compras del mes')->assertDontSee('Ventas − Compras del mes');

        // El almacenista ve compras pero no ventas ni Top 5
        $this->actingAs($this->almacenista)->get(route('dashboard'))
            ->assertOk()->assertSee('Compras del mes')->assertDontSee('Ventas del mes')->assertDontSee('Top 5');
    }

    public function test_el_top_5_cuenta_ventas_completadas_y_no_las_anuladas(): void
    {
        $popular = $this->producto('Producto Popular');
        $olvidado = $this->producto('Producto Anulado');
        $this->lote($popular, 50, null, 'P');
        $this->lote($olvidado, 50, null, 'O');
        $ventas = app(VentaService::class);
        $fecha = today()->toDateString();

        $ventas->registrar(['fecha' => $fecha, 'items' => [['producto_id' => $popular->id, 'cantidad' => 3]]], $this->vendedor->id);
        $anulada = $ventas->registrar(['fecha' => $fecha, 'items' => [['producto_id' => $olvidado->id, 'cantidad' => 20]]], $this->vendedor->id);
        $ventas->anular($anulada);

        $this->actingAs($this->vendedor)->get(route('dashboard'))
            ->assertOk()->assertSee('Producto Popular')->assertDontSee('Producto Anulado');
    }

    public function test_el_pdf_de_venta_lo_ven_quienes_pueden_ver_ventas_y_el_almacenista_recibe_403(): void
    {
        $p = $this->producto();
        $this->lote($p, 10);
        $venta = app(VentaService::class)->registrar(
            ['fecha' => today()->toDateString(), 'items' => [['producto_id' => $p->id, 'cantidad' => 2]]],
            $this->vendedor->id
        );

        $respuesta = $this->actingAs($this->vendedor)->get(route('ventas.pdf', $venta));
        $respuesta->assertOk();
        $this->assertStringContainsString('application/pdf', $respuesta->headers->get('Content-Type'));

        $this->actingAs($this->almacenista)->get(route('ventas.pdf', $venta))->assertForbidden();
    }

    public function test_el_pdf_de_la_orden_de_compra_se_genera(): void
    {
        $p = $this->producto();
        $compra = app(CompraService::class)->registrar([
            'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 5, 'costo_unitario' => 1000]],
        ], $this->almacenista->id);

        $respuesta = $this->actingAs($this->almacenista)->get(route('compras.pdf', $compra));
        $respuesta->assertOk();
        $this->assertStringContainsString('application/pdf', $respuesta->headers->get('Content-Type'));

        $this->actingAs($this->vendedor)->get(route('compras.pdf', $compra))->assertForbidden();
    }
}
