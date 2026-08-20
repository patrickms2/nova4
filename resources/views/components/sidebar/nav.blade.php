{{--
    Single sidebar component.
    Requires x-data="doubleSidebar()" (or similar) on a parent wrapper.
    $navItems passed from layout.
--}}

@php
    $isBusinessWorkspace = $isBusinessWorkspace ?? false;
    $workspaceProfile = $workspaceProfile ?? [
        'business_name' => 'Nova Community',
        'business_icon' => '✦',
    ];
@endphp

<aside class="flex h-full w-56 shrink-0 flex-col rounded-l-2xl border-r border-white/5 bg-[#050505] py-4 shadow-2xl">

    {{-- Brand --}}
    <div class="flex items-center gap-3 px-4 mb-6">
        <div class="flex items-center justify-center text-white bg-orange-600 shadow-lg h-9 w-9 rounded-2xl shadow-orange-600/25">
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                @if ($isBusinessWorkspace)
                    <text x="12" y="16" text-anchor="middle" fill="currentColor" stroke="none" font-size="14">{{ $workspaceProfile['business_icon'] }}</text>
                @else
                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
                @endif
            </svg>
        </div>
        <span class="text-sm font-semibold text-white">{{ $workspaceProfile['business_name'] }}</span>
    </div>

    {{-- Navigation links --}}
    <nav class="flex flex-1 flex-col gap-1 px-3 overflow-y-auto">
        @foreach ($navItems as $item)
            <a
                href="{{ route($item['route']) }}"
                title="{{ $item['title'] }}"
                :class="activeModule === '{{ $item['key'] }}'
                    ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/25'
                    : 'text-neutral-400 hover:bg-neutral-900 hover:text-white'"
                class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition-all duration-200 rounded-xl"
            >
                <span class="h-[18px] w-[18px] [&_svg]:h-full [&_svg]:w-full">{!! $item['icon'] !!}</span>
                <span>{{ $item['title'] }}</span>
            </a>
        @endforeach
    </nav>

    {{-- Footer actions --}}
    <div class="flex flex-col gap-1 px-3 pb-2">
        <a
            href="{{ route('facturacion.ajustes') }}"
            title="Ajustes"
            class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition rounded-xl text-neutral-400 hover:bg-neutral-900 hover:text-white"
        >
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
            <span>Ajustes</span>
        </a>

        <button
            type="button"
            title="Perfil"
            class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium transition rounded-xl text-neutral-400 hover:bg-neutral-900 hover:text-white"
        >
            <span class="flex items-center justify-center h-[22px] w-[22px] overflow-hidden rounded-full bg-neutral-800 text-xs font-bold text-neutral-300">
                {{ substr(auth()->user()?->firstname ?? 'U', 0, 1) }}
            </span>
            <span>Perfil</span>
        </button>
    </div>
</aside>
