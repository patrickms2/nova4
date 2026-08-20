@props([
    'actions' => [],
    'model' => 'prompt',
    'label' => 'Sugerencias',
])

<div {{ $attributes->class('flex flex-wrap items-center gap-2') }}>
    <span class="text-xs font-semibold uppercase tracking-widest text-neutral-500">
        {{ $label }}
    </span>

    @foreach ($actions as $action)
        <button
            type="button"
            x-on:click='$dispatch("nova-typewriter", @js($action))'
            class="rounded-2xl border border-neutral-800 bg-neutral-900 px-3 py-2 text-xs text-neutral-400 shadow-sm transition-colors hover:border-neutral-700 hover:text-white"
        >
            {{ $action }}
        </button>
    @endforeach
</div>
