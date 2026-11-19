<?php

namespace Tests\Feature\Reglas;

use App\Models\User;
use App\Services\SeguridadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Seguridad: roles por puesto, rol activo, permisos directos, Configuración y reglas de protección.
 */
class SeguridadTest extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
    }

    // ---------------------------------------------------------------- Control de acceso

    public function test_un_invitado_es_enviado_al_login(): void
    {
        $this->get(route('ventas.index'))->assertRedirect(route('login'));
    }

    public function test_cada_rol_solo_entra_a_sus_modulos(): void
    {
        $this->actingAs($this->vendedor)->get(route('ventas.index'))->assertOk();
        $this->actingAs($this->vendedor)->get(route('compras.index'))->assertForbidden();

        $this->actingAs($this->almacenista)->get(route('compras.index'))->assertOk();
        $this->actingAs($this->almacenista)->get(route('ventas.index'))->assertForbidden();
    }

    public function test_el_error_403_se_muestra_en_espanol_con_el_rol_del_usuario(): void
    {
        $this->actingAs($this->vendedor)->get(route('compras.index'))
            ->assertForbidden()
            ->assertSee('No tienes permiso para esta acción')
            ->assertSee('vendedor');
    }

    public function test_solo_el_admin_entra_a_configuracion(): void
    {
        $this->actingAs($this->vendedor)->get(route('configuracion.index'))->assertForbidden();
        $this->actingAs($this->vendedor)->get(route('configuracion.usuarios.index'))->assertForbidden();
        $this->actingAs($this->almacenista)->get(route('configuracion.roles.index'))->assertForbidden();

        $this->actingAs($this->admin)->get(route('configuracion.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('configuracion.usuarios.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('configuracion.roles.index'))->assertOk();
    }

    // ---------------------------------------------------------------- Rol activo

    public function test_con_varios_roles_hay_que_elegir_el_rol_activo(): void
    {
        $multi = $this->usuario('multi@test.com', ['vendedor', 'almacenista']);

        // Sin elegir rol, cualquier pantalla lo lleva a la selección
        $this->actingAs($multi)->get(route('dashboard'))->assertRedirect(route('rol.seleccionar'));
        $this->actingAs($multi)->get(route('ventas.index'))->assertRedirect(route('rol.seleccionar'));
        $this->actingAs($multi)->get(route('rol.seleccionar'))->assertOk()->assertSee('Elige tu rol de trabajo');
    }

    public function test_el_rol_activo_limita_los_permisos_y_se_puede_cambiar(): void
    {
        $multi = $this->usuario('multi@test.com', ['vendedor', 'almacenista']);

        // Rol activo: vendedor -> ve ventas, NO compras
        $this->actingAs($multi)->post(route('rol.cambiar'), ['rol' => 'vendedor'])->assertRedirect();
        $this->get(route('ventas.index'))->assertOk();
        $this->get(route('compras.index'))->assertForbidden();

        // Cambia a almacenista (sin cerrar sesión) -> ahora al revés
        $this->post(route('rol.cambiar'), ['rol' => 'almacenista']);
        $this->get(route('compras.index'))->assertOk();
        $this->get(route('ventas.index'))->assertForbidden();
    }

    public function test_no_se_puede_elegir_un_rol_que_no_se_tiene(): void
    {
        $multi = $this->usuario('multi@test.com', ['vendedor', 'almacenista']);

        $this->actingAs($multi)->post(route('rol.cambiar'), ['rol' => 'admin'])->assertSessionHasErrors('rol');
        $this->get(route('configuracion.index'))->assertRedirect(route('rol.seleccionar'));
    }

    public function test_con_un_solo_rol_no_se_pide_elegir(): void
    {
        $this->actingAs($this->vendedor)->get(route('dashboard'))->assertOk();
    }

    // ---------------------------------------------------------------- Permiso directo

    public function test_un_permiso_directo_se_suma_al_rol(): void
    {
        $supervisor = $this->usuario('sup@test.com', ['vendedor'], ['eliminar-ventas']);

        $this->assertTrue($supervisor->can('eliminar-ventas'));   // directo
        $this->assertTrue($supervisor->can('crear-ventas'));      // del rol
        $this->assertFalse($this->vendedor->can('eliminar-ventas')); // el vendedor «normal» no puede anular
    }

    public function test_el_permiso_directo_vale_sin_importar_el_rol_activo(): void
    {
        $multi = $this->usuario('multi@test.com', ['vendedor', 'almacenista'], ['ver-reportes']);

        $this->actingAs($multi)->post(route('rol.cambiar'), ['rol' => 'almacenista']);

        $this->assertTrue($multi->fresh()->can('ver-reportes'));  // directo: sigue valiendo
        $this->assertFalse($multi->fresh()->can('crear-ventas')); // del rol vendedor: no es el activo
    }

    // ---------------------------------------------------------------- Matriz de permisos

    public function test_el_admin_edita_la_matriz_pero_conserva_todos_los_permisos(): void
    {
        $vendedor = Role::findByName('vendedor');
        $admin = Role::findByName('admin');
        $totalPermisos = Permission::count();

        $this->actingAs($this->admin)->put(route('configuracion.roles.update'), [
            'permisos' => [
                $vendedor->id => ['ver-productos', 'ver-ventas'],   // se le quitan crear-ventas, clientes...
                $admin->id => [],                                    // intento de dejar al admin sin permisos
            ],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(['ver-productos', 'ver-ventas'], $vendedor->fresh()->permissions->pluck('name')->all());
        $this->assertCount($totalPermisos, $admin->fresh()->permissions);   // el admin NO pierde permisos
    }

    // ---------------------------------------------------------------- Reglas de protección

    public function test_no_se_puede_eliminar_al_unico_administrador(): void
    {
        $servicio = app(SeguridadService::class);
        $otro = $this->usuario('otro@test.com', ['vendedor']);

        // Aunque lo intente otro usuario, el único admin no se puede borrar
        try {
            $servicio->eliminarUsuario($this->admin, $otro);
            $this->fail('Debió lanzar ValidationException.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('único administrador', $e->errors()['usuario'][0]);
        }
        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }

    public function test_nadie_puede_eliminar_su_propia_cuenta(): void
    {
        $this->usuario('admin2@test.com', ['admin']);   // hay otro admin: la regla es «no a ti mismo»

        $this->actingAs($this->admin)
            ->delete(route('configuracion.usuarios.destroy', $this->admin))
            ->assertSessionHas('error');

        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }

    public function test_el_unico_admin_no_puede_eliminar_su_cuenta_desde_el_perfil(): void
    {
        $this->actingAs($this->admin)
            ->delete('/profile', ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertNotSoftDeleted('users', ['id' => $this->admin->id]);
    }

    public function test_un_admin_no_puede_quitarse_a_si_mismo_el_rol_admin(): void
    {
        $this->usuario('admin2@test.com', ['admin']);

        $this->actingAs($this->admin)->put(route('configuracion.usuarios.update', $this->admin), [
            'name' => $this->admin->name, 'email' => $this->admin->email, 'roles' => ['vendedor'],
        ])->assertSessionHasErrors('roles');

        $this->assertTrue($this->admin->fresh()->hasRole('admin'));
    }

    public function test_no_se_puede_quitar_el_rol_admin_al_ultimo_administrador(): void
    {
        $this->expectException(ValidationException::class);
        app(SeguridadService::class)->actualizarUsuario(
            $this->admin,
            ['name' => 'x', 'email' => 'admin@test.com', 'roles' => ['vendedor']],
            $this->usuario('otro@test.com', ['vendedor'])
        );
    }

    public function test_todo_usuario_debe_tener_al_menos_un_rol(): void
    {
        $this->actingAs($this->admin)->post(route('configuracion.usuarios.store'), [
            'name' => 'Sin rol', 'email' => 'sinrol@test.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertSessionHasErrors('roles');

        $this->assertDatabaseMissing('users', ['email' => 'sinrol@test.com']);
    }

    public function test_los_roles_base_y_los_roles_con_usuarios_no_se_eliminan(): void
    {
        $servicio = app(SeguridadService::class);

        // Rol base
        try {
            $servicio->eliminarRol(Role::findByName('vendedor'));
            $this->fail('Un rol base no debe eliminarse.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('rol base', $e->errors()['rol'][0]);
        }

        // Rol propio con un usuario asignado
        $contador = $servicio->crearRol('contador');
        $this->usuario('conta@test.com', ['contador']);
        $this->expectException(ValidationException::class);
        $servicio->eliminarRol($contador);
    }

    // ---------------------------------------------------------------- Usuarios: papelera y creación

    public function test_un_usuario_eliminado_va_a_la_papelera_y_no_puede_iniciar_sesion(): void
    {
        $victima = $this->usuario('victima@test.com', ['vendedor']);

        $this->actingAs($this->admin)->delete(route('configuracion.usuarios.destroy', $victima))->assertSessionHas('success');
        $this->assertSoftDeleted('users', ['id' => $victima->id]);

        auth()->logout();
        $this->post('/login', ['email' => 'victima@test.com', 'password' => 'password'])->assertSessionHasErrors();
        $this->assertGuest();

        // Restaurar lo devuelve a la vida
        $this->actingAs($this->admin)->patch(route('configuracion.usuarios.restore', $victima->id))->assertRedirect();
        $this->assertNotSoftDeleted('users', ['id' => $victima->id]);
    }

    public function test_el_admin_crea_un_usuario_con_varios_roles_y_permiso_directo(): void
    {
        $this->actingAs($this->admin)->post(route('configuracion.usuarios.store'), [
            'name' => 'Nuevo Empleado', 'email' => 'nuevo@test.com',
            'password' => 'password', 'password_confirmation' => 'password',
            'roles' => ['vendedor', 'almacenista'], 'permisos' => ['ver-reportes'],
        ])->assertRedirect(route('configuracion.usuarios.index'));

        $nuevo = User::where('email', 'nuevo@test.com')->firstOrFail();
        $this->assertEqualsCanonicalizing(['vendedor', 'almacenista'], $nuevo->getRoleNames()->all());
        $this->assertEquals(['ver-reportes'], $nuevo->getDirectPermissions()->pluck('name')->all());
    }
}
