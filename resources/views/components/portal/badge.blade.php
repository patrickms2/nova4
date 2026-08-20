@props([
    'color' => 'zinc', // red|blue|emerald|amber|violet|zinc
])

@php
    $map = [
        'red'     => 'tl-pill pill-red',
        'blue'    => 'tl-pill pill-blue',
        'emerald' => 'tl-pill pill-emerald',
        'amber'   => 'tl-pill pill-amber',
        'violet'  => 'tl-pill pill-violet',
        'zinc'    => 'tl-pill pill-zinc',
    ];
    $cls = $map[$color] ?? $map['zinc'];
@endphp

<span {{ $attributes->merge(['class' => $cls]) }}>
    {{ $slot }}
</span>
