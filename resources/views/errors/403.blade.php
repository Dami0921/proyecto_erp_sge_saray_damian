@php($logueado = auth()->check())
<x-dynamic-component :component="$logueado ? 'app-layout' : 'guest-layout'">
    @if ($logueado)
        <x-slot name="header">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Acceso denegado</h2>
        </x-slot>
    @endif

    <div class="{{ $logueado ? 'py-12' : '' }}">
        <div class="{{ $logueado ? 'max-w-4xl mx-auto sm:px-6 lg:px-8' : '' }}">
            <div class="bg-white shadow-sm sm:rounded-lg p-8 text-center">
                <p class="text-5xl font-bold text-green-700">403</p>
                <h3 class="mt-3 text-lg font-semibold text-gray-800">No tienes permiso para esta acción</h3>
                <p class="mt-2 text-sm text-gray-600">
                    Tu rol{{ $logueado && auth()->user()->rolActivo() ? ' activo «'.auth()->user()->rolActivo().'»' : '' }} no incluye este permiso.
                    Si necesitas acceso, pídele a un administrador que ajuste tus roles o permisos en Configuración.
                </p>
                <a href="{{ url('/dashboard') }}" class="mt-6 inline-flex items-center px-4 py-2 bg-green-700 rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-800">Volver al panel</a>
            </div>
        </div>
    </div>
</x-dynamic-component>
