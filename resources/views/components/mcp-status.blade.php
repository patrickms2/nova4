@props([
    'connected' => false,
    'size' => 'md',
    'showLabel' => false,
    'label' => null,
])

@php
    $sizeClasses = match($size) {
        'sm' => 'h-2 w-2',
        'md' => 'h-3 w-3',
        'lg' => 'h-4 w-4',
        default => 'h-3 w-3',
    };

    $pingClasses = match($size) {
        'sm' => 'h-2 w-2',
        'md' => 'h-3 w-3',
        'lg' => 'h-4 w-4',
        default => 'h-3 w-3',
    };

    $statusColor = $connected
        ? 'bg-green-500'
        : 'bg-red-500';

    $statusLabel = $label ?? ($connected ? 'Connected' : 'Disconnected');
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2']) }}>
    <span class="relative flex {{ $sizeClasses }}">
        @if($connected)
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full {{ $statusColor }} opacity-75"></span>
        @endif
        <span class="relative inline-flex {{ $sizeClasses }} rounded-full {{ $statusColor }}"></span>
    </span>
    @if($showLabel)
        <span class="text-sm font-medium {{ $connected ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
            {{ $statusLabel }}
        </span>
    @endif
</span>
