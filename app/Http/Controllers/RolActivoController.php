<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Rol activo: un usuario con varios roles trabaja con UNO a la vez. Cambiar de rol cambia
 * sus permisos de inmediato (menú, botones y rutas) sin cerrar sesión.
 */
class RolActivoController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $roles = $request->user()->getRoleNames();

        // Con 0 o 1 rol no hay nada que elegir
        if ($roles->count() < 2) {
            return redirect(RouteServiceProvider::HOME);
        }

        return view('auth.seleccionar-rol', [
            'roles' => $roles,
            'activo' => $request->user()->rolActivo(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $datos = $request->validate([
            // Solo se puede elegir un rol que el usuario realmente tiene
            'rol' => ['required', 'string', Rule::in($user->getRoleNames()->all())],
        ], [
            'rol.in' => 'Ese rol no está asignado a tu usuario.',
            'rol.required' => 'Elige un rol para continuar.',
        ]);

        $request->session()->put(User::SESION_ROL_ACTIVO, $datos['rol']);

        return redirect()->intended(RouteServiceProvider::HOME)
            ->with('success', "Ahora trabajas con el rol «{$datos['rol']}».");
    }
}
