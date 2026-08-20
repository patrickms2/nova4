@props([
    'title',
    'subtitle' => null,
    'iconBg'   => 'bg-red-500/10 ring-1 ring-red-500/20',
    'href'     => null,
])

@php
    $base = 'portal-actionable tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2';
@endphp

@if ($href)
    <a href="{{ $href }}" data-portal-action data-portal-pending-label="{{ $title }}" {{ $attributes->merge(['class' => $base]) }}>
        <div class="flex items-center gap-3 min-w-0">
            <span
                class="inline-flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl {{ $iconBg }}">
                {{ $icon ?? '' }}
            </span>
            <div class="min-w-0">
                <p class="truncate font-medium text-white/90">{{ $title }}</p>
                @if ($subtitle)
                    <p class="text-sm text-white/50">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        <div class="shrink-0">
            {{ $right ?? '' }}
        </div>
    </a>
@else
    <div data-portal-action data-portal-pending-label="{{ $title }}" {{ $attributes->merge(['class' => $base]) }}>
        <div class="flex items-center gap-3 min-w-0">
            <span
                class="inline-flex h-10 w-10 sm:h-11 sm:w-11 shrink-0 items-center justify-center rounded-xl {{ $iconBg }}">
                {{ $icon ?? '' }}
            </span>
            <div class="min-w-0">
                <p class="truncate font-medium text-white/90">{{ $title }}</p>
                @if ($subtitle)
                    <p class="text-sm text-white/50">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        <div class="shrink-0">
            {{ $right ?? '' }}
        </div>
    </div>
@endif
