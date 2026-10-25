<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            ¡Bienvenido, {{ $user->name }}!
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <p>AgroInsumos El Cultivador S.A.S. - Panel de control</p>
                    <p class="text-sm text-gray-600 mt-1">
                        @if ($roles->count() > 1)
                            Rol activo: <strong class="text-green-800">{{ $rolActivo }}</strong>
                            <span class="text-gray-400">(tienes también: {{ $roles->reject(fn ($r) => $r === $rolActivo)->join(', ') }})</span>
                            · <a href="{{ route('rol.seleccionar') }}" class="text-green-700 hover:underline">cambiar de rol</a>
                        @else
                            Rol: {{ $roles->isNotEmpty() ? $roles->join(', ') : 'Sin rol asignado' }}
                        @endif
                    </p>
                    @if ($roles->isEmpty())
                        <p class="mt-3 rounded-md bg-amber-50 border border-amber-200 text-amber-800 text-sm px-3 py-2">
                            Tu cuenta aún no tiene un rol. Un administrador debe asignártelo en Configuración para que puedas trabajar.
                        </p>
                    @endif
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

            {{-- Top 5: solo para quien puede ver ventas y con el módulo activo --}}
            @if ($topProductos !== null)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-2">Top 5 · Productos más vendidos</h3>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Unidades</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Ingresos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($topProductos as $fila)
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-500">{{ $loop->iteration }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-800">{{ $fila->producto?->nombre }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-600 text-right">{{ (int) $fila->unidades }}</td>
                                    <td class="px-3 py-2 text-sm text-gray-800 text-right">${{ number_format($fila->ingresos, 2, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-3 py-4 text-center text-sm text-gray-500">Aún no hay ventas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            {{-- Reabastecer: stock bajo, con acceso directo a una nueva orden de compra --}}
            @if ($reabastecer !== null)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-semibold text-gray-800">Productos para reabastecer</h3>
                        <a href="{{ route('compras.create') }}" class="text-sm text-green-700 hover:underline">Nueva orden de compra</a>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead>
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Producto</th>
                                <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase">Stock</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse ($reabastecer as $p)
                                <tr>
                                    <td class="px-3 py-2 text-sm text-gray-800">{{ $p->nombre }}</td>
                                    <td class="px-3 py-2 text-sm text-right {{ (int) $p->stock === 0 ? 'text-red-600 font-semibold' : 'text-yellow-700' }}">{{ (int) $p->stock }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="2" class="px-3 py-4 text-center text-sm text-gray-500">Todo el inventario tiene stock suficiente.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
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
