<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Nuevo usuario
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <x-input-error :messages="$errors->get('usuario')" class="mb-4" />
                <form method="POST" action="{{ route('configuracion.usuarios.store') }}">
                    @csrf
                    
                    @include('configuracion.usuarios.partials.form')

                    <div class="flex items-center gap-3">
                        <x-primary-button>Guardar usuario</x-primary-button>
                        <a href="{{ route('configuracion.usuarios.index') }}" class="text-sm text-gray-600 hover:underline">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
