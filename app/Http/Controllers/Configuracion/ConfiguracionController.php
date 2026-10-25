<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Models\User;
use Spatie\Permission\Models\Role;

class ConfiguracionController extends Controller
{
    // Puerta de entrada: la ve quien administre usuarios, roles o módulos (middleware en la ruta)
    public function __invoke()
    {
        return view('configuracion.index', [
            'totalUsuarios' => User::count(),
            'totalRoles' => Role::count(),
        ]);
    }
}
