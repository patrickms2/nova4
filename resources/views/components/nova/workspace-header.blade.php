@props([
    'workspace',
    'business',
    'status',
])

<header class="flex items-center justify-between gap-6 border-b border-neutral-900 py-3">
    <div class="min-w-0">
        <div class="flex items-center gap-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">
                    Espacio de trabajo
                </p>
                <h1 class="mt-1 truncate text-lg font-semibold text-white">
                    {{ $business }}
                </h1>
            </div>
            <x-nova.status-badge :status="$status" />
        </div>
        <p class="mt-1 truncate text-xs text-neutral-400">
            {{ $workspace }}
        </p>
    </div>

    <div class="flex shrink-0 items-center gap-4">
        <button
            type="button"
            wire:click="runShowcase"
            wire:loading.attr="disabled"
            wire:target="runShowcase"
            class="rounded-2xl bg-orange-500 px-4 py-2 text-xs font-semibold text-black shadow-sm transition-colors hover:bg-orange-400 disabled:cursor-wait disabled:opacity-60"
        >
            Reiniciar misión
        </button>
    </div>
</header>
