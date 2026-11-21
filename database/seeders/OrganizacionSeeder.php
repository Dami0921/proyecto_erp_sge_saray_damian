<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use Illuminate\Database\Seeder;

class OrganizacionSeeder extends Seeder
{
    // Se puede repetir sin pisar la identidad que el administrador ya haya configurado
    public function run(): void
    {
        Organizacion::firstOrCreate([], Organizacion::porDefecto());
    }
}
