<?php

namespace Tests\Feature\Reglas;

use App\Events\CompraRecibida;
use App\Events\VentaRegistrada;
use App\Notifications\CompraRecibidaNotification;
use App\Notifications\LotesPorVencerNotification;
use App\Notifications\StockBajoNotification;
use App\Services\CompraService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Clase 14: eventos, listeners, notificaciones (correo + campana) y tarea programada.
 */
class Clase14Test extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    private function vender($producto, int $cantidad)
    {
        return app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => $cantidad]],
        ], $this->vendedor->id);
    }

    private function comprar($producto, int $pedido, int $recibido)
    {
        $service = app(CompraService::class);
        $compra = $service->registrar([
            'proveedor_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $producto->id, 'cantidad' => $pedido, 'costo_unitario' => 7000]],
        ], $this->almacenista->id);

        $detalle = $compra->detalles->first();
        $service->recibir($compra, [$detalle->id => [
            'cantidad' => $recibido, 'fecha_vencimiento' => today()->addYear()->toDateString(), 'numero_lote' => null,
        ]]);

        return $compra->fresh();
    }

    /** E1. Vender dispara el evento VentaRegistrada. */
    public function test_una_venta_dispara_el_evento(): void
    {
        Event::fake([VentaRegistrada::class]);
        $p = $this->producto();
        $this->lote($p, 50);

        $venta = $this->vender($p, 2);

        Event::assertDispatched(VentaRegistrada::class, fn ($e) => $e->venta->is($venta));
    }

    /** N1. Cuando una venta deja el producto en stock bajo, avisan quienes compran (no el vendedor). */
    public function test_cruzar_el_umbral_avisa_a_quienes_compran(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 12);

        $this->vender($p, 5); // 12 -> 7 (cruza el umbral de 10)

        Notification::assertSentTo($this->almacenista, StockBajoNotification::class, fn ($n) => $n->stock === 7);
        Notification::assertSentTo($this->admin, StockBajoNotification::class);
        Notification::assertNotSentTo($this->vendedor, StockBajoNotification::class);
    }

    /** N2. Si el producto ya estaba en stock bajo, no se repite la alerta en cada venta. */
    public function test_no_repite_la_alerta_si_ya_estaba_bajo(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 8);

        $this->vender($p, 2); // 8 -> 6, ya estaba por debajo

        Notification::assertNothingSent();
    }

    /** N3. Una venta con stock suficiente por encima del umbral no genera alertas. */
    public function test_una_venta_normal_no_genera_alertas(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 100);

        $this->vender($p, 5);

        Notification::assertNothingSent();
    }

    /** N4. Si la venta falla y hace rollback, nadie recibe una alerta falsa. */
    public function test_una_venta_fallida_no_notifica(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 12);

        try {
            $this->vender($p, 500); // más de lo disponible
            $this->fail('Debió fallar por stock insuficiente');
        } catch (ValidationException) {
            // esperado
        }

        Notification::assertNothingSent();
        $this->assertSame(12, $this->stock($p));
    }

    /** N5. Recibir la orden completa avisa a quien la creó. */
    public function test_recibir_la_orden_completa_avisa_al_creador(): void
    {
        Notification::fake();
        $p = $this->producto();

        $compra = $this->comprar($p, 20, 20);

        Notification::assertSentTo($this->almacenista, CompraRecibidaNotification::class,
            fn ($n) => $n->completa && $n->compra->is($compra));
    }

    /** N6. Una recepción parcial también avisa, marcada como no completa. */
    public function test_recepcion_parcial_avisa_como_incompleta(): void
    {
        Event::fake([CompraRecibida::class]);
        $p = $this->producto();

        $this->comprar($p, 20, 8);

        Event::assertDispatched(CompraRecibida::class, fn ($e) => $e->completa === false);
    }

    /** N7. El correo de mercancía recibida lleva la orden de compra en PDF adjunta. */
    public function test_el_correo_de_compra_recibida_adjunta_el_pdf(): void
    {
        $p = $this->producto();
        $compra = $this->comprar($p, 20, 20);

        $mail = (new CompraRecibidaNotification($compra, true))->toMail($this->almacenista);

        $this->assertCount(1, $mail->rawAttachments);
        $this->assertSame("orden-{$compra->numero_orden}.pdf", $mail->rawAttachments[0]['name']);
        $this->assertStringStartsWith('%PDF', $mail->rawAttachments[0]['data']);
        $this->assertStringContainsString($compra->numero_orden, $mail->subject);
    }

    /** N8. Las notificaciones usan correo y base de datos (campana). */
    public function test_las_notificaciones_usan_correo_y_campana(): void
    {
        $p = $this->producto();

        $this->assertSame(['mail', 'database'], (new StockBajoNotification($p, 3))->via($this->admin));
        $this->assertSame(['mail', 'database'], (new LotesPorVencerNotification([], 30))->via($this->admin));
    }

    /** T1. El comando avisa solo de lotes con existencias dentro de la ventana, a quienes ven lotes. */
    public function test_el_comando_avisa_de_los_lotes_por_vencer(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 10, today()->addDays(10)->toDateString(), 'L-PRONTO');
        $this->lote($p, 10, today()->addDays(200)->toDateString(), 'L-LEJANO');
        $this->lote($p, 0, today()->addDays(5)->toDateString(), 'L-VACIO');
        $this->lote($p, 10, today()->subDays(3)->toDateString(), 'L-VENCIDO');

        Artisan::call('erp:lotes-por-vencer', ['--dias' => 30]);

        Notification::assertSentTo($this->almacenista, LotesPorVencerNotification::class, function ($n) {
            return count($n->lotes) === 1 && $n->lotes[0]['lote'] === 'L-PRONTO';
        });
        Notification::assertSentTo($this->admin, LotesPorVencerNotification::class);
        Notification::assertNotSentTo($this->vendedor, LotesPorVencerNotification::class);
    }

    /** T2. Sin lotes por vencer, el comando no envía nada. */
    public function test_el_comando_no_envia_nada_si_no_hay_lotes_por_vencer(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 10, today()->addDays(200)->toDateString());

        $this->artisan('erp:lotes-por-vencer')->assertSuccessful();

        Notification::assertNothingSent();
    }

    /** T3. La tarea está programada cada día. */
    public function test_la_tarea_esta_programada(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('erp:lotes-por-vencer', Artisan::output());
    }

    /** C1. La campana: se abre la notificación, se marca como leída y se redirige a su pantalla. */
    public function test_abrir_una_notificacion_la_marca_como_leida(): void
    {
        $p = $this->producto();
        $this->almacenista->notify(new StockBajoNotification($p, 4));
        $notificacion = $this->almacenista->unreadNotifications()->firstOrFail();

        $this->actingAs($this->almacenista)
            ->get(route('notificaciones.abrir', $notificacion->id))
            ->assertRedirect(route('inventario.kardex', ['producto' => $p->id]));

        $this->assertNotNull($notificacion->fresh()->read_at);
    }

    /** C2. Nadie puede abrir las notificaciones de otro usuario. */
    public function test_no_se_pueden_abrir_notificaciones_ajenas(): void
    {
        $p = $this->producto();
        $this->almacenista->notify(new StockBajoNotification($p, 4));
        $ajena = $this->almacenista->notifications()->firstOrFail();

        $this->actingAs($this->vendedor)
            ->get(route('notificaciones.abrir', $ajena->id))
            ->assertNotFound();

        $this->assertNull($ajena->fresh()->read_at);
    }

    /** C3. La pantalla muestra solo las notificaciones propias y «marcar todas» las deja leídas. */
    public function test_la_pantalla_lista_y_marca_todas_como_leidas(): void
    {
        $p = $this->producto();
        $this->almacenista->notify(new StockBajoNotification($p, 4));
        $this->admin->notify(new StockBajoNotification($p, 4));

        $this->actingAs($this->almacenista)->get(route('notificaciones.index'))
            ->assertOk()->assertSee('Stock bajo')->assertSee('Sin leer: 1');

        $this->actingAs($this->almacenista)->post(route('notificaciones.leer-todas'))
            ->assertRedirect(route('notificaciones.index'));

        $this->assertSame(0, $this->almacenista->unreadNotifications()->count());
        $this->assertSame(1, $this->admin->unreadNotifications()->count());
    }

    /** C4. Invitado: la campana exige iniciar sesión. */
    public function test_la_campana_exige_sesion(): void
    {
        $this->get(route('notificaciones.index'))->assertRedirect(route('login'));
    }
}
