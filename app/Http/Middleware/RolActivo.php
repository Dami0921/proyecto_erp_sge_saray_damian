<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exige que un usuario con varios roles elija con cuál va a trabajar (rol activo).
 * Uso en rutas: ->middleware('rol.activo')
 *
 *  - 0 roles  -> pasa (verá el panel vacío y el aviso de «sin rol asignado»).
 *  - 1 rol    -> ese es su rol activo automáticamente.
 *  - 2+ roles -> si aún no eligió, se le lleva a la pantalla de selección.
 */
class RolActivo
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $roles = $user->roles;

        if ($roles->count() === 1) {
            $request->session()->put(User::SESION_ROL_ACTIVO, $roles->first()->name);
        } elseif ($roles->count() > 1 && $user->rolActivo() === null) {
            return redirect()->route('rol.seleccionar');
        }

        return $next($request);
    }
}
