{{-- Tema de color de la organización. El verde original no necesita ajustes; los demás redefinen las clases «green» que usa el sistema. --}}
@php($t = \App\Models\Organizacion::actual()->paleta())
@if ($t)
<style id="tema-organizacion">
    .bg-green-50, .hover\:bg-green-50:hover { background-color: {{ $t['50'] }}; }
    .bg-green-100 { background-color: {{ $t['100'] }}; }
    .bg-green-700 { background-color: {{ $t['700'] }}; }
    .hover\:bg-green-800:hover { background-color: {{ $t['800'] }}; }
    .text-green-400 { color: {{ $t['400'] }}; }
    .text-green-600 { color: {{ $t['600'] }}; }
    .text-green-700 { color: {{ $t['700'] }}; }
    .text-green-800, .hover\:text-green-800:hover { color: {{ $t['800'] }}; }
    .text-green-900 { color: {{ $t['900'] }}; }
    .border-green-100 { border-color: {{ $t['100'] }}; }
    .border-green-200 { border-color: {{ $t['200'] }}; }
    .border-green-700 { border-color: {{ $t['700'] }}; }
    .focus\:border-green-500:focus { border-color: {{ $t['500'] }}; }
    .focus\:ring-green-500:focus { --tw-ring-color: {{ $t['500'] }}; }
    .focus\:ring-green-600:focus, .hover\:ring-green-600:hover { --tw-ring-color: {{ $t['600'] }}; }
</style>
@endif
