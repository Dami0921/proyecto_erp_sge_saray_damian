<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Auditable, HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    // Auditoría (Clase 12)
    protected string $auditLog = 'usuarios';

    // Nunca se audita la contraseña
    protected array $auditExcept = ['password'];

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
        if ($this->roles->count() <= 1) {
            return false;
        }

        // API (Clase 13): un token con habilidad «rol:...» trabaja con ese rol
        if ($this->rolDelToken() !== null) {
            return true;
        }

        return auth()->check() && auth()->id() === $this->getKey();
    }

    /** Rol con el que se emitió el token de la petición actual (habilidad «rol:vendedor»), o null. */
    public function rolDelToken(): ?string
    {
        $token = $this->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return null;
        }

        foreach ($token->abilities ?? [] as $habilidad) {
            if (str_starts_with($habilidad, 'rol:')) {
                return substr($habilidad, 4);
            }
        }

        return null;
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

        // API: el rol viene en el token, no en la sesión
        $delToken = $this->rolDelToken();
        if ($delToken !== null) {
            return $this->roles->contains('name', $delToken) ? $delToken : null;
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
