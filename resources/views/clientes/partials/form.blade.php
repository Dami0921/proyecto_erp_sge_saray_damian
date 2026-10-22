<div class="mb-4">
    <x-input-label for="nombre" value="Nombre o razón social" />
    <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $cliente->nombre)" required autofocus />
    <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
</div>

<div class="mb-4">
    <x-input-label for="tipo_cliente" value="Tipo de cliente" />
    <select id="tipo_cliente" name="tipo_cliente" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
        <option value="">-- Seleccione un tipo --</option>
        @foreach ($tipos as $tipo)
            <option value="{{ $tipo }}" @selected(old('tipo_cliente', $cliente->tipo_cliente) === $tipo)>{{ $tipo }}</option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('tipo_cliente')" class="mt-2" />
</div>

<div class="mb-4">
    <x-input-label for="documento" value="Documento (cédula o NIT)" />
    <x-text-input id="documento" name="documento" type="text" class="mt-1 block w-full" :value="old('documento', $cliente->documento)" />
    <x-input-error :messages="$errors->get('documento')" class="mt-2" />
</div>

<div class="mb-4">
    <x-input-label for="telefono" value="Teléfono" />
    <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $cliente->telefono)" />
    <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
</div>

<div class="mb-6">
    <x-input-label for="municipio" value="Municipio" />
    <x-text-input id="municipio" name="municipio" type="text" class="mt-1 block w-full" :value="old('municipio', $cliente->municipio)" />
    <x-input-error :messages="$errors->get('municipio')" class="mt-2" />
</div>
