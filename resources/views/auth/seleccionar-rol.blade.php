<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Elige tu rol de trabajo
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <p class="text-sm text-gray-600 mb-4">
                    Tu usuario tiene varios roles. Trabajas con <strong>uno a la vez</strong>: el menú, los botones y las
                    acciones permitidas dependen del rol activo. Puedes cambiarlo en cualquier momento desde el menú de tu usuario.
                </p>

                <x-input-error :messages="$errors->get('rol')" class="mb-3" />

                <form method="POST" action="{{ route('rol.cambiar') }}" class="space-y-3">
                    @csrf
                    @foreach ($roles as $rol)
                        <label class="flex items-center gap-3 rounded-md border border-gray-200 px-4 py-3 cursor-pointer hover:bg-green-50">
                            <input type="radio" name="rol" value="{{ $rol }}" class="text-green-700 focus:ring-green-600"
                                @checked(old('rol', $activo) === $rol) required>
                            <span class="font-medium text-gray-800">{{ ucfirst($rol) }}</span>
                            @if ($activo === $rol)
                                <span class="ms-auto text-xs rounded-full bg-green-100 text-green-800 px-2 py-0.5">activo</span>
                            @endif
                        </label>
                    @endforeach

                    <x-primary-button class="mt-2">Continuar con este rol</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
