<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Configuración</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @can('gestionar-usuarios')
                    <a href="{{ route('configuracion.usuarios.index') }}" class="block bg-white rounded-lg shadow-sm p-6 hover:ring-2 hover:ring-green-600">
                        <p class="text-lg font-semibold text-green-800">Usuarios</p>
                        <p class="text-sm text-gray-600 mt-1">Crea usuarios, asígnales uno o varios roles y, si hace falta, permisos directos.</p>
                        <p class="text-xs text-gray-500 mt-3">{{ $totalUsuarios }} usuarios activos</p>
                    </a>
                @endcan
                @can('gestionar-roles')
                    <a href="{{ route('configuracion.roles.index') }}" class="block bg-white rounded-lg shadow-sm p-6 hover:ring-2 hover:ring-green-600">
                        <p class="text-lg font-semibold text-green-800">Roles y permisos</p>
                        <p class="text-sm text-gray-600 mt-1">Matriz de seguridad: qué puede hacer cada rol en cada módulo.</p>
                        <p class="text-xs text-gray-500 mt-3">{{ $totalRoles }} roles</p>
                    </a>
                @endcan
                @can('gestionar-organizacion')
                    <a href="{{ route('organizacion.edit') }}" class="block bg-white rounded-lg shadow-sm p-6 hover:ring-2 hover:ring-green-600">
                        <p class="text-lg font-semibold text-green-800">Organización</p>
                        <p class="text-sm text-gray-600 mt-1">Identidad corporativa: nombre, NIT, logo y color de la empresa.</p>
                    </a>
                @endcan
                @can('gestionar-modulos')
                    <a href="{{ route('modulos.index') }}" class="block bg-white rounded-lg shadow-sm p-6 hover:ring-2 hover:ring-green-600">
                        <p class="text-lg font-semibold text-green-800">Módulos del sistema</p>
                        <p class="text-sm text-gray-600 mt-1">Activa o desactiva módulos del ERP sin afectar al resto.</p>
                    </a>
                @endcan
            </div>
        </div>
    </div>
</x-app-layout>
