<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Proveedor;

class ProveedorSeeder extends Seeder
{
    public function run(): void
    {
        Proveedor::create(['nombre' => 'Agroquímicos del Valle', 'nit' => '900111222', 'telefono' => '3001112233', 'direccion' => 'Cra 10 #5-20']);
        Proveedor::create(['nombre' => 'Semillas del Campo', 'nit' => '900222333', 'telefono' => '3002223344', 'direccion' => 'Cll 8 #12-30']);
        Proveedor::create(['nombre' => 'FertiColombia', 'nit' => '900333444', 'telefono' => '3003334455', 'direccion' => 'Av. Siempre Viva 45']);
        Proveedor::create(['nombre' => 'Herramientas Andinas', 'nit' => '900444555', 'telefono' => '3004445566', 'direccion' => 'Cra 20 #15-10']);
        Proveedor::create(['nombre' => 'Riegos y Bombas S.A.S.', 'nit' => '900555666', 'telefono' => '3005556677', 'direccion' => 'Cll 30 #7-40']);
        Proveedor::create(['nombre' => 'Distribuidora Agrícola del Norte', 'nit' => '900666777', 'telefono' => '3006667788', 'direccion' => 'Cra 15 #22-05']);
        Proveedor::create(['nombre' => 'AgroQuímicos Nacionales', 'nit' => '900777888', 'telefono' => '3007778899', 'direccion' => 'Cll 45 #10-12']);
        Proveedor::create(['nombre' => 'Insumos y Fertilizantes S.A.', 'nit' => '900888999', 'telefono' => '3008889900', 'direccion' => 'Cra 8 #30-18']);
        Proveedor::create(['nombre' => 'Semillas Certificadas del Oriente', 'nit' => '900999000', 'telefono' => '3009990011', 'direccion' => 'Cll 12 #5-60']);
        Proveedor::create(['nombre' => 'Equipos y Herramientas del Campo', 'nit' => '901000111', 'telefono' => '3000001122', 'direccion' => 'Cra 25 #14-33']);
    }
}
