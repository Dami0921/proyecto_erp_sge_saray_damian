<?php

namespace App\Http\Middleware;

use App\Models\Modulo;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ModuloActivo
{
    // Uso en rutas: ->middleware('modulo:inventario')
    public function handle(Request $request, Closure $next, string $clave): Response
    {
        abort_unless(Modulo::activo($clave), 404, 'Este módulo está desactivado.');

        return $next($request);
    }
}
