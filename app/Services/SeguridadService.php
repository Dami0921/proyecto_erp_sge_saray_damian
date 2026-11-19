<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reglas de seguridad del módulo de Configuración (usuarios, roles y permisos).
 * Todas las operaciones corren en una transacción: si una regla de protección falla,
 * no se guarda NADA (ni el usuario, ni los roles, ni los permisos).
 */
class SeguridadService
{
    /** Roles base del sistema: no se pueden eliminar ni renombrar. */
    public const ROLES_BASE = ['admin', 'vendedor', 'almacenista'];

    /** Rol con todos los permisos, siempre. */
    public const ROL_ADMIN = 'admin';

    // ------------------------------------------------------------------ Usuarios

    /**
     * @param  array  $data  ['name','email','password','roles' => [nombres], 'permisos' => [nombres]]
     */
    public function crearUsuario(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $roles = $this->rolesValidos($data['roles'] ?? []);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'], // el cast "hashed" la encripta
            ]);

            $user->syncRoles($roles);
            $user->syncPermissions($data['permisos'] ?? []); // permisos directos

            $user->unsetRelation('roles')->unsetRelation('permissions');
            $this->registrarSeguridad($user, 'usuario creado con sus roles y permisos', [], $this->fotoAccesos($user));

            return $user;
        });
    }

    /**
     * @param  array  $data  ['name','email','password'?,'roles' => [nombres], 'permisos' => [nombres]]
     */
    public function actualizarUsuario(User $user, array $data, User $actor): User
    {
        return DB::transaction(function () use ($user, $data, $actor) {
            $user = User::withTrashed()->lockForUpdate()->findOrFail($user->id);
            $roles = $this->rolesValidos($data['roles'] ?? []);

            $quitaAdmin = $user->esAdmin() && ! in_array(self::ROL_ADMIN, $roles, true);

            if ($quitaAdmin) {
                // Regla: un administrador no puede quitarse a sí mismo el rol admin (se bloquearía la Configuración)
                if ($user->is($actor)) {
                    throw ValidationException::withMessages([
                        'roles' => 'No puedes quitarte a ti mismo el rol de administrador.',
                    ]);
                }
                // Regla: siempre debe quedar al menos otro administrador
                $this->exigirOtroAdministrador($user, 'roles', 'No se puede quitar el rol admin: es el único administrador del sistema.');
            }

            $antes = $this->fotoAccesos($user); // para la bitácora de seguridad

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            // La contraseña solo cambia si se escribe una nueva
            if (! empty($data['password'])) {
                $user->password = $data['password'];
            }

            $user->save();
            $user->syncRoles($roles);
            $user->syncPermissions($data['permisos'] ?? []);

            $user->unsetRelation('roles')->unsetRelation('permissions');
            $despues = $this->fotoAccesos($user);
            if ($antes !== $despues) {
                $this->registrarSeguridad($user, 'roles y permisos del usuario modificados', $antes, $despues);
            }

            return $user;
        });
    }

    public function eliminarUsuario(User $user, User $actor): void
    {
        DB::transaction(function () use ($user, $actor) {
            $user = User::lockForUpdate()->findOrFail($user->id);

            // Regla: nadie puede eliminar su propia cuenta desde Configuración
            if ($user->is($actor)) {
                throw ValidationException::withMessages(['usuario' => 'No puedes eliminar tu propia cuenta.']);
            }

            // Regla: el último administrador no se puede eliminar
            if ($user->esAdmin()) {
                $this->exigirOtroAdministrador($user, 'usuario', 'No se puede eliminar al único administrador del sistema.');
            }

            $user->delete(); // soft delete: conserva el historial de ventas y compras
        });
    }

    public function restaurarUsuario(User $user): void
    {
        DB::transaction(function () use ($user) {
            User::onlyTrashed()->lockForUpdate()->findOrFail($user->id)->restore();
        });
    }

    // --------------------------------------------------------------- Roles y matriz

    /**
     * Guarda la matriz completa de permisos por rol.
     *
     * @param  array  $matriz  [rol_id => [nombres de permiso]]
     */
    public function actualizarMatriz(array $matriz): void
    {
        DB::transaction(function () use ($matriz) {
            $existentes = Permission::pluck('name')->all();

            foreach (Role::all() as $rol) {
                $antes = $rol->permissions()->pluck('name')->sort()->values()->all();

                // Regla: el rol admin SIEMPRE conserva todos los permisos (no se edita desde la matriz)
                if ($rol->name === self::ROL_ADMIN) {
                    $rol->syncPermissions(Permission::all());
                } else {
                    // Solo se aceptan permisos que existan en el catálogo
                    $permisos = array_values(array_intersect($matriz[$rol->id] ?? [], $existentes));
                    $rol->syncPermissions($permisos);
                }

                $despues = $rol->permissions()->pluck('name')->sort()->values()->all();
                if ($antes !== $despues) {
                    $this->registrarSeguridad($rol, "permisos del rol «{$rol->name}» modificados", ['permisos' => $antes], ['permisos' => $despues]);
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function crearRol(string $nombre): Role
    {
        return DB::transaction(function () use ($nombre) {
            $rol = Role::create(['name' => $nombre, 'guard_name' => 'web']);
            $this->registrarSeguridad($rol, "rol «{$nombre}» creado", [], ['nombre' => $nombre], 'created');

            return $rol;
        });
    }

    public function eliminarRol(Role $rol): void
    {
        DB::transaction(function () use ($rol) {
            $rol = Role::lockForUpdate()->findOrFail($rol->id);

            // Regla: los roles base del sistema no se eliminan
            if (in_array($rol->name, self::ROLES_BASE, true)) {
                throw ValidationException::withMessages(['rol' => "El rol «{$rol->name}» es un rol base del sistema y no se puede eliminar."]);
            }

            // Regla: un rol con usuarios asignados no se elimina
            $usuarios = $rol->users()->count();
            if ($usuarios > 0) {
                throw ValidationException::withMessages(['rol' => "El rol «{$rol->name}» tiene {$usuarios} usuario(s) asignado(s). Reasígnalos antes de eliminarlo."]);
            }

            $this->registrarSeguridad($rol, "rol «{$rol->name}» eliminado", ['nombre' => $rol->name], [], 'deleted');
            $rol->delete();
        });
    }

    // ------------------------------------------------------------------ Internos

    /** Roles y permisos directos de un usuario, ordenados, para comparar antes → después. */
    private function fotoAccesos(User $user): array
    {
        return [
            'roles' => $user->getRoleNames()->sort()->values()->all(),
            'permisos_directos' => $user->getDirectPermissions()->pluck('name')->sort()->values()->all(),
        ];
    }

    /**
     * Spatie Activitylog no audita roles ni permisos por sí solo: se registran a mano en la bitácora
     * «seguridad», con el mismo formato (old / attributes) de los modelos auditados.
     */
    private function registrarSeguridad($sujeto, string $descripcion, array $antes, array $despues, string $evento = 'updated'): void
    {
        activity('seguridad')
            ->performedOn($sujeto)
            ->causedBy(auth()->user())
            ->event($evento)
            ->withChanges(['old' => $antes, 'attributes' => $despues])
            ->log($descripcion);
    }

    /** Regla: todo usuario debe tener al menos un rol, y solo roles que existan. */
    private function rolesValidos(array $roles): array
    {
        $roles = array_values(array_unique($roles));

        if ($roles === []) {
            throw ValidationException::withMessages(['roles' => 'El usuario debe tener al menos un rol.']);
        }

        $existentes = Role::whereIn('name', $roles)->pluck('name')->all();
        if (count($existentes) !== count($roles)) {
            throw ValidationException::withMessages(['roles' => 'Alguno de los roles elegidos no existe.']);
        }

        return $roles;
    }

    /** ¿Es $user el único administrador activo del sistema? */
    public function esUnicoAdministrador(User $user): bool
    {
        return $user->esAdmin() && ! User::role(self::ROL_ADMIN)->whereKeyNot($user->id)->exists();
    }

    /** Lanza el error si, descontando a $user, no queda otro administrador activo. */
    private function exigirOtroAdministrador(User $user, string $campo, string $mensaje): void
    {
        // lockForUpdate: dos administradores no pueden quitarse el rol a la vez dejando el sistema sin ninguno
        $otros = User::role(self::ROL_ADMIN)->whereKeyNot($user->id)->lockForUpdate()->count();

        if ($otros === 0) {
            throw ValidationException::withMessages([$campo => $mensaje]);
        }
    }
}
