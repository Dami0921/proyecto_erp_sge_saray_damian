<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar la caché de permisos de Spatie
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permisos = [
            'ver-productos', 'crear-productos', 'editar-productos', 'eliminar-productos',
            'ver-categorias', 'crear-categorias', 'editar-categorias', 'eliminar-categorias',
            'ver-proveedores', 'crear-proveedores', 'editar-proveedores', 'eliminar-proveedores',
            'ver-clientes', 'crear-clientes', 'editar-clientes', 'eliminar-clientes',
            'ver-lotes', 'crear-lotes', 'editar-lotes', 'eliminar-lotes',
            // Ventas (eliminar-ventas = anular)
            'ver-ventas', 'crear-ventas', 'editar-ventas', 'eliminar-ventas',
            // Compras (eliminar-compras = anular; recibir-compras = registrar llegada de mercancía)
            'ver-compras', 'crear-compras', 'editar-compras', 'eliminar-compras', 'recibir-compras',
            'ver-reportes',
            'gestionar-modulos',
        ];

        // findOrCreate: si el permiso ya existe no lo duplica (el seeder se puede repetir)
        foreach ($permisos as $permiso) {
            Permission::findOrCreate($permiso);
        }

        // syncPermissions: deja al rol exactamente con estos permisos
        Role::findOrCreate('admin')->syncPermissions(Permission::all());

        Role::findOrCreate('vendedor')->syncPermissions([
            'ver-productos',
            'ver-clientes', 'crear-clientes', 'editar-clientes',
            'ver-ventas', 'crear-ventas',
        ]);

        Role::findOrCreate('almacenista')->syncPermissions([
            'ver-productos', 'crear-productos', 'editar-productos',
            'ver-categorias',
            'ver-proveedores', 'crear-proveedores', 'editar-proveedores',
            'ver-lotes', 'crear-lotes', 'editar-lotes',
            'ver-compras', 'crear-compras', 'recibir-compras',
        ]);
    }
}
