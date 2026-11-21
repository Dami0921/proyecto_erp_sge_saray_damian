<?php

namespace Tests\Feature\Reglas;

use App\Models\Organizacion;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Organización: la identidad corporativa (nombre, NIT, logo, color) hace que el mismo ERP sirva a cualquier empresa.
 */
class OrganizacionTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    private function datos(array $extra = []): array
    {
        return $extra + [
            'nombre' => 'Ferretería El Tornillo S.A.S.', 'nit' => '900123456-1', 'direccion' => 'Calle 10 # 5-20',
            'telefono' => '3001234567', 'email' => 'contacto@tornillo.test', 'eslogan' => 'Todo para tu obra',
            'descripcion' => 'Herramientas y materiales para construir.', 'color' => 'azul',
        ];
    }

    /** O1. Sin configurar, el sistema usa la identidad inicial de config/empresa.php. */
    public function test_sin_configurar_usa_la_identidad_inicial(): void
    {
        $this->assertSame(config('empresa.nombre'), Organizacion::actual()->nombre);
        $this->actingAs($this->admin)->get(route('organizacion.edit'))->assertOk()->assertSee(config('empresa.nombre'));
    }

    /** O2. Solo quien tiene el permiso gestionar-organizacion entra (por defecto, el admin). */
    public function test_solo_el_admin_gestiona_la_organizacion(): void
    {
        $contador = $this->usuario('contador@test.com', ['contador']);

        foreach ([$this->vendedor, $this->almacenista, $contador] as $usuario) {
            $this->actingAs($usuario)->get(route('organizacion.edit'))->assertForbidden();
            $this->actingAs($usuario)->put(route('organizacion.update'), $this->datos())->assertForbidden();
        }

        $this->assertTrue(Role::findByName('admin')->hasPermissionTo('gestionar-organizacion'));
        $this->assertDatabaseCount('organizacion', 0);
    }

    /** O3. El admin cambia la identidad y se ve de inmediato en el sistema (la caché se descarta). */
    public function test_el_admin_actualiza_la_identidad_y_se_ve_de_inmediato(): void
    {
        Organizacion::actual(); // llena la caché con los valores iniciales

        $this->actingAs($this->admin)->put(route('organizacion.update'), $this->datos())
            ->assertRedirect(route('organizacion.edit'));

        $this->assertSame('Ferretería El Tornillo S.A.S.', Organizacion::actual()->nombre);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('Ferretería El Tornillo S.A.S.');
    }

    /** O4. La portada y el inicio de sesión muestran la empresa configurada, no la distribuidora original. */
    public function test_la_portada_y_el_login_muestran_la_empresa_configurada(): void
    {
        Organizacion::create($this->datos());

        $this->get('/')->assertOk()
            ->assertSee('Ferretería El Tornillo S.A.S.')
            ->assertSee('Todo para tu obra')
            ->assertSee('Herramientas y materiales para construir.')
            ->assertDontSee('AgroInsumos');

        $this->get(route('login'))->assertOk()->assertSee('Ferretería El Tornillo S.A.S.')->assertDontSee('AgroInsumos');
    }

    /** O5. Validaciones: nombre obligatorio, color de la lista y logo png/jpg liviano. */
    public function test_las_validaciones_protegen_la_identidad(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('organizacion.update'), $this->datos(['nombre' => '']))->assertSessionHasErrors('nombre');
        $this->put(route('organizacion.update'), $this->datos(['color' => 'rosado']))->assertSessionHasErrors('color');
        $this->put(route('organizacion.update'), $this->datos(['email' => 'no-es-correo']))->assertSessionHasErrors('email');
        $this->put(route('organizacion.update'), $this->datos(['logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml')]))
            ->assertSessionHasErrors('logo');
        $this->put(route('organizacion.update'), $this->datos(['logo' => UploadedFile::fake()->image('grande.png', 50, 50)->size(400)]))
            ->assertSessionHasErrors('logo');

        $this->assertDatabaseCount('organizacion', 0);
    }

    /** O6. El logo se guarda, aparece en el menú y se puede quitar; ambos cambios quedan anotados. */
    public function test_el_logo_se_sube_se_muestra_y_se_quita(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('organizacion.update'), $this->datos(['logo' => UploadedFile::fake()->image('logo.png', 60, 60)]))
            ->assertSessionHasNoErrors();

        $this->assertStringStartsWith('data:image/png;base64,', Organizacion::first()->logo);
        $this->get(route('dashboard'))->assertSee('data:image/png;base64', false);
        $this->assertTrue(Activity::where('log_name', 'organizacion')->where('description', 'logo actualizado')->exists());

        $this->put(route('organizacion.update'), $this->datos(['quitar_logo' => 1]))->assertSessionHasNoErrors();

        $this->assertNull(Organizacion::first()->logo);
        $this->get(route('dashboard'))->assertDontSee('data:image/png;base64', false);
        $this->assertTrue(Activity::where('log_name', 'organizacion')->where('description', 'logo eliminado')->exists());
    }

    /** O7. El color elegido cambia el tema; el verde original no agrega ajustes. */
    public function test_el_color_cambia_el_tema(): void
    {
        $this->actingAs($this->admin);

        $this->put(route('organizacion.update'), $this->datos(['color' => 'azul']));
        $this->get(route('dashboard'))->assertSee('id="tema-organizacion"', false)->assertSee('#1d4ed8');

        $this->put(route('organizacion.update'), $this->datos(['color' => 'verde']));
        $this->get(route('dashboard'))->assertDontSee('id="tema-organizacion"', false);
    }

    /** O8. Cambiar la identidad queda en la auditoría con el valor anterior y el nuevo. */
    public function test_el_cambio_de_identidad_queda_auditado(): void
    {
        Organizacion::create($this->datos(['nombre' => 'Empresa Uno']));

        $this->actingAs($this->admin)->put(route('organizacion.update'), $this->datos(['nombre' => 'Empresa Dos']));

        $actividad = Activity::where('log_name', 'organizacion')->where('event', 'updated')->latest('id')->firstOrFail();
        $this->assertSame($this->admin->id, $actividad->causer_id);
        $this->assertSame('Empresa Uno', $actividad->attribute_changes->get('old')['nombre']);
        $this->assertSame('Empresa Dos', $actividad->attribute_changes->get('attributes')['nombre']);
    }

    /** O9. Los documentos PDF salen con la identidad configurada (incluido el logo). */
    public function test_los_pdf_usan_la_identidad_configurada(): void
    {
        $this->actingAs($this->admin)->put(route('organizacion.update'), $this->datos([
            'logo' => UploadedFile::fake()->image('logo.png', 60, 60),
        ]));

        $datos = Organizacion::actual()->paraPdf();
        $this->assertSame('Ferretería El Tornillo S.A.S.', $datos['nombre']);
        $this->assertSame('900123456-1', $datos['nit']);
        $this->assertSame('#1e40af', $datos['color']); // tono 800 del azul
        $this->assertNotNull($datos['logo']);

        $p = $this->producto();
        $this->lote($p, 10);
        $venta = app(VentaService::class)->registrar([
            'cliente_id' => null, 'fecha' => today()->toDateString(),
            'items' => [['producto_id' => $p->id, 'cantidad' => 2]],
        ], $this->vendedor->id);

        $respuesta = $this->actingAs($this->vendedor)->get(route('ventas.pdf', $venta));
        $respuesta->assertOk();
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }
}
