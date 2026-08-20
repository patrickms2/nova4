@props([
    'label',
    'value',
    'description',
])

<article class="flex min-w-0 items-center justify-between gap-4 border-t border-neutral-800 py-4">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">
            {{ $label }}
        </p>
        <p class="mt-1 truncate text-xs text-neutral-400">
            {{ $description }}
        </p>
    </div>
    <div class="shrink-0">
        <p class="max-w-40 truncate text-sm font-semibold text-white">
            {{ $value }}
        </p>
    </div>
</article>
