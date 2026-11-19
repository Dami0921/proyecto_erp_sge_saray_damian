<?php

namespace Tests\Feature\Reglas;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Clase 13: API REST v1 con Sanctum (login con rol activo, permisos, reglas de negocio compartidas)
 * e importación/exportación con Excel.
 */
class Clase13Test extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    /** Hace login por la API y devuelve el token. */
    private function token(string $email, ?string $rol = null): string
    {
        // Cada login parte «limpio»: sin el token de una petición anterior ni usuarios en memoria.
        // (En una prueba todas las peticiones comparten el mismo proceso; en producción cada una tiene el suyo.)
        $this->flushHeaders();
        $this->app['auth']->forgetGuards();

        $respuesta = $this->postJson('/api/v1/login', array_filter([
            'email' => $email, 'password' => 'password', 'device_name' => 'pruebas', 'rol' => $rol,
        ]))->assertCreated();

        $this->app['auth']->forgetGuards();

        return $respuesta->json('token');
    }

    private function datosVenta(Producto $p, int $cantidad): array
    {
        return ['fecha' => today()->toDateString(), 'items' => [['producto_id' => $p->id, 'cantidad' => $cantidad]]];
    }

    /** API1. El login entrega un token Bearer con el rol activo. */
    public function test_el_login_entrega_un_token_con_el_rol_activo(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'vendedor@test.com', 'password' => 'password', 'device_name' => 'pruebas',
        ])->assertCreated()->assertJsonPath('token_type', 'Bearer')->assertJsonPath('rol_activo', 'vendedor');
    }

    /** API2. Credenciales incorrectas → 422; un usuario en la papelera no entra. */
    public function test_credenciales_incorrectas_o_usuario_eliminado_no_entran(): void
    {
        $this->postJson('/api/v1/login', ['email' => 'vendedor@test.com', 'password' => 'mala', 'device_name' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('email');

        $this->vendedor->delete();
        $this->postJson('/api/v1/login', ['email' => 'vendedor@test.com', 'password' => 'password', 'device_name' => 'x'])
            ->assertStatus(422);
    }

    /** API3. Con varios roles hay que indicar con cuál se trabaja. */
    public function test_con_varios_roles_el_login_exige_elegir_uno(): void
    {
        $this->usuario('multi@test.com', ['vendedor', 'almacenista']);

        $this->postJson('/api/v1/login', ['email' => 'multi@test.com', 'password' => 'password', 'device_name' => 'x'])
            ->assertStatus(422)->assertJsonValidationErrors('rol');

        $this->postJson('/api/v1/login', ['email' => 'multi@test.com', 'password' => 'password', 'device_name' => 'x', 'rol' => 'admin'])
            ->assertStatus(422)->assertJsonValidationErrors('rol');
    }

    /** API4. Sin token o con token falso → 401 (JSON, aunque no se envíe Accept). */
    public function test_sin_token_responde_401(): void
    {
        $this->get('/api/v1/productos')->assertUnauthorized();
        $this->withToken('token-inventado')->getJson('/api/v1/productos')->assertUnauthorized();
    }

    /** API5. El listado de productos es paginado y muestra el stock vigente. */
    public function test_listar_productos_pagina_y_muestra_el_stock_vigente(): void
    {
        $p = $this->producto('Urea 46%');
        $this->lote($p, 10);
        $this->lote($p, 99, today()->subDay()->toDateString(), 'L-VENCIDO'); // vencido: no cuenta

        $token = $this->token('vendedor@test.com');

        $this->withToken($token)->getJson('/api/v1/productos')
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('data.0.nombre', 'Urea 46%')
            ->assertJsonPath('data.0.stock', 10);

        $this->withToken($token)->getJson('/api/v1/productos/99999')->assertNotFound();
        $this->withToken($token)->getJson('/api/v1/productos/'.$p->id)->assertOk()->assertJsonPath('data.stock', 10);
    }

    /** API6. Una venta por la API usa el mismo servicio: descuenta lotes y deja el kardex. */
    public function test_la_venta_por_api_usa_las_mismas_reglas_y_deja_kardex(): void
    {
        $p = $this->producto('Urea 46%', 10000);
        $this->lote($p, 10);

        $respuesta = $this->withToken($this->token('vendedor@test.com'))
            ->postJson('/api/v1/ventas', $this->datosVenta($p, 4))
            ->assertCreated()
            ->assertJsonPath('data.factura', 'CV-00001')
            ->assertJsonPath('data.total', 40000);

        $this->assertSame(6, $this->stock($p));
        $this->assertSame(1, MovimientoInventario::where('concepto', 'venta')->count());

        $this->withToken($this->token('vendedor@test.com'))
            ->getJson('/api/v1/ventas/'.$respuesta->json('data.id'))->assertOk()->assertJsonPath('data.detalle.0.cantidad', 4);
    }

    /** API7. Stock insuficiente o datos inválidos → 422 y no se guarda nada. */
    public function test_venta_sin_stock_o_con_datos_invalidos_responde_422(): void
    {
        $p = $this->producto();
        $this->lote($p, 5);
        $token = $this->token('vendedor@test.com');

        $this->withToken($token)->postJson('/api/v1/ventas', $this->datosVenta($p, 9999))
            ->assertStatus(422)->assertJsonValidationErrors('items');
        $this->withToken($token)->postJson('/api/v1/ventas', [])->assertStatus(422);

        $this->assertSame(5, $this->stock($p));
    }

    /** API8. Los mismos permisos de la web: el almacenista no puede vender (403). */
    public function test_el_almacenista_no_puede_vender_por_api(): void
    {
        $p = $this->producto();
        $this->lote($p, 5);

        $this->withToken($this->token('almacenista@test.com'))
            ->postJson('/api/v1/ventas', $this->datosVenta($p, 1))->assertForbidden();
    }

    /** API9. El rol activo viaja en el token: con rol almacenista no vende, con rol vendedor sí. */
    public function test_el_rol_activo_del_token_decide_los_permisos(): void
    {
        $this->usuario('multi@test.com', ['vendedor', 'almacenista']);
        $p = $this->producto();
        $this->lote($p, 5);

        $this->withToken($this->token('multi@test.com', 'almacenista'))
            ->postJson('/api/v1/ventas', $this->datosVenta($p, 1))->assertForbidden();

        $this->app['auth']->forgetGuards();
        $this->withToken($this->token('multi@test.com', 'vendedor'))
            ->postJson('/api/v1/ventas', $this->datosVenta($p, 1))->assertCreated();
    }

    /** API10. Lotes por vencer: solo con permiso ver-lotes; excluye vencidos y lejanos. */
    public function test_lotes_por_vencer(): void
    {
        $p = $this->producto();
        $this->lote($p, 5, today()->addDays(10)->toDateString(), 'L-PRONTO');
        $this->lote($p, 5, today()->addDays(200)->toDateString(), 'L-LEJANO');
        $this->lote($p, 5, today()->subDays(3)->toDateString(), 'L-VENCIDO');

        $respuesta = $this->withToken($this->token('almacenista@test.com'))->getJson('/api/v1/lotes/por-vencer?dias=30')->assertOk();
        $this->assertSame(['L-PRONTO'], collect($respuesta->json('data'))->pluck('numero_lote')->all());

        $this->app['auth']->forgetGuards();
        $this->withToken($this->token('vendedor@test.com'))->getJson('/api/v1/lotes/por-vencer')->assertForbidden();
    }

    /** API11. El logout revoca el token: después responde 401. */
    public function test_el_logout_revoca_el_token(): void
    {
        $token = $this->token('vendedor@test.com');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('rol_activo', 'vendedor');
        $this->withToken($token)->postJson('/api/v1/logout')->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/me')->assertUnauthorized();
    }

    /** XLS1. Exportar productos y ventas a Excel (con permiso); el vendedor recibe 403 en productos. */
    public function test_exportar_a_excel(): void
    {
        $p = $this->producto();
        $this->lote($p, 5);
        $this->vender($p);

        $this->actingAs($this->almacenista)->get(route('productos.exportar'))
            ->assertOk()->assertDownload();
        $this->actingAs($this->admin)->get(route('ventas.exportar'))
            ->assertOk()->assertDownload();
        $this->actingAs($this->vendedor)->get(route('productos.exportar'))->assertForbidden();
    }

    private function vender(Producto $p): void
    {
        app(\App\Services\VentaService::class)->registrar($this->datosVenta($p, 1), $this->vendedor->id);
    }

    /** XLS2. Importación: guarda las filas válidas, omite las inválidas con fila y columna, y no toca el stock. */
    public function test_la_importacion_valida_fila_por_fila_y_no_toca_el_stock(): void
    {
        Categoria::create(['nombre' => 'Herramientas']);
        $existente = $this->producto('Urea 46%', 10000); // categoría Fertilizantes
        $this->lote($existente, 10);

        $csv = implode("\n", [
            'nombre,categoria,unidad_medida,precio_venta,iva_porcentaje',
            'Pala de punta,Herramientas,unidad,35000,19',      // fila 2: válida (nueva)
            'Urea 46%,Fertilizantes,bulto,12500,0',           // fila 3: existe → actualiza precio
            'Rastrillo,Herramientas,unidad,,19',              // fila 4: sin precio
            'Fumigadora,Maquinaria inexistente,unidad,90000,19', // fila 5: categoría que no existe
        ]);

        $respuesta = $this->actingAs($this->almacenista)->post(route('productos.importar.guardar'), [
            'archivo' => UploadedFile::fake()->createWithContent('productos.csv', $csv),
        ]);

        $respuesta->assertRedirect(route('productos.importar'));
        $respuesta->assertSessionHas('success', fn ($m) => str_contains($m, '1 producto(s) creado(s) y 1 actualizado(s)'));

        $fallas = session('fallas');
        $this->assertCount(2, $fallas);
        $this->assertEqualsCanonicalizing([4, 5], collect($fallas)->pluck('fila')->all());
        $this->assertEqualsCanonicalizing(['precio_venta', 'categoria'], collect($fallas)->pluck('columna')->all());

        $this->assertEquals(12500, $existente->fresh()->precio_venta);
        $this->assertSame(10, $this->stock($existente), 'La importación nunca cambia las existencias');
        $this->assertNotNull(Producto::where('nombre', 'Pala de punta')->first());
        $this->assertNull(Producto::where('nombre', 'Rastrillo')->first());
        $this->assertSame(0, Lote::where('producto_id', Producto::where('nombre', 'Pala de punta')->value('id'))->count());
    }

    /** XLS3. Solo quien tiene el permiso importa; el vendedor recibe 403. */
    public function test_el_vendedor_no_puede_importar(): void
    {
        $this->actingAs($this->vendedor)->get(route('productos.importar'))->assertForbidden();
        $this->actingAs($this->almacenista)->get(route('productos.importar'))->assertOk();
    }
}
