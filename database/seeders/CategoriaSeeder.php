<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    public function run(): void
    {
        Categoria::create(['nombre' => 'Semillas']);
        Categoria::create(['nombre' => 'Fertilizantes']);
        Categoria::create(['nombre' => 'Agroquímicos']);
        Categoria::create(['nombre' => 'Herramientas']);
        Categoria::create(['nombre' => 'Riego']);
        Categoria::create(['nombre' => 'Fungicidas']);
        Categoria::create(['nombre' => 'Insecticidas']);
        Categoria::create(['nombre' => 'Abonos orgánicos']);
        Categoria::create(['nombre' => 'Equipos de protección']);
        Categoria::create(['nombre' => 'Empaques y embalajes']);
    }
}
