<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cliente;

class ClienteSeeder extends Seeder
{
    public function run(): void
    {
        Cliente::create(['nombre' => 'Juan Pérez', 'tipo_cliente' => 'Natural', 'documento' => '1001234567', 'telefono' => '3101112233', 'municipio' => 'Cúcuta']);
        Cliente::create(['nombre' => 'Finca La Esperanza', 'tipo_cliente' => 'Empresa', 'documento' => '900987654', 'telefono' => '3102223344', 'municipio' => 'Los Patios']);
        Cliente::create(['nombre' => 'María Gómez', 'tipo_cliente' => 'Natural', 'documento' => '1002345678', 'telefono' => '3103334455', 'municipio' => 'Villa del Rosario']);
        Cliente::create(['nombre' => 'Cultivos del Norte S.A.S.', 'tipo_cliente' => 'Empresa', 'documento' => '901123456', 'telefono' => '3104445566', 'municipio' => 'Pamplona']);
        Cliente::create(['nombre' => 'Carlos Rodríguez', 'tipo_cliente' => 'Natural', 'documento' => '1003456789', 'telefono' => '3105556677', 'municipio' => 'Ocaña']);
        Cliente::create(['nombre' => 'Cooperativa Agrícola El Cultivador', 'tipo_cliente' => 'Cooperativa', 'documento' => '901234567', 'telefono' => '3106667788', 'municipio' => 'Cúcuta']);
        Cliente::create(['nombre' => 'Finca Buenavista', 'tipo_cliente' => 'Empresa', 'documento' => '901345678', 'telefono' => '3107778899', 'municipio' => 'Chinácota']);
        Cliente::create(['nombre' => 'Laura Sánchez', 'tipo_cliente' => 'Natural', 'documento' => '1004567890', 'telefono' => '3108889900', 'municipio' => 'Bochalema']);
        Cliente::create(['nombre' => 'Cooperativa Campesina del Norte', 'tipo_cliente' => 'Cooperativa', 'documento' => '901456789', 'telefono' => '3109990011', 'municipio' => 'Pamplonita']);
        Cliente::create(['nombre' => 'Andrés Martínez', 'tipo_cliente' => 'Natural', 'documento' => '1005678901', 'telefono' => '3100001122', 'municipio' => 'Chinácota']);
    }
}
