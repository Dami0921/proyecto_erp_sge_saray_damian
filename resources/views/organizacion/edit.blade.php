<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Organización · identidad corporativa</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('organizacion.update') }}" enctype="multipart/form-data"
                  class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
                @csrf
                @method('PUT')

                <p class="text-sm text-gray-600">
                    Estos datos personalizan el ERP para su empresa: aparecen en el menú, el inicio de sesión, la portada,
                    los documentos PDF y los correos. Para quitar funciones que su empresa no usa, vea
                    <a href="{{ route('modulos.index') }}" class="text-green-700 hover:underline">Módulos del sistema</a>.
                </p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-input-label for="nombre" value="Nombre de la empresa *" />
                        <x-text-input id="nombre" name="nombre" type="text" class="mt-1 block w-full" :value="old('nombre', $org->nombre)" required />
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="nit" value="NIT" />
                        <x-text-input id="nit" name="nit" type="text" class="mt-1 block w-full" :value="old('nit', $org->nit)" />
                        <x-input-error :messages="$errors->get('nit')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="telefono" value="Teléfono" />
                        <x-text-input id="telefono" name="telefono" type="text" class="mt-1 block w-full" :value="old('telefono', $org->telefono)" />
                        <x-input-error :messages="$errors->get('telefono')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="direccion" value="Dirección" />
                        <x-text-input id="direccion" name="direccion" type="text" class="mt-1 block w-full" :value="old('direccion', $org->direccion)" />
                        <x-input-error :messages="$errors->get('direccion')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="email" value="Correo de contacto" />
                        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $org->email)" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="eslogan" value="Eslogan (título de la portada)" />
                        <x-text-input id="eslogan" name="eslogan" type="text" class="mt-1 block w-full" :value="old('eslogan', $org->eslogan)" />
                        <x-input-error :messages="$errors->get('eslogan')" class="mt-2" />
                    </div>
                    <div class="md:col-span-2">
                        <x-input-label for="descripcion" value="Mensaje de bienvenida (portada)" />
                        <textarea id="descripcion" name="descripcion" rows="3"
                                  class="mt-1 block w-full border-gray-300 focus:border-green-500 focus:ring-green-500 rounded-md shadow-sm">{{ old('descripcion', $org->descripcion) }}</textarea>
                        <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                    </div>
                </div>

                <div>
                    <x-input-label value="Color principal" />
                    <div class="mt-2 flex flex-wrap gap-4">
                        @foreach ($paletas as $clave => $paleta)
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700">
                                <input type="radio" name="color" value="{{ $clave }}" @checked(old('color', $org->color) === $clave)>
                                <span class="inline-block h-6 w-6 rounded-full border border-gray-300" style="background: {{ $paleta['tonos']['700'] }}"></span>
                                {{ $paleta['nombre'] }}
                            </label>
                        @endforeach
                    </div>
                    <x-input-error :messages="$errors->get('color')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="logo" value="Logo (PNG o JPG, máximo 300 KB)" />
                    @if ($org->logo)
                        <div class="mt-2 flex items-center gap-4">
                            <img src="{{ $org->logo }}" alt="Logo actual" class="h-16 w-auto object-contain border border-gray-200 rounded p-1">
                            <label class="flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="quitar_logo" value="1" class="rounded border-gray-300 text-green-600 focus:ring-green-500">
                                Quitar el logo actual
                            </label>
                        </div>
                    @endif
                    <input id="logo" name="logo" type="file" accept="image/png,image/jpeg" class="mt-2 block w-full text-sm text-gray-700">
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                </div>

                <div class="flex justify-end">
                    <x-primary-button>Guardar identidad</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
