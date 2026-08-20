@props([
    'title'    => '',
    'subtitle' => null,
    'icon'     => null,
])

{{--
    Usage:
    <x-ui.page-header title="Facturas" subtitle="42 registros">
        <x-slot:actions>
            <x-ui.button size="sm">+ Nueva</x-ui.button>
        </x-slot:actions>
        <x-slot:meta>
            Optional secondary row (filters, tabs…)
        </x-slot:meta>
    </x-ui.page-header>
--}}

<header class="sticky top-0 z-20 border-b border-neutral-800/60 bg-neutral-950/80 backdrop-blur-md">

    {{-- ── Main row ──────────────────────────────────────────────────── --}}
    <div class="flex h-14 items-center gap-3 px-5">

        {{-- Sidebar trigger (collapses secondary panel) --}}
        <button
            type="button"
            @click="secondaryOpen = !secondaryOpen"
            class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-neutral-500 transition hover:bg-neutral-800 hover:text-neutral-200"
        >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect width="18" height="18" x="3" y="3" rx="2"/>
                <path d="M9 3v18"/>
            </svg>
        </button>

        <div class="h-4 w-px bg-neutral-800 shrink-0"></div>

        {{-- Title --}}
        <div class="flex min-w-0 items-center gap-2.5">
            @if ($icon)
                <span class="shrink-0 text-neutral-400">{{ $icon }}</span>
            @endif
            <h1 class="truncate text-sm font-semibold text-neutral-100 tracking-tight">{{ $title }}</h1>
            @if ($subtitle)
                <span class="hidden shrink-0 text-xs text-neutral-600 sm:block">{{ $subtitle }}</span>
            @endif
        </div>

        {{-- Actions slot --}}
        @isset($actions)
            <div class="ml-auto flex items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>

    {{-- ── Secondary row (filters, tabs, etc.) ──────────────────────── --}}
    @isset($meta)
        <div class="border-t border-neutral-800/50 px-5 py-2.5">
            {{ $meta }}
        </div>
    @endisset

</header>
