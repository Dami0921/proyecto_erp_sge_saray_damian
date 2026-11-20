<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    // Requiere haber ejecutado antes RolePermissionSeeder. Se puede repetir sin duplicar.
    // Contraseña de TODOS los usuarios de prueba: password
    public function run(): void
    {
        $usuarios = [
            // Un rol cada uno
            ['name' => 'Admin Prueba', 'email' => 'admin@test.com', 'roles' => ['admin']],
            ['name' => 'Vendedor Prueba', 'email' => 'vendedor@test.com', 'roles' => ['vendedor']],
            ['name' => 'Almacenista Prueba', 'email' => 'almacenista@test.com', 'roles' => ['almacenista']],
            ['name' => 'Contador Prueba', 'email' => 'contador@test.com', 'roles' => ['contador']],

            // Varios roles: al iniciar sesión debe elegir el rol activo (vendedor o almacenista)
            ['name' => 'Multirol Prueba', 'email' => 'multirol@test.com', 'roles' => ['vendedor', 'almacenista']],

            // Permiso directo: es vendedor, pero además puede anular ventas (eliminar-ventas),
            // permiso que el rol vendedor NO tiene. Se conserva sin importar el rol activo.
            ['name' => 'Supervisor de Ventas', 'email' => 'supervisor@test.com', 'roles' => ['vendedor'], 'permisos' => ['eliminar-ventas']],
        ];

        foreach ($usuarios as $dato) {
            $user = User::firstOrCreate(
                ['email' => $dato['email']],
                ['name' => $dato['name'], 'password' => 'password'] // el cast "hashed" la encripta
            );
            $user->syncRoles($dato['roles']);
            $user->syncPermissions($dato['permisos'] ?? []);
        }
    }
}
