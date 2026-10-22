<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

class ModuloSeeder extends Seeder
{
    // Se puede repetir sin duplicar y sin cambiar el estado activo/inactivo de los módulos
    public function run(): void
    {
        $modulos = [
            ['clave' => 'nucleo',      'nombre' => 'Núcleo (usuarios, roles y panel)',   'es_nucleo' => true],
            ['clave' => 'inventario',  'nombre' => 'Inventario (productos, categorías y lotes)', 'es_nucleo' => false],
            ['clave' => 'proveedores', 'nombre' => 'Proveedores',                        'es_nucleo' => false],
            ['clave' => 'clientes',    'nombre' => 'Clientes',                           'es_nucleo' => false],
            ['clave' => 'ventas',      'nombre' => 'Ventas (requiere Inventario)',       'es_nucleo' => false],
            ['clave' => 'compras',     'nombre' => 'Compras (requiere Inventario)',      'es_nucleo' => false],
        ];

        foreach ($modulos as $m) {
            Modulo::updateOrCreate(
                ['clave' => $m['clave']],
                ['nombre' => $m['nombre'], 'es_nucleo' => $m['es_nucleo']]
            );
        }
    }
}
