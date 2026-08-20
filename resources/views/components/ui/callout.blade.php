@props(['variant' => 'default', 'icon' => null])

@php
$tones = [
    'default' => 'border-border bg-card text-card-foreground',
    'secondary' => 'border-border bg-muted text-foreground',
    'warning' => 'border-warning/20 bg-warning/10 text-warning',
    'destructive' => 'border-destructive/20 bg-destructive/10 text-destructive',
    'info' => 'border-info/20 bg-info/10 text-info',
    'success' => 'border-success/20 bg-success/10 text-success',
];

$classes = $tones[$variant] ?? $tones['default'];

$iconMap = [
    'paper-clip' => 'paperclip',
    'exclamation-triangle' => 'triangle-alert',
];

$lucideIcon = $icon ? ($iconMap[$icon] ?? $icon) : null;
@endphp

<div
    data-slot="callout"
    role="alert"
    {{ $attributes->twMerge('relative w-full rounded-lg border px-4 py-3 text-sm flex items-start gap-3 '.$classes) }}
>
    @if ($lucideIcon)
        <x-dynamic-component :component="'lucide-'.$lucideIcon" class="size-4 shrink-0 translate-y-0.5" />
    @endif

    <div class="flex-1 min-w-0">
        {{ $slot }}
    </div>

    @isset($actions)
        <div data-slot="callout-actions" class="flex items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endisset
</div>
