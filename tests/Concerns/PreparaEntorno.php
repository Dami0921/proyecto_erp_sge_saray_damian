<?php

namespace Tests\Concerns;

use App\Models\Categoria;
use App\Models\Lote;
use App\Models\Producto;
use App\Models\User;
use Database\Seeders\ModuloSeeder;
use Database\Seeders\RolePermissionSeeder;

/**
 * Datos mínimos para probar las reglas de negocio: módulos, roles/permisos, usuarios por rol,
 * y un producto con lotes. Se usa junto con RefreshDatabase.
 */
trait PreparaEntorno
{
    protected User $admin;

    protected User $vendedor;

    protected User $almacenista;

    protected function prepararEntorno(): void
    {
        $this->seed([ModuloSeeder::class, RolePermissionSeeder::class]);

        $this->admin = $this->usuario('admin@test.com', ['admin']);
        $this->vendedor = $this->usuario('vendedor@test.com', ['vendedor']);
        $this->almacenista = $this->usuario('almacenista@test.com', ['almacenista']);
    }

    protected function usuario(string $email, array $roles, array $permisosDirectos = []): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->syncRoles($roles);
        $user->syncPermissions($permisosDirectos);

        return $user;
    }

    protected function producto(string $nombre = 'Urea 46%', float $precio = 10000, float $iva = 0): Producto
    {
        $categoria = Categoria::firstOrCreate(['nombre' => 'Fertilizantes']);

        return Producto::create([
            'nombre' => $nombre, 'categoria_id' => $categoria->id, 'unidad_medida' => 'bulto',
            'precio_venta' => $precio, 'iva_porcentaje' => $iva,
        ]);
    }

    protected function lote(Producto $producto, int $cantidad, ?string $vence = null, string $numero = 'L-1'): Lote
    {
        return Lote::create([
            'producto_id' => $producto->id, 'numero_lote' => $numero,
            'fecha_ingreso' => today(), 'fecha_vencimiento' => $vence ?? today()->addMonths(6)->toDateString(),
            'cantidad_inicial' => $cantidad, 'cantidad_actual' => $cantidad, 'costo_unitario' => 5000,
        ]);
    }

    protected function stock(Producto $producto): int
    {
        return (int) Lote::where('producto_id', $producto->id)->sum('cantidad_actual');
    }
}
