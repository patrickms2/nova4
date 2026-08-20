{{-- resources/views/components/nova/stat.blade.php --}}

@props([
    'label',
    'value',
    'description' => null,
])

<article class="rounded-3xl border border-white/10 bg-zinc-900/70 p-5">
    <p class="text-xs font-medium uppercase tracking-[0.18em] text-zinc-500">
        {{ $label }}
    </p>

    <p class="mt-3 text-2xl font-semibold tracking-tight">
        {{ $value }}
    </p>

    @if ($description)
        <p class="mt-1 text-xs text-zinc-600">
            {{ $description }}
        </p>
    @endif
</article>
