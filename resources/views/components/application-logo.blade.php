@php($org = \App\Models\Organizacion::actual())
{{-- Logo de la empresa; si no ha subido uno, un círculo con su inicial y su color --}}
@if ($org->logo)
    <img src="{{ $org->logo }}" alt="Logo de {{ $org->nombre }}" {{ $attributes->merge(['class' => 'object-contain']) }}>
@else
    <svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg" {{ $attributes }}>
        <circle cx="50" cy="50" r="45" fill="{{ $org->tonos()['700'] }}"/>
        <text x="50" y="66" text-anchor="middle" font-size="46" font-weight="bold" font-family="sans-serif" fill="white">{{ mb_strtoupper(mb_substr($org->nombre, 0, 1)) }}</text>
    </svg>
@endif
