{{--
    Double sidebar component.
    Requires x-data="doubleSidebar()" on a parent wrapper.
    $navItems passed from layout.
--}}

@php
    $isBusinessWorkspace = $isBusinessWorkspace ?? false;
    $workspaceProfile = $workspaceProfile ?? [
        'business_name' => 'Mi negocio',
        'business_icon' => '✦',
    ];
@endphp

{{-- PRIMARY --}}
<aside class="flex h-full w-14 shrink-0 flex-col items-center rounded-l-2xl border-r border-white/5 bg-[#050505] py-4 shadow-2xl">

    <div class="flex items-center justify-center mb-6 text-white bg-orange-600 shadow-lg h-9 w-9 rounded-2xl shadow-orange-600/25">
        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
            @if ($isBusinessWorkspace)
                <text x="12" y="16" text-anchor="middle" fill="currentColor" stroke="none" font-size="14">{{ $workspaceProfile['business_icon'] }}</text>
            @else
                <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>
            @endif
        </svg>
    </div>

    <nav class="flex flex-1 flex-col items-center gap-1.5">
        @foreach ($navItems as $item)
            <button
                type="button"
                @click="selectModule('{{ $item['key'] }}')"
                title="{{ $item['title'] }}"
                :class="activeModule === '{{ $item['key'] }}'
                    ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/25'
                    : 'text-neutral-500 hover:bg-neutral-900 hover:text-white'"
                class="relative flex items-center justify-center transition-all duration-200 h-9 w-9 rounded-2xl focus:outline-none"
            >
                <span class="h-[18px] w-[18px] [&_svg]:h-full [&_svg]:w-full">{!! $item['icon'] !!}</span>

                <span
                    x-show="activeModule === '{{ $item['key'] }}'"
                    class="absolute w-1 h-5 -translate-y-1/2 bg-orange-500 rounded-full -left-2 top-1/2"
                ></span>
            </button>
        @endforeach
                <a
            href="{{ route('nova.studio') }}"
            title="Studio"
            class="flex items-center justify-center transition h-9 w-9 rounded-2xl text-neutral-500 hover:bg-neutral-900 hover:text-white"
        >
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </a>
    </nav>

    <div class="flex flex-col items-center gap-2 pb-2">
        <a
            href="{{ route('facturacion.ajustes') }}"
            title="Ajustes"
            class="flex items-center justify-center transition h-9 w-9 rounded-2xl text-neutral-500 hover:bg-neutral-900 hover:text-white"
        >
            <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/>
                <circle cx="12" cy="12" r="3"/>
            </svg>
        </a>

        <button
            type="button"
            title="Perfil"
            class="flex items-center justify-center overflow-hidden transition rounded-full h-9 w-9 bg-neutral-900 ring-1 ring-neutral-800 hover:ring-orange-500/60"
        >
            <span class="text-sm font-bold text-neutral-300">
                {{ substr(auth()->user()?->firstname ?? 'U', 0, 1) }}
            </span>
        </button>
    </div>
</aside>

{{-- SECONDARY --}}
<aside
    class="flex h-full shrink-0 flex-col overflow-hidden rounded-r-2xl border-r border-white/5 bg-[#080808] shadow-2xl transition-all duration-300 ease-out"
    :class="secondaryOpen ? 'w-72' : 'w-11'"
>
    {{-- EXPANDED --}}
    <div
        x-show="secondaryOpen"
        x-transition.opacity.duration.200ms
        class="flex flex-col h-full"
        x-cloak
    >
        {{-- Header --}}
        <div class="px-4 pt-4 pb-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="truncate text-[10px] font-bold uppercase tracking-[0.28em] text-orange-500/80">
                        {{ $isBusinessWorkspace ? 'NOVA Workspace' : 'NovaFactu' }}
                    </p>
                    @if ($isBusinessWorkspace && count($workspaceChoices ?? []) > 1)
                        <label class="sr-only" for="nova-workspace-selector">Seleccionar Workspace</label>
                        <select
                            id="nova-workspace-selector"
                            x-on:change="window.location.href = @js(route('nova.nova-workspace')) + '?workspace=' + encodeURIComponent($event.target.value)"
                            class="mt-1 w-full cursor-pointer appearance-none truncate border-0 bg-transparent p-0 pr-6 text-lg font-semibold text-white outline-none focus:ring-0"
                        >
                            @foreach ($workspaceChoices as $workspaceChoice)
                                <option
                                    value="{{ $workspaceChoice['id'] }}"
                                    @selected($workspaceChoice['id'] === $activeWorkspaceId)
                                    class="bg-neutral-900 text-white"
                                >
                                    {{ $workspaceChoice['business_icon'] }} {{ $workspaceChoice['business_name'] }}
                                </option>
                            @endforeach
                        </select>
                    @else
                        <h2 class="mt-1 truncate text-lg font-semibold text-white" x-text="moduleLabel"></h2>
                    @endif
                </div>

                <button
                    type="button"
                    @click="secondaryOpen = false"
                    class="p-2 transition rounded-xl text-neutral-500 hover:bg-neutral-900 hover:text-white"
                >
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Actions --}}
        <div class="px-4 pb-3">
            <template x-if="currentActions?.length">
                <div class="grid gap-2">
                    <template x-for="action in currentActions" :key="action.label">
                        <button
                            type="button"
                            @click="action.handler && window[action.handler] ? window[action.handler]() : (action.href ? window.location.href = action.href : null)"
                            :class="action.primary
                                ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700'
                                : 'bg-neutral-900 text-neutral-400 hover:bg-neutral-800 hover:text-white'"
                            class="flex items-center justify-center gap-2 rounded-2xl px-3 py-2.5 text-sm font-semibold transition"
                        >
                            <span x-html="action.icon"></span>
                            <span x-text="action.label"></span>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        {{-- Filters --}}
        <div
            x-show="showFilters ?? false"
            x-collapse
            class="mx-4 mb-3 rounded-2xl border border-orange-500/20 bg-black p-3 shadow-[0_0_0_1px_rgba(249,115,22,0.04)]"
        >
            <p class="mb-3 text-xs font-semibold text-orange-500">Filtros rápidos</p>

            <div class="space-y-3">
                <input
                    x-model="filters.search"
                    placeholder="Buscar factura, cliente..."
                    class="w-full px-3 py-2 text-sm text-white transition border outline-none rounded-xl border-neutral-800 bg-neutral-950 placeholder-neutral-600 focus:border-orange-500/70 focus:ring-2 focus:ring-orange-500/10"
                >

                <select
                    x-model="filters.status"
                    class="w-full px-3 py-2 text-sm text-white transition border outline-none rounded-xl border-neutral-800 bg-neutral-950 focus:border-orange-500/70 focus:ring-2 focus:ring-orange-500/10"
                >
                    <option value="">Todos los estados</option>
                    <option value="draft">Borrador</option>
                    <option value="sent">Emitida</option>
                    <option value="paid">Pagada</option>
                    <option value="overdue">Vencida</option>
                </select>

                <button
                    type="button"
                    @click="applyFilters && applyFilters()"
                    class="w-full py-2 text-sm font-semibold text-white transition bg-orange-600 rounded-xl hover:bg-orange-700"
                >
                    Aplicar filtros
                </button>
            </div>
        </div>

        <div class="h-px mx-4 mb-3 bg-neutral-800/70"></div>

        {{-- Skeleton --}}
        <div x-show="loading" class="px-4 pt-1 space-y-5">
            <template x-for="i in 3" :key="i">
                <div class="space-y-2">
                    <div class="w-24 h-3 rounded-full animate-pulse bg-neutral-800"></div>
                    <div class="w-full h-9 animate-pulse rounded-xl bg-neutral-900"></div>
                    <div class="w-10/12 h-9 animate-pulse rounded-xl bg-neutral-900"></div>
                </div>
            </template>
        </div>

        {{-- Nav --}}
        <nav
            x-show="!loading"
            x-transition
            class="flex-1 px-4 pb-4 space-y-5 overflow-y-auto hidden-scrollbar"
        >
            <template x-for="group in currentGroups" :key="group.title ?? Math.random()">
                <div>
                    <template x-if="group.title">
                        <button
                            type="button"
                            @click="toggleGroup ? toggleGroup(group.title) : null"
                            class="mb-2 flex w-full items-center justify-between px-1 text-[10px] font-bold uppercase tracking-[0.22em] text-neutral-600"
                        >
                            <span x-text="group.title"></span>
                            <svg
                                class="h-3.5 w-3.5 transition"
                                :class="openedGroups?.includes(group.title) ? 'rotate-180 text-orange-500' : ''"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2.5"
                            >
                                <path d="m6 9 6 6 6-6"/>
                            </svg>
                        </button>
                    </template>

                    <div
                        x-show="!group.title || !openedGroups || openedGroups.includes(group.title)"
                        x-collapse
                        class="space-y-1"
                    >
                        <template x-for="link in group.items" :key="link.id ?? link.label">
                            <div
                                x-show="link.visible !== false"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0 -translate-x-3"
                                x-transition:enter-end="opacity-100 translate-x-0"
                            >
                                <a
                                    :href="link.href ?? '#'"
                                    @click.prevent="toggleLink(link)"
                                    :class="link.isImproved
                                        ? 'bg-orange-500/10 text-orange-300 ring-1 ring-orange-500/30 shadow-[0_0_22px_rgba(249,115,22,0.12)]'
                                        : (activeLink === link.label
                                            ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/15'
                                            : 'text-neutral-500 hover:bg-neutral-900 hover:text-white')"
                                    class="flex cursor-pointer items-center justify-between rounded-2xl px-3 py-2.5 text-sm font-medium transition"
                                >
                                    <span class="flex items-center min-w-0 gap-3">
                                        <span class="text-sm leading-none shrink-0" x-html="link.icon ?? ''"></span>
                                        <span class="truncate" x-text="link.label"></span>
                                    </span>

                                    <span class="flex items-center gap-1 ml-auto shrink-0">
                                        <template x-if="link.isImproved">
                                            <span class="rounded-full bg-orange-500/15 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wider text-orange-300">
                                                ✨ Mejorado
                                            </span>
                                        </template>

                                        <template x-if="link.badge != null">
                                            <span
                                                :class="activeLink === link.label
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-neutral-900 text-neutral-500'"
                                                class="rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums"
                                                x-text="link.badge"
                                            ></span>
                                        </template>

                                        <template x-if="link.children && link.children.length">
                                            <svg
                                                class="h-3.5 w-3.5 text-neutral-600 transition-transform duration-200"
                                                :class="openedLinks.includes(link.label) ? 'rotate-180 text-orange-500' : ''"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2.5"
                                            >
                                                <path d="m6 9 6 6 6-6"/>
                                            </svg>
                                        </template>
                                    </span>
                                </a>

                                <div
                                    x-show="link.children && link.children.length && openedLinks.includes(link.label)"
                                    x-collapse
                                    class="pl-3 mt-1 ml-5 space-y-1 border-l border-neutral-800"
                                >
                                    <template x-for="child in link.children" :key="child.label">
                                        <a
                                            :href="child.href ?? '#'"
                                            @click.prevent="activateWorkspaceTool(child)"
                                            :class="activeLink === child.label
                                                ? 'bg-neutral-900 text-orange-400'
                                                : 'text-neutral-600 hover:bg-neutral-900 hover:text-white'"
                                            class="flex items-center justify-between px-3 py-2 text-xs transition rounded-xl"
                                        >
                                            <span x-text="child.label"></span>

                                            <template x-if="child.badge != null">
                                                <span
                                                    class="rounded-full bg-neutral-900 px-2 py-0.5 text-[10px] font-bold tabular-nums text-neutral-500"
                                                    x-text="child.badge"
                                                ></span>
                                            </template>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </nav>

        {{-- Footer context --}}
        <div class="p-4 border-t border-neutral-800/70">
            <div class="p-3 border rounded-2xl border-orange-500/10 bg-neutral-900/60">
                <p class="text-xs font-semibold text-neutral-300">{{ $isBusinessWorkspace ? 'Tu negocio' : 'Panel contextual' }}</p>
                <p class="mt-1 text-xs leading-5 text-neutral-500">
                    {{ $isBusinessWorkspace ? 'Navegación creada por NOVA para este Workspace.' : 'Acciones, filtros y navegación del módulo activo.' }}
                </p>
            </div>
        </div>
    </div>

    {{-- COLLAPSED --}}
    <div
        x-show="!secondaryOpen"
        x-transition.opacity.duration.150ms
        class="flex flex-col items-center h-full gap-1 py-3 w-11"
    >
        <button
            type="button"
            @click="secondaryOpen = true"
            title="Expandir panel"
            class="flex items-center justify-center mb-1 transition h-9 w-9 rounded-2xl text-neutral-600 hover:bg-neutral-900 hover:text-white"
        >
            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="m9 18 6-6-6-6"/>
            </svg>
        </button>

        <div class="w-5 h-px mb-1 bg-neutral-800"></div>

        <template x-for="action in (currentActions ?? [])" :key="action.label">
            <button
                type="button"
                @click="action.handler && window[action.handler] ? window[action.handler]() : (action.href ? window.location.href = action.href : null)"
                :title="action.label"
                :class="action.primary
                    ? 'bg-orange-600 text-white shadow-lg shadow-orange-600/20 hover:bg-orange-700'
                    : 'text-neutral-500 hover:bg-neutral-900 hover:text-white'"
                class="flex items-center justify-center transition h-9 w-9 rounded-2xl"
                x-html="action.icon"
            ></button>
        </template>
    </div>
</aside>
