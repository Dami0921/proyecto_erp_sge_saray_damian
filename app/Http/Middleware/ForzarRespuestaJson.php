<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Toda la API responde JSON, aunque el cliente olvide la cabecera «Accept: application/json»
 * (sin ella, Laravel respondería con una redirección HTML al login).
 */
class ForzarRespuestaJson
{
    public function handle(Request $request, Closure $next): Response
    {
        $request->headers->set('Accept', 'application/json');

        return $next($request);
    }
}
