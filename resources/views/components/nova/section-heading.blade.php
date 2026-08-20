@props([
    'eyebrow' => null,
    'title' => null,
    'description' => null,
    'meta' => null,
])

<div {{ $attributes->class('flex items-end justify-between gap-6') }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">
                {{ $eyebrow }}
            </p>
        @endif

        @if ($title)
            <h2 class="mt-2 truncate text-lg font-semibold text-white">
                {{ $title }}
            </h2>
        @endif

        @if ($description)
            <p class="mt-2 text-sm leading-6 text-neutral-400">
                {{ $description }}
            </p>
        @endif
    </div>

    @if ($meta)
        <span class="shrink-0 text-xs uppercase tracking-widest text-neutral-400">
            {{ $meta }}
        </span>
    @endif

    {{ $slot }}
</div>
