<?php

namespace Tests\Feature\Reglas;

use App\Models\Modulo;
use App\Services\CompraService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Personalización: el ERP se adapta a cada empresa apagando los módulos que no usa,
 * sin romper el resto del sistema (web, API, alertas y tareas programadas).
 */
class ModulosTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    // update() sobre el modelo limpia la caché de módulos (igual que el botón del panel)
    private function apagar(string ...$claves): void
    {
        foreach ($claves as $clave) {
            Modulo::where('clave', $clave)->firstOrFail()->update(['activo' => false]);
        }
    }

    private function token(string $email): string
    {
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $token = $this->postJson('/api/v1/login', [
            'email' => $email, 'password' => 'password', 'device_name' => 'pruebas',
        ])->assertCreated()->json('token');

        $this->app['auth']->forgetGuards();

        return $token;
    }

    /** M1. Sin el módulo Proveedores, compras e inventario siguen funcionando (el proveedor es opcional). */
    public function test_sin_proveedores_las_compras_siguen_funcionando(): void
    {
        $this->apagar('proveedores');
        $p = $this->producto();

        $this->actingAs($this->almacenista)->get(route('proveedores.index'))->assertNotFound();
        $this->actingAs($this->almacenista)->get(route('productos.index'))->assertOk();
        $this->actingAs($this->almacenista)->get(route('compras.create'))->assertOk();

        $service = app(CompraService::class);
        $compra = $service->registrar([
            'proveedor_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 10, 'costo_unitario' => 5000]],
        ], $this->almacenista->id);
        $service->recibir($compra, [$compra->detalles->first()->id => [
            'cantidad' => 10, 'fecha_vencimiento' => today()->addYear()->toDateString(), 'numero_lote' => null,
        ]]);

        $this->assertSame(10, $this->stock($p));
    }

    /** M2. Sin el módulo Clientes, se puede vender a un cliente general. */
    public function test_sin_clientes_las_ventas_siguen_funcionando(): void
    {
        $this->apagar('clientes');
        $p = $this->producto();
        $this->lote($p, 20);

        $this->actingAs($this->vendedor)->get(route('clientes.index'))->assertNotFound();
        $this->actingAs($this->vendedor)->get(route('ventas.create'))->assertOk();

        $venta = app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 3]],
        ], $this->vendedor->id);

        $this->assertSame(17, $this->stock($p));
        $this->actingAs($this->vendedor)->get(route('ventas.pdf', $venta))->assertOk();
    }

    /** M3. Sin Ventas, la web y la API de ventas responden 404 y lo demás sigue en pie. */
    public function test_sin_ventas_web_y_api_responden_404_y_el_resto_sigue(): void
    {
        $p = $this->producto();
        $this->lote($p, 20);
        $venta = app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 2]],
        ], $this->vendedor->id);

        $this->apagar('ventas');

        $this->actingAs($this->vendedor)->get(route('ventas.index'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('productos.index'))->assertOk();

        $token = $this->token('vendedor@test.com');
        $this->withToken($token)->getJson("/api/v1/ventas/{$venta->id}")->assertNotFound();
        $this->withToken($token)->postJson('/api/v1/ventas', [
            'fecha' => today()->toDateString(), 'items' => [['producto_id' => $p->id, 'cantidad' => 1]],
        ])->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/productos')->assertOk();
        $this->assertSame(18, $this->stock($p));
    }

    /** M4. Sin Inventario también se apagan lo que depende de él (ventas, compras, kardex, API). */
    public function test_sin_inventario_se_apagan_sus_dependientes_pero_no_el_nucleo(): void
    {
        $p = $this->producto();
        $this->apagar('inventario');

        $this->actingAs($this->admin)->get(route('productos.index'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('inventario.kardex'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('ventas.index'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('compras.index'))->assertNotFound();

        // El núcleo sigue: dashboard, configuración, auditoría y notificaciones
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();
        $this->actingAs($this->admin)->get(route('configuracion.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('auditoria.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('notificaciones.index'))->assertOk();

        $token = $this->token('admin@test.com');
        $this->withToken($token)->getJson('/api/v1/productos')->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/me')->assertOk();
    }

    /** M5. El menú solo muestra los módulos activos y vuelven al activarlos. */
    public function test_el_menu_oculta_los_modulos_apagados_y_los_muestra_al_activarlos(): void
    {
        $enlace = 'href="'.route('proveedores.index').'"';

        $this->actingAs($this->almacenista)->get(route('dashboard'))->assertSee($enlace, false);

        $this->apagar('proveedores');
        $this->actingAs($this->almacenista)->get(route('dashboard'))->assertDontSee($enlace, false);

        Modulo::where('clave', 'proveedores')->firstOrFail()->update(['activo' => true]);
        $this->actingAs($this->almacenista)->get(route('dashboard'))->assertSee($enlace, false);
        $this->actingAs($this->almacenista)->get(route('proveedores.index'))->assertOk();
    }

    /** M6. El admin apaga y enciende módulos desde el panel; el núcleo no se puede apagar. */
    public function test_el_panel_apaga_y_enciende_modulos_y_protege_el_nucleo(): void
    {
        $proveedores = Modulo::where('clave', 'proveedores')->firstOrFail();

        $this->actingAs($this->admin)->patch(route('modulos.toggle', $proveedores))->assertRedirect();
        $this->assertFalse($proveedores->fresh()->activo);

        $this->actingAs($this->admin)->patch(route('modulos.toggle', $proveedores))->assertRedirect();
        $this->assertTrue($proveedores->fresh()->activo);

        $nucleo = Modulo::where('clave', 'nucleo')->firstOrFail();
        $this->actingAs($this->admin)->patch(route('modulos.toggle', $nucleo))->assertForbidden();
        $this->assertTrue(Modulo::activo('nucleo'));

        $this->actingAs($this->vendedor)->get(route('modulos.index'))->assertForbidden();
    }

    /** M7. Sin Compras no se envían alertas de stock bajo (invitan a comprar). */
    public function test_sin_compras_no_hay_alerta_de_stock_bajo(): void
    {
        Notification::fake();
        $this->apagar('compras');
        $p = $this->producto();
        $this->lote($p, 12);

        app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 5]],
        ], $this->vendedor->id);

        Notification::assertNothingSent();
        $this->assertSame(7, $this->stock($p));
    }

    /** M8. Sin Inventario, la tarea programada de lotes por vencer no hace nada. */
    public function test_sin_inventario_la_tarea_programada_no_avisa(): void
    {
        Notification::fake();
        $p = $this->producto();
        $this->lote($p, 10, today()->addDays(5)->toDateString());
        $this->apagar('inventario');

        Artisan::call('erp:lotes-por-vencer');

        Notification::assertNothingSent();
        $this->assertStringContainsString('desactivado', Artisan::output());
    }
}
