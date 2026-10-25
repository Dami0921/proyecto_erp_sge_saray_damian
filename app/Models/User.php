<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    /** Clave de sesión donde se guarda el rol con el que el usuario eligió trabajar. */
    public const SESION_ROL_ACTIVO = 'rol_activo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * ¿Los permisos de este usuario dependen de un rol activo?
     * Solo aplica al usuario autenticado en la petición actual y que tenga MÁS de un rol.
     * Con un solo rol (o fuera de una sesión web: tinker, seeders, colas) se usan todos sus roles.
     */
    public function usaRolActivo(): bool
    {
        return auth()->check()
            && auth()->id() === $this->getKey()
            && $this->roles->count() > 1;
    }

    /**
     * Rol con el que está trabajando ahora. Con un solo rol es ese rol; con varios, el elegido
     * al iniciar sesión (o null si todavía no eligió). Se descarta un valor de sesión que ya no
     * corresponda a un rol del usuario (por ejemplo, si un administrador se lo quitó).
     */
    public function rolActivo(): ?string
    {
        if ($this->roles->count() === 1) {
            return $this->roles->first()->name;
        }

        $elegido = request()->hasSession() ? request()->session()->get(self::SESION_ROL_ACTIVO) : null;

        return $elegido !== null && $this->roles->contains('name', $elegido) ? $elegido : null;
    }

    /**
     * Sobrescribe la comprobación de Spatie: con varios roles, el permiso vía rol solo vale si lo
     * concede el ROL ACTIVO. Los permisos directos (hasDirectPermission) se siguen aplicando siempre,
     * sin importar el rol activo. Sin rol activo elegido, se deniega por defecto.
     */
    protected function hasPermissionViaRole(Permission $permission): bool
    {
        if ($this->usaRolActivo()) {
            $activo = $this->rolActivo();

            return $activo !== null && $permission->roles->contains('name', $activo);
        }

        return $this->hasRole($permission->roles);
    }

    /** ¿Es administrador? (tiene el rol admin; se usa en las reglas de protección). */
    public function esAdmin(): bool
    {
        return $this->roles->contains('name', 'admin');
    }
}
