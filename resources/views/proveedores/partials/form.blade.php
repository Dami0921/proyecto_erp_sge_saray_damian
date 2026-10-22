<div class="mb-4">
    <x-input-label for="nombre" value="Nombre o razón social" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $proveedor->nombre)" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div class="mb-4">
    <x-input-label for="nit" value="NIT" />
    <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full" :value="old('nit', $proveedor->nit)" required />
    <x-input-error :messages="$errors->get('nit')" class="mt-2" />
</div>

<div class="mb-4">
    <x-input-label for="telefono" value="Teléfono" />
    <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $proveedor->telefono)" />
    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
</div>

<div class="mb-6">
    <x-input-label for="direccion" value="Dirección" />
    <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full" :value="old('direccion', $proveedor->direccion)" />
    <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
</div>
