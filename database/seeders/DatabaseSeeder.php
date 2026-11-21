<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Siembra TODO lo necesario para probar el sistema desde cero:
     *   sail artisan migrate:fresh --seed
     * Usuarios de prueba (contraseña: password): admin@, vendedor@, almacenista@, contador@, multirol@, supervisor@ test.com
     */
    public function run(): void
    {
        $this->call([
            ModuloSeeder::class,          // módulos activables (núcleo, inventario, clientes, ...)
            OrganizacionSeeder::class,    // identidad corporativa de la empresa (nombre, NIT, logo, color)
            RolePermissionSeeder::class,  // roles y permisos (Spatie)
            UserRoleSeeder::class,        // usuarios de prueba por rol
            CategoriaSeeder::class,       // 10 categorías
            ProveedorSeeder::class,       // 10 proveedores
            ClienteSeeder::class,         // 10 clientes
            ProductoSeeder::class,        // 10 productos
            PrecioProductoSeeder::class,  // precios de venta por categoría
            LoteSeeder::class,            // 10 lotes (uno por producto)
            MovimientosDemoSeeder::class, // compras (recibida, parcial, pendiente) y ventas (una anulada)
        ]);
    }
}
