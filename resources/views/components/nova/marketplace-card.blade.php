@props([
    'name',
    'description' => null,
    'category' => null,
    'includes' => [],
    'status' => 'available',
    'action' => null,
    'actionLabel' => 'Install',
])

<x-nova.card interactive class="grid gap-4">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            @if ($category)
                <p class="text-xs font-semibold uppercase tracking-widest text-neutral-500">{{ $category }}</p>
            @endif
            <h3 class="mt-1 truncate text-base font-semibold text-white">{{ $name }}</h3>
            @if ($description)
                <p class="mt-2 text-sm leading-6 text-neutral-400">{{ $description }}</p>
            @endif
        </div>
        <x-nova.status-pill :status="$status" class="shrink-0" />
    </div>

    @if (count($includes))
        <div class="flex flex-wrap gap-2">
            @foreach ($includes as $capability)
                <span class="rounded-2xl bg-neutral-800 px-2 py-1 text-xs text-neutral-400">{{ $capability }}</span>
            @endforeach
        </div>
    @endif

    @if ($action)
        <div class="border-t border-neutral-800 pt-4">
            <button
                type="button"
                wire:click="{{ $action }}"
                class="rounded-2xl bg-orange-500 px-3 py-2 text-xs font-semibold text-black shadow-sm transition-colors hover:bg-orange-400"
            >
                {{ $actionLabel }}
            </button>
        </div>
    @endif
</x-nova.card>
