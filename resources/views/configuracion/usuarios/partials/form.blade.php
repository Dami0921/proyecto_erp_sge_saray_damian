<div class="mb-4">
    <x-input-label for="name" value="Nombre" />
    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $usuario->name)" required autofocus />
    <x-input-error :messages="$errors->get('name')" class="mt-2" />
</div>

<div class="mb-4">
    <x-input-label for="email" value="Correo" />
    <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $usuario->email)" required />
    <x-input-error :messages="$errors->get('email')" class="mt-2" />
</div>

<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
    <div>
        <x-input-label for="password" :value="$usuario->exists ? 'Nueva contraseña (opcional)' : 'Contraseña'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" :required="! $usuario->exists" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Confirmar contraseña" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
    </div>
</div>

<fieldset class="mb-6">
    <legend class="text-sm font-medium text-gray-700">Roles</legend>
    <p class="text-xs text-gray-500 mb-2">Un usuario puede tener varios roles; al iniciar sesión elegirá con cuál trabaja (rol activo).</p>
    <div class="flex flex-wrap gap-4">
        @foreach ($roles as $rol)
            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="roles[]" value="{{ $rol->name }}" class="rounded border-gray-300 text-green-700 focus:ring-green-600"
                    @checked(in_array($rol->name, $rolesDelUsuario, true))>
                {{ $rol->name }}
            </label>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('roles')" class="mt-2" />
    <x-input-error :messages="$errors->get('roles.*')" class="mt-2" />
</fieldset>

<fieldset class="mb-6">
    <legend class="text-sm font-medium text-gray-700">Permisos directos (excepciones)</legend>
    <p class="text-xs text-gray-500 mb-3">
        Se suman a los de sus roles y valen <strong>sin importar el rol activo</strong>. Úsalos solo para excepciones puntuales
        (ej.: un vendedor autorizado para anular ventas).
    </p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
        @foreach ($permisosAgrupados as $modulo => $acciones)
            <div>
                <p class="text-xs font-semibold text-gray-600 uppercase">{{ \App\Support\CatalogoPermisos::etiquetaModulo($modulo) }}</p>
                <div class="flex flex-wrap gap-x-4">
                    @foreach ($acciones as $accion => $nombre)
                        <label class="inline-flex items-center gap-1.5 text-sm text-gray-700">
                            <input type="checkbox" name="permisos[]" value="{{ $nombre }}" class="rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                @checked(in_array($nombre, $permisosDirectos, true))>
                            {{ \App\Support\CatalogoPermisos::ACCIONES[$accion] ?? $accion }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
    <x-input-error :messages="$errors->get('permisos.*')" class="mt-2" />
</fieldset>
