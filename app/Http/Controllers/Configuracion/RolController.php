<?php

namespace App\Http\Controllers\Configuracion;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRolRequest;
use App\Services\SeguridadService;
use App\Support\CatalogoPermisos;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolController extends Controller
{
    public function __construct(private SeguridadService $service)
    {
        $this->middleware('permission:gestionar-roles');
    }

    // Matriz de seguridad: por cada rol, una tabla módulo × acción con casillas
    public function index()
    {
        return view('configuracion.roles.index', [
            'roles' => Role::with('permissions')->withCount('users')->orderByRaw("name = 'admin' desc")->orderBy('name')->get(),
            'tabla' => CatalogoPermisos::agrupar(Permission::orderBy('name')->pluck('name')),
            'rolesBase' => SeguridadService::ROLES_BASE,
        ]);
    }

    public function update(Request $request)
    {
        $datos = $request->validate([
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['array'],
            'permisos.*.*' => ['string'],
        ]);

        $this->service->actualizarMatriz($datos['permisos'] ?? []);

        return redirect()->route('configuracion.roles.index')
            ->with('success', 'Matriz de permisos guardada. El rol admin conserva siempre todos los permisos.');
    }

    public function store(StoreRolRequest $request)
    {
        $rol = $this->service->crearRol($request->validated('nombre'));

        return redirect()->route('configuracion.roles.index')
            ->with('success', "Rol «{$rol->name}» creado sin permisos. Marca sus casillas en la matriz y guarda.");
    }

    public function destroy(Role $rol)
    {
        try {
            $this->service->eliminarRol($rol);
        } catch (ValidationException $e) {
            return redirect()->route('configuracion.roles.index')->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('configuracion.roles.index')->with('success', "Rol «{$rol->name}» eliminado.");
    }
}
