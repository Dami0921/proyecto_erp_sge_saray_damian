<?php

namespace Tests\Feature\Reglas;

use App\Models\Compra;
use App\Services\CompraService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Reglas de negocio de COMPRAS y RECEPCIÓN (CompraService + DB::transaction).
 */
class ComprasTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    private function ordenar(int $productoId, int $cantidad = 10): Compra
    {
        return app(CompraService::class)->registrar([
            'proveedor_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $productoId, 'cantidad' => $cantidad, 'costo_unitario' => 5000]],
        ], $this->almacenista->id);
    }

    private function recepcion(Compra $compra, int $cantidad, ?string $vence = null): array
    {
        return [$compra->detalles->first()->id => [
            'cantidad' => $cantidad, 'fecha_vencimiento' => $vence ?? today()->addMonths(6)->toDateString(),
        ]];
    }

    /** R8. Crear una orden NO mueve el inventario. */
    public function test_crear_la_orden_no_mueve_el_stock(): void
    {
        $p = $this->producto();

        $compra = $this->ordenar($p->id, 25);

        $this->assertSame(Compra::PENDIENTE, $compra->estado);
        $this->assertSame('OC-00001', $compra->numero_orden);
        $this->assertSame(0, $this->stock($p));
    }

    /** R9. La recepción parcial aumenta el stock y deja la orden «parcial»; al completar pasa a «recibida». */
    public function test_la_recepcion_parcial_sube_el_stock_y_cambia_el_estado(): void
    {
        $p = $this->producto();
        $compra = $this->ordenar($p->id, 10);
        $servicio = app(CompraService::class);

        $servicio->recibir($compra, $this->recepcion($compra, 4));
        $this->assertSame(4, $this->stock($p));
        $this->assertSame(Compra::PARCIAL, $compra->fresh()->estado);

        $servicio->recibir($compra, $this->recepcion($compra, 6));
        $this->assertSame(10, $this->stock($p));
        $this->assertSame(Compra::RECIBIDA, $compra->fresh()->estado);
    }

    /** R10. No se puede recibir más de lo pedido, y no queda stock a medias. */
    public function test_no_se_recibe_mas_de_lo_pedido(): void
    {
        $p = $this->producto();
        $compra = $this->ordenar($p->id, 10);

        try {
            app(CompraService::class)->recibir($compra, $this->recepcion($compra, 11));
            $this->fail('Debió lanzar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('No se puede recibir más de lo pedido', $e->errors()['recepcion'][0]);
        }

        $this->assertSame(0, $this->stock($p));
        $this->assertSame(Compra::PENDIENTE, $compra->fresh()->estado);
    }

    /** R11. No se recibe mercancía vencida (agroquímicos y semillas vencidos no se pueden vender). */
    public function test_no_se_recibe_mercancia_vencida(): void
    {
        $p = $this->producto();
        $compra = $this->ordenar($p->id, 10);

        $this->expectException(ValidationException::class);
        app(CompraService::class)->recibir($compra, $this->recepcion($compra, 5, today()->subDay()->toDateString()));
    }

    /** R12. Una recepción debe traer al menos una unidad. */
    public function test_la_recepcion_exige_al_menos_una_unidad(): void
    {
        $p = $this->producto();
        $compra = $this->ordenar($p->id, 10);

        $this->expectException(ValidationException::class);
        app(CompraService::class)->recibir($compra, $this->recepcion($compra, 0));
    }

    /** R13. Solo se anula una orden sin mercancía recibida. */
    public function test_no_se_anula_una_orden_con_mercancia_recibida(): void
    {
        $p = $this->producto();
        $compra = $this->ordenar($p->id, 10);
        app(CompraService::class)->recibir($compra, $this->recepcion($compra, 3));

        $this->expectException(ValidationException::class);
        app(CompraService::class)->anular($compra);
    }

    /** R14. Una orden sin recibir sí se puede anular (queda en la papelera). */
    public function test_una_orden_pendiente_se_puede_anular(): void
    {
        $p = $this->producto();
        $compra = $this->ordenar($p->id, 10);

        app(CompraService::class)->anular($compra);

        $this->assertSoftDeleted('compras', ['id' => $compra->id, 'estado' => Compra::ANULADA]);
    }

    /** R15. Un producto no puede repetirse en la misma orden (Form Request). */
    public function test_la_orden_no_admite_productos_repetidos(): void
    {
        $p = $this->producto();

        $this->actingAs($this->almacenista)
            ->post(route('compras.store'), [
                'fecha' => today()->toDateString(),
                'items' => [
                    ['producto_id' => $p->id, 'cantidad' => 1, 'costo_unitario' => 100],
                    ['producto_id' => $p->id, 'cantidad' => 2, 'costo_unitario' => 100],
                ],
            ])
            ->assertSessionHasErrors('items.0.producto_id');

        $this->assertSame(0, Compra::count());
    }
}
