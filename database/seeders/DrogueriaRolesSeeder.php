<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DrogueriaRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la caché de permisos de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            // Productos
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            // Lotes
            'ver-lotes', 'crear-lotes', 'editar-lotes', 'dar-baja-lotes',
            // Proveedores
            'ver-proveedores', 'crear-proveedores', 'editar-proveedores',
            // Clientes
            'ver-clientes', 'crear-clientes', 'editar-clientes',
            // Compras
            'ver-compras', 'crear-compras', 'editar-compras', 'eliminar-compras',
            // Recepción
            'recibir-lotes',
            // Ventas
            'ver-ventas', 'crear-ventas', 'anular-ventas',
            // Control especial
            'dispensar-control-especial',
            // Reportes
            'ver-reportes',
        ];

        // findOrCreate: si el permiso ya existe no lo duplica
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        // Admin: todo menos recibir-lotes (quien compra no recibe)
        Role::findOrCreate('admin')->syncPermissions(
            array_values(array_diff($permissions, ['recibir-lotes']))
        );

        Role::findOrCreate('regente')->syncPermissions([
            'ver-productos', 'editar-productos',
            'ver-lotes', 'crear-lotes', 'editar-lotes', 'dar-baja-lotes',
            'ver-proveedores',
            'ver-compras', 'recibir-lotes',
            'ver-ventas', 'dispensar-control-especial',
            'ver-reportes',
        ]);

        Role::findOrCreate('cajero')->syncPermissions([
            'ver-productos', 'ver-lotes',
            'ver-clientes', 'crear-clientes',
            'ver-ventas', 'crear-ventas',
        ]);

        Role::findOrCreate('auxiliar-bodega')->syncPermissions([
            'ver-productos', 'ver-lotes',
            'ver-compras',
        ]);
    }
}
