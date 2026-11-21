<?php

namespace Tests\Feature\Reglas;

use App\Models\Modulo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Configuración → Usuarios → Permisos: qué puede hacer cada persona y de dónde viene cada permiso.
 */
class PermisosUsuarioTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    /** P1. Solo quien administra usuarios ve los permisos de los demás. */
    public function test_solo_quien_gestiona_usuarios_ve_los_permisos(): void
    {
        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $this->vendedor))->assertOk();

        $this->actingAs($this->vendedor)->get(route('configuracion.usuarios.permisos', $this->almacenista))->assertForbidden();
        $this->actingAs($this->almacenista)->get(route('configuracion.usuarios.permisos', $this->vendedor))->assertForbidden();
    }

    /** P2. Se ven los roles, los módulos con acceso y los que no. */
    public function test_muestra_roles_y_modulos_de_un_usuario(): void
    {
        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $this->vendedor))
            ->assertOk()
            ->assertSee($this->vendedor->email)
            ->assertSee('Rol vendedor')
            ->assertSee('Ventas')
            ->assertSee('Clientes')
            ->assertSee('Sin ningún permiso en:')
            ->assertSee('Compras');   // aparece en la lista de módulos sin acceso
    }

    /** P3. Un permiso directo aparece en su propia columna, distinto del rol. */
    public function test_un_permiso_directo_se_distingue_del_del_rol(): void
    {
        $supervisor = $this->usuario('sup@test.com', ['vendedor'], ['eliminar-ventas']);

        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $supervisor))
            ->assertOk()
            ->assertSee('Directos')
            ->assertSee('Eliminar / anular');   // solo existe como permiso directo: el rol vendedor no lo trae
    }

    /** P4. Con varios roles se muestra una columna por rol y se explica el rol activo. */
    public function test_con_varios_roles_hay_una_columna_por_rol(): void
    {
        $multi = $this->usuario('multi@test.com', ['vendedor', 'almacenista']);

        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $multi))
            ->assertOk()
            ->assertSee('Rol vendedor')
            ->assertSee('Rol almacenista')
            ->assertSee('rol activo');
    }

    /** P5. Si un módulo está apagado, se avisa en la fila correspondiente. */
    public function test_avisa_cuando_el_modulo_esta_desactivado(): void
    {
        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $this->vendedor))
            ->assertDontSee('módulo desactivado');

        Modulo::where('clave', 'ventas')->firstOrFail()->update(['activo' => false]);

        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $this->vendedor))
            ->assertSee('módulo desactivado');
    }

    /** P6. También se consultan los permisos de un usuario en la papelera. */
    public function test_se_ven_los_permisos_de_un_usuario_en_la_papelera(): void
    {
        $this->vendedor->delete();

        $this->actingAs($this->admin)->get(route('configuracion.usuarios.permisos', $this->vendedor))
            ->assertOk()
            ->assertSee('en la papelera');
    }

    /** P7. La lista de usuarios enlaza a esta pantalla. */
    public function test_la_lista_de_usuarios_enlaza_a_los_permisos(): void
    {
        $this->actingAs($this->admin)->get(route('configuracion.usuarios.index'))
            ->assertOk()
            ->assertSee('href="'.route('configuracion.usuarios.permisos', $this->vendedor).'"', false);
    }
}
