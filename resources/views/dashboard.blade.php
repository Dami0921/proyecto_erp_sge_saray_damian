<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ¡Bienvenido, {{ $user->name }}!
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <p>AgroInsumos El Cultivador S.A.S. - Panel de control</p>
                    <p class="text-sm text-gray-600 mt-1">
                        Rol: {{ $roles->isNotEmpty() ? $roles->join(', ') : 'Sin rol asignado' }}
                    </p>
                </div>
            </div>

            {{-- Indicadores: solo aparecen los que el rol tiene permiso de ver --}}
            @if ($indicadores->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    @foreach ($indicadores as $indicador)
                        <div class="bg-white p-4 rounded-lg shadow-sm">
                            <p class="text-sm text-gray-500">{{ $indicador['etiqueta'] }}</p>
                            <p class="text-2xl font-bold text-green-700">{{ $indicador['valor'] }}</p>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-2">Módulos disponibles</h3>
                <ul class="list-disc ps-6 space-y-1 text-gray-700">
                    @forelse ($modulos as $modulo)
                        <li>
                            <a href="{{ route($modulo['ruta']) }}" class="text-green-700 hover:underline">{{ $modulo['titulo'] }}</a>
                            - {{ $modulo['descripcion'] }}
                        </li>
                    @empty
                        <li>No tienes módulos disponibles. Pide a un administrador que te asigne un rol.</li>
                    @endforelse
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
