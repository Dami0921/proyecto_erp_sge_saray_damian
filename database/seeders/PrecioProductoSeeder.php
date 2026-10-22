<?php

namespace Database\Seeders;

use App\Models\Producto;
use Illuminate\Database\Seeder;

class PrecioProductoSeeder extends Seeder
{
    // Precios de partida para la demo, por categoría. Solo toca productos con precio 0
    // (se pueden editar después desde Productos). Se puede repetir sin problema.
    public function run(): void
    {
        $porCategoria = [
            'Semillas' => 18000,
            'Fertilizantes' => 85000,
            'Agroquímicos' => 62000,
            'Herramientas' => 45000,
            'Riego' => 38000,
        ];

        Producto::with('categoria')->where('precio_venta', 0)->get()->each(function (Producto $p) use ($porCategoria) {
            $p->update(['precio_venta' => $porCategoria[$p->categoria?->nombre] ?? 25000]);
        });
    }
}
