@php
    $clases = [
        'pendiente' => 'bg-yellow-100 text-yellow-800',
        'parcial' => 'bg-blue-100 text-blue-800',
        'recibida' => 'bg-green-100 text-green-800',
        'anulada' => 'bg-red-100 text-red-800',
    ];
@endphp
<span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $clases[$estado] ?? 'bg-gray-100 text-gray-800' }}">{{ ucfirst($estado) }}</span>
