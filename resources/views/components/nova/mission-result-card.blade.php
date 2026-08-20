@props([
    'result',
])

<article {{ $attributes->class('flex items-start gap-4 rounded-xl border border-transparent px-3 py-4 transition hover:border-neutral-800 hover:bg-neutral-950') }}>
    <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-emerald-500/10 text-xs text-emerald-400">✓</span>
    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-4">
            <p class="truncate text-sm font-medium text-neutral-300">{{ $result['goal'] }}</p>
            <span class="shrink-0 text-[10px] font-semibold uppercase tracking-wider text-emerald-500/70">Completada</span>
        </div>
        <p class="mt-1 truncate text-xs text-neutral-600">{{ $result['summary'] }}</p>
        @if ($result['files'])
            <p class="mt-2 text-[11px] text-neutral-700">
                {{ count($result['files']) }} {{ count($result['files']) === 1 ? 'archivo disponible' : 'archivos disponibles' }}
            </p>
        @endif
    </div>
</article>
