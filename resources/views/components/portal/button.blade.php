@props([
    'variant' => 'primary', // primary|ghost
    'as'      => 'button',  // button|a
    'href'    => null,
    'type'    => 'button',
])

@php
    $variants = [
        'primary' => 'portal-actionable tl-s3 tl-interactive inline-flex items-center justify-center rounded-full border border-white/10 bg-primary p-2 white/80 tl-interactive',
        'ghost'   => 'portal-actionable tl-s3 tl-interactive inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 p-2 white/80 tl-interactive',
    ];
    $cls = $variants[$variant] ?? $variants['primary'];
@endphp

@if ($as === 'a')
    <a href="{{ $href }}" data-portal-action {{ $attributes->merge(['class' => $cls]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" data-portal-action {{ $attributes->merge(['class' => $cls]) }}>
        {{ $slot }}
    </button>
@endif
