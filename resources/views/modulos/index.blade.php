<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Módulos del sistema
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 rounded-md bg-green-50 border border-green-200 text-green-800 px-4 py-3">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-700 text-sm">
                    Activa o desactiva los módulos del ERP. Un módulo desactivado desaparece del menú y del panel,
                    y sus rutas responden 404; el resto del sistema sigue funcionando.
                </div>

                <ul class="divide-y divide-gray-100">
                    @foreach ($modulos as $modulo)
                        <li class="flex items-center justify-between gap-4 px-6 py-4">
                            <div>
                                <p class="font-medium text-gray-900">{{ $modulo->nombre }}</p>
                                <p class="text-xs text-gray-500">clave: {{ $modulo->clave }}</p>
                            </div>

                            <div class="flex items-center gap-3">
                                @if ($modulo->es_nucleo)
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700">
                                        Núcleo · siempre activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
                                        {{ $modulo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $modulo->activo ? 'Activo' : 'Desactivado' }}
                                    </span>

                                    <form method="POST" action="{{ route('modulos.toggle', $modulo) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit"
                                            class="rounded-md px-4 py-2 text-sm font-semibold text-white
                                            {{ $modulo->activo ? 'bg-red-600 hover:bg-red-700' : 'bg-green-700 hover:bg-green-800' }}">
                                            {{ $modulo->activo ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
