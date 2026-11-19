<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/login → entrega un token Bearer.
     * Si el usuario tiene varios roles debe indicar con cuál trabajará («rol»), igual que en la
     * pantalla de selección de rol del navegador. El rol viaja DENTRO del token como habilidad «rol:vendedor».
     */
    public function login(Request $request)
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'], // ej.: "app-movil-bodega"
            'rol' => ['nullable', 'string'],
        ]);

        // Los usuarios en la papelera (inactivos) no se encuentran: no pueden entrar
        $user = User::where('email', $datos['email'])->first();

        if (! $user || ! Hash::check($datos['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Credenciales incorrectas.']);
        }

        $roles = $user->getRoleNames();
        $rol = $datos['rol'] ?? ($roles->count() === 1 ? $roles->first() : null);

        if ($roles->count() > 1 && ! $rol) {
            throw ValidationException::withMessages([
                'rol' => 'Su usuario tiene varios roles. Indique con cuál desea trabajar: '.$roles->join(', ').'.',
            ]);
        }

        if ($rol && ! $roles->contains($rol)) {
            throw ValidationException::withMessages(['rol' => "El rol «{$rol}» no está asignado a su usuario."]);
        }

        $token = $user->createToken($datos['device_name'], $rol ? ["rol:{$rol}"] : []);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'rol_activo' => $rol,
        ], 201);
    }

    // GET /api/v1/me → quién soy y qué puedo hacer
    public function me(Request $request)
    {
        $user = $request->user();
        $nombreRol = $user->rolActivo();

        // Permisos efectivos = permisos del rol activo + permisos directos
        // Se toma del usuario (guard «web»): Role::findByName() usaría el guard «sanctum» de la petición y fallaría
        $permisos = ($nombreRol ? $user->roles->firstWhere('name', $nombreRol)?->permissions->pluck('name') : null) ?? collect();
        $permisos = $permisos
            ->merge($user->getDirectPermissions()->pluck('name'))
            ->unique()->sort()->values();

        return response()->json([
            'id' => $user->id,
            'nombre' => $user->name,
            'email' => $user->email,
            'rol_activo' => $nombreRol,
            'permisos' => $permisos,
        ]);
    }

    // POST /api/v1/logout → revoca SOLO el token con el que se hizo la petición
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada. El token fue revocado.']);
    }
}
