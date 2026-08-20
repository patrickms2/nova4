@props([
    'as' => 'div',
    'padding' => 'md',
    'interactive' => false,
])

@php
    $paddingClasses = match ($padding) {
        'none' => '',
        'sm' => 'p-4',
        'lg' => 'p-8',
        default => 'p-6',
    };
@endphp

<{{ $as }}
    {{ $attributes->class([
        'rounded-2xl border border-neutral-800 bg-neutral-900 text-white shadow-sm',
        $paddingClasses,
        'transition-colors hover:border-neutral-700' => $interactive,
    ]) }}
>
    {{ $slot }}
</{{ $as }}>
