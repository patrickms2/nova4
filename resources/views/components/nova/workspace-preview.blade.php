@props([
    'workspace',
])

<aside
    {{ $attributes->class([
        'overflow-hidden rounded-2xl border border-neutral-800 bg-[#080808] text-white shadow-2xl',
    ]) }}
>
    <div class="border-b border-neutral-800 px-5 py-5">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-orange-500">NOVA Workspace</p>
                <h2 class="mt-2 truncate text-xl font-semibold">{{ $workspace['business_name'] }}</h2>
            </div>
            <span class="text-neutral-600">‹</span>
        </div>
    </div>

    <div class="px-4 py-5">
        <p class="px-2 text-[10px] font-bold uppercase tracking-[0.28em] text-neutral-600">Workspace</p>

        <nav class="mt-3 grid gap-1">
            @foreach ($workspace['navigation'] as $area)
                <div @class([
                    'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
                    'bg-orange-500/10 text-orange-300 ring-1 ring-orange-500/20' => ($area['improvements'] ?? []) !== [],
                    'text-neutral-500' => ($area['improvements'] ?? []) === [],
                ])>
                    <span class="w-5 text-center text-sm">{{ $area['icon'] }}</span>
                    <span class="truncate">{{ $area['name'] }}</span>

                    @if (($area['improvements'] ?? []) !== [])
                        <span class="ml-auto size-1.5 rounded-full bg-orange-500"></span>
                    @endif
                </div>
            @endforeach
        </nav>
    </div>

    <div class="mt-2 border-t border-neutral-800 p-4">
        <div class="rounded-2xl border border-orange-500/20 bg-neutral-900/60 p-3">
            <p class="text-xs font-semibold text-neutral-300">Tu negocio</p>
            <p class="mt-1 text-xs leading-5 text-neutral-500">Así se organiza ahora tu Workspace.</p>
        </div>
    </div>
</aside>
