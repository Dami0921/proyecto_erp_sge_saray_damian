<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Auditoría · Bitácora de cambios</h2>
    </x-slot>

    @php
        $colores = [
            'created' => 'bg-green-100 text-green-800',
            'updated' => 'bg-blue-100 text-blue-800',
            'deleted' => 'bg-red-100 text-red-800',
            'restored' => 'bg-amber-100 text-amber-800',
        ];
        // Convierte cualquier valor (texto, número, arreglo, booleano) en texto legible
        $mostrar = fn ($v) => is_array($v) ? (implode(', ', $v) ?: '(vacío)') : (is_bool($v) ? ($v ? 'sí' : 'no') : ($v ?? '—'));
    @endphp

    <div class="py-12">
        <div class="max-w-screen-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">

                <form method="GET" action="{{ route('auditoria.index') }}" class="flex flex-wrap items-end gap-2">
                    <div>
                        <x-input-label for="modulo" value="Módulo" />
                        <select id="modulo" name="modulo" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            @foreach ($modulos as $modulo)
                                <option value="{{ $modulo }}" @selected(request('modulo') === $modulo)>{{ $modulo }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="evento" value="Evento" />
                        <select id="evento" name="evento" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            @foreach ($eventos as $valor => $etiqueta)
                                <option value="{{ $valor }}" @selected(request('evento') === $valor)>{{ $etiqueta }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="usuario" value="Usuario" />
                        <select id="usuario" name="usuario" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Todos</option>
                            @foreach ($usuarios as $u)
                                <option value="{{ $u->id }}" @selected((int) request('usuario') === $u->id)>{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <x-input-label for="desde" value="Desde" />
                        <input id="desde" type="date" name="desde" value="{{ request('desde') }}" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <div>
                        <x-input-label for="hasta" value="Hasta" />
                        <input id="hasta" type="date" name="hasta" value="{{ request('hasta') }}" class="mt-1 border-gray-300 rounded-md shadow-sm text-sm">
                    </div>
                    <x-primary-button>Filtrar</x-primary-button>
                </form>

                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-xs text-gray-500 uppercase">
                            <th class="p-2">Fecha</th>
                            <th class="p-2">Usuario</th>
                            <th class="p-2">Módulo</th>
                            <th class="p-2">Evento</th>
                            <th class="p-2">Registro</th>
                            <th class="p-2">Cambios (antes → después)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($actividades as $actividad)
                            @php
                                $nuevo = $actividad->attribute_changes?->get('attributes', []) ?? [];
                                $antes = $actividad->attribute_changes?->get('old', []) ?? [];
                                $sujeto = $actividad->subject;
                            @endphp
                            <tr class="border-b align-top">
                                <td class="p-2 whitespace-nowrap">{{ $actividad->created_at->format('d/m/Y H:i:s') }}</td>
                                <td class="p-2">{{ $actividad->causer?->name ?? 'Sistema' }}</td>
                                <td class="p-2">{{ $actividad->log_name }}</td>
                                <td class="p-2">
                                    <span class="px-2 py-0.5 rounded-full text-xs {{ $colores[$actividad->event] ?? 'bg-gray-100 text-gray-700' }}">{{ $actividad->description }}</span>
                                </td>
                                <td class="p-2">
                                    {{ class_basename($actividad->subject_type) }} #{{ $actividad->subject_id }}
                                    <span class="block text-xs text-gray-500">{{ $sujeto?->nombre ?? $sujeto?->numero_factura ?? $sujeto?->numero_orden ?? $sujeto?->name }}</span>
                                </td>
                                <td class="p-2">
                                    <ul class="text-xs space-y-0.5">
                                        @foreach ($nuevo as $campo => $valor)
                                            <li>
                                                <strong>{{ $campo }}:</strong>
                                                @if (array_key_exists($campo, $antes))
                                                    <span class="text-red-700 line-through">{{ $mostrar($antes[$campo]) }}</span> →
                                                @endif
                                                <span class="text-green-700">{{ $mostrar($valor) }}</span>
                                            </li>
                                        @endforeach
                                        @if (empty($nuevo) && ! empty($antes))
                                            @foreach ($antes as $campo => $valor)
                                                <li><strong>{{ $campo }}:</strong> {{ $mostrar($valor) }}</li>
                                            @endforeach
                                        @endif
                                    </ul>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="p-4 text-center text-gray-500">No hay actividad registrada.</td></tr>
                        @endforelse
                    </tbody>
                </table>

                {{ $actividades->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
