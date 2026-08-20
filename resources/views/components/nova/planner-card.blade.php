@props([
    'title',
    'description',
    'status',
    'type' => null,
    'installable' => false,
    'packageId' => null,
])

<article class="group flex items-start justify-between gap-6 border-t border-neutral-800 py-5">
    <div class="min-w-0">
        <div class="flex items-center gap-4">
            <span class="mt-0.5 size-2 shrink-0 rounded-2xl bg-orange-500"></span>
            <h3 class="truncate font-semibold text-white">
                {{ $title }}
            </h3>
            @if ($type)
                <span class="text-xs uppercase tracking-widest text-neutral-400">
                    {{ $type }}
                </span>
            @endif
        </div>
        <p class="mt-2 pl-6 text-sm leading-6 text-neutral-400">
            {{ $description }}
        </p>
    </div>

    @if ($installable)
        <button
            type="button"
            wire:click="installPackage('{{ $packageId }}')"
            class="shrink-0 rounded-2xl bg-orange-500 px-3 py-2 text-xs font-semibold text-black shadow-sm transition-colors hover:bg-orange-400"
        >
            Install
        </button>
    @else
        <x-nova.status-badge :status="$status" />
    @endif
</article>
