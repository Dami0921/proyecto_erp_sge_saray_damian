<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserRoleSeeder extends Seeder
{
    // Requiere haber ejecutado antes RolePermissionSeeder. Se puede repetir sin duplicar.
    public function run(): void
    {
        $usuarios = [
            ['name' => 'Admin Prueba', 'email' => 'admin@test.com', 'rol' => 'admin'],
            ['name' => 'Vendedor Prueba', 'email' => 'vendedor@test.com', 'rol' => 'vendedor'],
            ['name' => 'Almacenista Prueba', 'email' => 'almacenista@test.com', 'rol' => 'almacenista'],
        ];

        foreach ($usuarios as $dato) {
            $user = User::firstOrCreate(
                ['email' => $dato['email']],
                ['name' => $dato['name'], 'password' => 'password'] // el cast "hashed" la encripta
            );
            $user->syncRoles($dato['rol']);
        }
    }
}
