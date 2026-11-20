<?php

namespace Tests\Feature\Reglas;

use App\Models\User;
use App\Services\SeguridadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\Concerns\PreparaEntorno;
use Tests\TestCase;

/**
 * Clase 15: cuarto rol («contador», de solo consulta). Quien registra no audita.
 */
class Clase15Test extends TestCase
{
    use PreparaEntorno, RefreshDatabase;

    private User $contador;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prepararEntorno();
        $this->contador = $this->usuario('contador@test.com', ['contador']);
    }

    /** R1. El sistema tiene al menos 4 roles y el contador existe con sus permisos de consulta. */
    public function test_existen_cuatro_roles_y_el_contador_solo_consulta(): void
    {
        $this->assertGreaterThanOrEqual(4, Role::count());

        $permisos = Role::findByName('contador')->permissions->pluck('name');
        $this->assertTrue($permisos->contains('ver-kardex'));
        $this->assertTrue($permisos->contains('ver-auditoria'));
        $this->assertTrue($permisos->contains('exportar-ventas'));
        // Ningún permiso de escritura
        $escritura = $permisos->filter(fn ($p) => preg_match('/^(crear|editar|eliminar|recibir|ajustar|importar|gestionar)-/', $p));
        $this->assertSame([], $escritura->values()->all());
    }

    /** R2. El contador consulta ventas, kardex y auditoría, y exporta a Excel. */
    public function test_el_contador_consulta_y_exporta(): void
    {
        $this->actingAs($this->contador);

        $this->get(route('ventas.index'))->assertOk();
        $this->get(route('compras.index'))->assertOk();
        $this->get(route('inventario.kardex'))->assertOk();
        $this->get(route('auditoria.index'))->assertOk();
        $this->get(route('ventas.exportar'))->assertOk();
        $this->get(route('productos.exportar'))->assertOk();
    }

    /** R3. El contador no puede registrar, ajustar, importar ni configurar. */
    public function test_el_contador_no_puede_modificar_nada(): void
    {
        $p = $this->producto();
        $this->actingAs($this->contador);

        $this->get(route('ventas.create'))->assertForbidden();
        $this->get(route('compras.create'))->assertForbidden();
        $this->get(route('inventario.ajustar', $p))->assertForbidden();
        $this->get(route('productos.importar'))->assertForbidden();
        $this->get(route('configuracion.index'))->assertForbidden();
    }

    /** R4. El contador no recibe alertas de compra (no crea órdenes). */
    public function test_el_contador_no_recibe_alertas_de_stock(): void
    {
        $this->assertFalse($this->contador->can('crear-compras'));
        $this->assertTrue($this->contador->can('ver-lotes'));
    }

    /** R5. Los 4 roles base están protegidos: no se pueden eliminar. */
    public function test_el_rol_contador_es_base_y_no_se_elimina(): void
    {
        $this->assertContains('contador', SeguridadService::ROLES_BASE);
    }
}
