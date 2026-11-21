<?php

namespace Tests\Feature\Reglas;

use App\Exports\PlantillaClientesExport;
use App\Exports\PlantillaProveedoresExport;
use App\Imports\ClientesImport;
use App\Imports\ProveedoresImport;
use App\Models\Cliente;
use App\Models\Modulo;
use App\Models\Proveedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Activitylog\Models\Activity;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Carga masiva de clientes y proveedores desde Excel/CSV: validación fila por fila, actualización por
 * documento/NIT, permisos por rol y dependencia del panel de módulos.
 */
class ImportacionTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    private function csv(string ...$lineas): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('datos.csv', implode("\n", $lineas));
    }

    /** I1. Clientes: crea los nuevos y actualiza por documento (aunque el tipo venga en minúsculas). */
    public function test_importar_clientes_crea_y_actualiza_por_documento(): void
    {
        Cliente::create(['nombre' => 'Finca Vieja', 'tipo_cliente' => 'Finca', 'documento' => '900111', 'municipio' => 'Silvania']);

        $respuesta = $this->actingAs($this->vendedor)->post(route('clientes.importar.guardar'), [
            'archivo' => $this->csv(
                'nombre,tipo_cliente,documento,telefono,municipio',
                'Finca Vieja Actualizada,finca,900111,3001112233,Fusagasugá',   // ya existe → actualiza
                'Juan Pérez,Natural,1012345678,,Silvania',                      // nueva
                'Coop Agro,COOPERATIVA,800222,3004445566,Arbeláez',             // nueva, tipo en mayúsculas
            ),
        ]);

        $respuesta->assertRedirect(route('clientes.importar'));
        $respuesta->assertSessionHas('success', fn ($m) => str_contains($m, '2 cliente(s) creado(s) y 1 actualizado(s)'));
        $this->assertSame([], session('fallas'));

        $this->assertSame(3, Cliente::count());
        $this->assertDatabaseHas('clientes', ['documento' => '900111', 'nombre' => 'Finca Vieja Actualizada', 'municipio' => 'Fusagasugá']);
        $this->assertDatabaseHas('clientes', ['documento' => '800222', 'tipo_cliente' => 'Cooperativa']);

        // La carga masiva también queda en la auditoría, a nombre de quien la hizo
        $creados = Activity::where('log_name', 'clientes')->where('event', 'created')->where('causer_id', $this->vendedor->id)->count();
        $this->assertSame(2, $creados);
    }

    /** I2. Clientes: las filas inválidas se omiten con su fila y columna; las válidas se guardan. */
    public function test_importar_clientes_valida_fila_por_fila(): void
    {
        $this->actingAs($this->vendedor)->post(route('clientes.importar.guardar'), [
            'archivo' => $this->csv(
                'nombre,tipo_cliente,documento,telefono,municipio',
                'Sin tipo,,111,,X',            // fila 2: falta el tipo
                ',Natural,222,,Y',             // fila 3: falta el nombre
                'Tipo malo,Robot,333,,Z',      // fila 4: tipo inexistente
                'Sin documento,Natural,,,W',   // fila 5: falta el documento
                'Cliente bueno,Natural,444,,Q', // fila 6: válida
            ),
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '1 cliente(s) creado(s)'));

        $fallas = session('fallas');
        $this->assertCount(4, $fallas);
        $this->assertEqualsCanonicalizing([2, 3, 4, 5], collect($fallas)->pluck('fila')->all());
        $this->assertEqualsCanonicalizing(['tipo_cliente', 'nombre', 'tipo_cliente', 'documento'], collect($fallas)->pluck('columna')->all());
        $this->assertSame(1, Cliente::count());
        $this->assertDatabaseHas('clientes', ['documento' => '444']);
    }

    /** I3. Los números de Excel (documentos, teléfonos) llegan como número y se convierten a texto limpio. */
    public function test_las_celdas_numericas_de_excel_se_convierten_a_texto(): void
    {
        $cliente = (new ClientesImport)->prepareForValidation([
            'nombre' => '  Finca El Sol ', 'tipo_cliente' => 'finca', 'documento' => 1234567890.0, 'telefono' => 3001234567, 'municipio' => null,
        ], 0);

        $this->assertSame('Finca El Sol', $cliente['nombre']);
        $this->assertSame('Finca', $cliente['tipo_cliente']);
        $this->assertSame('1234567890', $cliente['documento']);
        $this->assertSame('3001234567', $cliente['telefono']);
        $this->assertNull($cliente['municipio']);

        $proveedor = (new ProveedoresImport)->prepareForValidation(['nombre' => 'Semillas', 'nit' => 900123456.0, 'telefono' => 3201112233], 0);
        $this->assertSame('900123456', $proveedor['nit']);
        $this->assertSame('3201112233', $proveedor['telefono']);
    }

    /** I4. Un cliente que está en la papelera no se duplica: la fila se omite con un mensaje claro. */
    public function test_un_cliente_en_la_papelera_no_se_duplica(): void
    {
        Cliente::create(['nombre' => 'Eliminado', 'tipo_cliente' => 'Natural', 'documento' => '555'])->delete();

        $this->actingAs($this->vendedor)->post(route('clientes.importar.guardar'), [
            'archivo' => $this->csv('nombre,tipo_cliente,documento', 'Eliminado de nuevo,Natural,555'),
        ]);

        $fallas = session('fallas');
        $this->assertCount(1, $fallas);
        $this->assertStringContainsString('papelera', implode(' ', $fallas[0]['errores']));
        $this->assertSame(1, Cliente::withTrashed()->count());
    }

    /** I5. Proveedores: actualiza por NIT, exige el NIT y no revive los de la papelera. */
    public function test_importar_proveedores_actualiza_por_nit_y_valida(): void
    {
        Proveedor::create(['nombre' => 'Viejo Nombre', 'nit' => '900111']);
        Proveedor::create(['nombre' => 'Eliminado', 'nit' => '777'])->delete();

        $this->actingAs($this->almacenista)->post(route('proveedores.importar.guardar'), [
            'archivo' => $this->csv(
                'nombre,nit,telefono,direccion',
                'Nombre Nuevo,900111,3201112233,Calle 1',   // existe → actualiza
                'Agro Sur,800222,,Km 3',                    // nueva
                'Sin NIT,,3000000000,Calle 2',              // fila 4: falta el NIT
                'Revivir,777,,',                            // fila 5: está en la papelera
            ),
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '1 proveedor(s) creado(s) y 1 actualizado(s)'));

        $this->assertEqualsCanonicalizing([4, 5], collect(session('fallas'))->pluck('fila')->all());
        $this->assertDatabaseHas('proveedores', ['nit' => '900111', 'nombre' => 'Nombre Nuevo', 'telefono' => '3201112233']);
        $this->assertDatabaseHas('proveedores', ['nit' => '800222']);
        $this->assertSame(2, Proveedor::count());
    }

    /** I6. Cada rol carga lo que administra: el contador no carga nada. */
    public function test_los_permisos_de_importacion_por_rol(): void
    {
        $contador = $this->usuario('contador@test.com', ['contador']);

        $this->actingAs($this->vendedor)->get(route('clientes.importar'))->assertOk();
        $this->actingAs($this->vendedor)->get(route('proveedores.importar'))->assertForbidden();

        $this->actingAs($this->almacenista)->get(route('proveedores.importar'))->assertOk();
        $this->actingAs($this->almacenista)->get(route('clientes.importar'))->assertForbidden();

        $this->actingAs($contador)->get(route('clientes.importar'))->assertForbidden();
        $this->actingAs($contador)->get(route('proveedores.importar'))->assertForbidden();
        $this->actingAs($contador)->post(route('clientes.importar.guardar'), ['archivo' => $this->csv('nombre', 'x')])->assertForbidden();

        $this->actingAs($this->admin)->get(route('clientes.importar'))->assertOk();
        $this->actingAs($this->admin)->get(route('proveedores.importar'))->assertOk();
    }

    /** I7. Si el módulo se apaga en el panel, su carga masiva también deja de existir (404). */
    public function test_sin_el_modulo_no_hay_carga_masiva(): void
    {
        Modulo::where('clave', 'clientes')->firstOrFail()->update(['activo' => false]);
        Modulo::where('clave', 'proveedores')->firstOrFail()->update(['activo' => false]);

        $this->actingAs($this->admin)->get(route('clientes.importar'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('clientes.plantilla'))->assertNotFound();
        $this->actingAs($this->admin)->get(route('proveedores.importar'))->assertNotFound();
        $this->actingAs($this->admin)->post(route('proveedores.importar.guardar'), ['archivo' => $this->csv('nombre,nit', 'x,1')])->assertNotFound();
    }

    /** I8. El archivo es obligatorio y solo se admite Excel o CSV. */
    public function test_el_archivo_es_obligatorio_y_de_tipo_valido(): void
    {
        $this->actingAs($this->vendedor);

        $this->post(route('clientes.importar.guardar'), [])->assertSessionHasErrors('archivo');
        $this->post(route('clientes.importar.guardar'), ['archivo' => UploadedFile::fake()->create('datos.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors('archivo');
    }

    /** I9. Las plantillas se descargan y sus encabezados coinciden con lo que espera cada importación. */
    public function test_las_plantillas_se_descargan_con_los_encabezados_correctos(): void
    {
        $respuesta = $this->actingAs($this->vendedor)->get(route('clientes.plantilla'));
        $respuesta->assertOk();
        $this->assertStringContainsString('plantilla_clientes.xlsx', $respuesta->headers->get('content-disposition'));

        $respuesta = $this->actingAs($this->almacenista)->get(route('proveedores.plantilla'));
        $respuesta->assertOk();
        $this->assertStringContainsString('plantilla_proveedores.xlsx', $respuesta->headers->get('content-disposition'));

        $this->assertSame(['nombre', 'tipo_cliente', 'documento', 'telefono', 'municipio'], (new PlantillaClientesExport)->headings());
        $this->assertSame(['nombre', 'nit', 'telefono', 'direccion'], (new PlantillaProveedoresExport)->headings());
    }

    /** I10. Los listados muestran el botón de importar solo a quien tiene el permiso. */
    public function test_el_boton_de_importar_depende_del_permiso(): void
    {
        $this->actingAs($this->vendedor)->get(route('clientes.index'))
            ->assertSee('href="'.route('clientes.importar').'"', false);

        $this->actingAs($this->almacenista)->get(route('proveedores.index'))
            ->assertSee('href="'.route('proveedores.importar').'"', false);

        $this->actingAs($this->admin)->get(route('proveedores.index'))
            ->assertSee('href="'.route('proveedores.importar').'"', false);
    }
}
