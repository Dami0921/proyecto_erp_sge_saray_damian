<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Notificaciones</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if (session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Sin leer: {{ $sinLeer }}</h3>
                    @if ($sinLeer > 0)
                        <form method="POST" action="{{ route('notificaciones.leer-todas') }}">
                            @csrf
                            <button type="submit" class="text-sm text-green-700 hover:underline">Marcar todas como leídas</button>
                        </form>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100">
                    @forelse ($notificaciones as $n)
                        <li class="py-3 flex items-start justify-between gap-4 {{ $n->read_at ? 'text-gray-500' : 'bg-green-50 -mx-3 px-3 rounded' }}">
                            <div>
                                <div class="font-medium {{ $n->read_at ? '' : 'text-gray-900' }}">{{ $n->data['titulo'] ?? 'Notificación' }}</div>
                                <div class="text-sm">{{ $n->data['mensaje'] ?? '' }}</div>
                                <div class="text-xs text-gray-400">{{ $n->created_at->format('d/m/Y H:i') }}</div>
                            </div>
                            <a href="{{ route('notificaciones.abrir', $n->id) }}" class="text-sm text-green-700 hover:underline shrink-0">Abrir</a>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-gray-500">No tienes notificaciones.</li>
                    @endforelse
                </ul>

                {{ $notificaciones->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
