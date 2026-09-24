<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar producto
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('productos.update', $producto->id) }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-4">
                        <x-input-label for="nombre" value="Nombre del producto" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" value="{{ old('nombre', $producto->nombre) }}" required autofocus />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="categoria_id" value="Categoría" />
                        <select id="categoria_id" name="categoria_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
                            @foreach ($categorias as $categoria)
                                <option value="{{ $categoria->id }}" @selected(old('categoria_id', $producto->categoria_id) == $categoria->id)>{{ $categoria->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('categoria_id')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <x-input-label for="proveedor_id" value="Proveedor" />
                        <select id="proveedor_id" name="proveedor_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-green-500 focus:ring-green-500" required>
                            @foreach ($proveedores as $proveedor)
                                <option value="{{ $proveedor->id }}" @selected(old('proveedor_id', $producto->proveedor_id) == $proveedor->id)>{{ $proveedor->nombre }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('proveedor_id')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <x-input-label for="unidad_medida" value="Unidad de medida (kg, litro, bulto, unidad...)" />
                        <x-text-input id="unidad_medida" name="unidad_medida" type="text" class="mt-1 block w-full" value="{{ old('unidad_medida', $producto->unidad_medida) }}" required />
                        <x-input-error :messages="$errors->get('unidad_medida')" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-3">
                        <x-primary-button>Actualizar producto</x-primary-button>
                        <a href="{{ route('productos.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
