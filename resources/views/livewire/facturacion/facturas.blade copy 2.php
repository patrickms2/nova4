@php
    $totalFacturas = $facturasList->count();
    $totalImporte  = $facturasList->sum('importe');
    $totalBase     = $facturasList->sum('baseimponible');
    $totalIgic     = $facturasList->sum('impuesto');
@endphp
<div
    x-data="facturasPage()"
    class="flex min-h-screen bg-[#f7f7f5] text-neutral-950"
>
    {{-- Primary sidebar --}}
    <aside class="m-2 mr-0 flex w-12 flex-col items-center rounded-2xl bg-black py-3 text-white">
        <button class="mb-5 flex h-8 w-8 items-center justify-center rounded-xl bg-neutral-900">
            <x-lucide-zap class="h-4 w-4" />
        </button>

        <nav class="flex flex-1 flex-col gap-2">
            <button class="grid h-9 w-9 place-items-center rounded-xl bg-neutral-800">
                <x-lucide-file-text class="h-4 w-4" />
            </button>
            <button class="grid h-9 w-9 place-items-center rounded-xl text-neutral-500 hover:bg-neutral-900 hover:text-white">
                <x-lucide-users class="h-4 w-4" />
            </button>
            <button class="grid h-9 w-9 place-items-center rounded-xl text-neutral-500 hover:bg-neutral-900 hover:text-white">
                <x-lucide-bar-chart-3 class="h-4 w-4" />
            </button>
        </nav>

        <button class="grid h-9 w-9 place-items-center rounded-xl text-neutral-500 hover:bg-neutral-900 hover:text-white">
            <x-lucide-settings class="h-4 w-4" />
        </button>
    </aside>

    {{-- Secondary sidebar --}}
    <aside
        class="my-2 w-80 rounded-2xl bg-[#080808] p-4 text-white shadow-xl transition-all duration-300"
        :class="sidebarCollapsed ? 'w-0 overflow-hidden p-0 opacity-0' : 'w-80'"
    >
        <div class="flex items-center justify-between">
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-neutral-500">Facturas</p>
                <h2 class="mt-1 text-lg font-semibold">Gestión</h2>
            </div>

            <button @click="sidebarCollapsed = true" class="rounded-lg p-2 text-neutral-500 hover:bg-neutral-900 hover:text-white">
                <x-lucide-chevron-left class="h-4 w-4" />
            </button>
        </div>

        <div class="mt-5 flex gap-2">
            <a
                href="{{ route('facturacion.nuevafactura') }}"
                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-white px-3 py-2 text-sm font-semibold text-black hover:bg-neutral-200"
            >
                <x-lucide-plus class="h-4 w-4" />
                Nueva factura
            </a>

            <button
                @click="filtersOpen = !filtersOpen"
                class="grid h-10 w-10 place-items-center rounded-xl bg-neutral-900 text-neutral-300 hover:text-white"
            >
                <x-lucide-filter class="h-4 w-4" />
            </button>
        </div>

        <div x-show="filtersOpen" x-collapse class="mt-4 rounded-2xl border border-neutral-800 bg-neutral-950 p-3">
            <p class="mb-3 text-xs font-semibold text-neutral-400">Filtros rápidos</p>

            <div class="space-y-3">
                <input
                    x-model="filters.search"
                    placeholder="Buscar factura, cliente..."
                    class="w-full rounded-xl border border-neutral-800 bg-black px-3 py-2 text-sm outline-none focus:border-neutral-600"
                >

                <select x-model="filters.status" class="w-full rounded-xl border border-neutral-800 bg-black px-3 py-2 text-sm">
                    <option value="">Todos los estados</option>
                    <option value="paid">Pagadas</option>
                    <option value="pending">Pendientes</option>
                    <option value="overdue">Vencidas</option>
                </select>

                <button
                    @click="applyFilters"
                    class="w-full rounded-xl bg-white py-2 text-sm font-semibold text-black hover:bg-neutral-200"
                >
                    Aplicar filtros
                </button>
            </div>
        </div>

        <nav class="mt-6 space-y-6">
            <div>
                <button
                    @click="toggleGroup('facturas')"
                    class="flex w-full items-center justify-between text-xs font-semibold uppercase tracking-wider text-neutral-500"
                >
                    Facturas
                    <x-lucide-chevron-down class="h-4 w-4 transition" ::class="opened.includes('facturas') ? 'rotate-180' : ''" />
                </button>

                <div x-show="opened.includes('facturas')" x-collapse class="mt-3 space-y-1">
                    <a class="flex items-center gap-3 rounded-xl bg-neutral-900 px-3 py-2 text-sm font-medium">
                        <x-lucide-file class="h-4 w-4" />
                        Todas las facturas
                    </a>
                    <a class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-neutral-400 hover:bg-neutral-900 hover:text-white">
                        <x-lucide-clock class="h-4 w-4" />
                        Pendientes
                    </a>
                    <a class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-neutral-400 hover:bg-neutral-900 hover:text-white">
                        <x-lucide-alert-triangle class="h-4 w-4" />
                        Vencidas
                    </a>
                </div>
            </div>

            <div>
                <button
                    @click="toggleGroup('informes')"
                    class="flex w-full items-center justify-between text-xs font-semibold uppercase tracking-wider text-neutral-500"
                >
                    Informes
                    <x-lucide-chevron-down class="h-4 w-4 transition" ::class="opened.includes('informes') ? 'rotate-180' : ''" />
                </button>

                <div x-show="opened.includes('informes')" x-collapse class="mt-3 space-y-1">
                    <a class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-neutral-400 hover:bg-neutral-900 hover:text-white">
                        <x-lucide-bar-chart-3 class="h-4 w-4" />
                        Ventas por cliente
                    </a>
                    <a class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-neutral-400 hover:bg-neutral-900 hover:text-white">
                        <x-lucide-percent class="h-4 w-4" />
                        IVA repercutido
                    </a>
                </div>
            </div>
        </nav>
    </aside>

    {{-- Main --}}
    <main class="flex min-w-0 flex-1 flex-col">
        {{-- Topbar --}}
        <header class="sticky top-0 z-20 flex h-14 items-center justify-between border-b border-neutral-200/80 bg-[#f7f7f5]/90 px-5 backdrop-blur">
            <div class="flex items-center gap-3">
                <button
                    x-show="sidebarCollapsed"
                    @click="sidebarCollapsed = false"
                    class="rounded-lg border border-neutral-200 bg-white p-2 hover:bg-neutral-100"
                >
                    <x-lucide-panel-left-open class="h-4 w-4" />
                </button>

                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="font-semibold">Facturas</h1>
                        <span class="rounded-full bg-neutral-200 px-2 py-0.5 text-xs text-neutral-600">42 registros</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-medium hover:bg-neutral-100">
                    Filtrar
                </button>

                <button class="rounded-xl border border-neutral-200 bg-white px-3 py-2 text-sm font-medium hover:bg-neutral-100">
                    Generar remesa
                </button>

                <a
                    href="{{ route('facturacion.nuevafactura') }}"
                    class="rounded-xl bg-orange-600 px-3 py-2 text-sm font-semibold text-white hover:bg-orange-700"
                >
                    + Nueva factura
                </a>
            </div>
        </header>

        {{-- Content --}}
        <section class="p-6">
            <div class="grid grid-cols-4 gap-4">
                <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold">42</p>
                    <p class="text-sm text-neutral-500">Facturas emitidas</p>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold">16.065 €</p>
                    <p class="text-sm text-neutral-500">Base imponible</p>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold">985 €</p>
                    <p class="text-sm text-neutral-500">IGIC total</p>
                </div>

                <div class="rounded-2xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <p class="text-2xl font-bold">14.677 €</p>
                    <p class="text-sm text-neutral-500">Importe total</p>
                </div>
            </div>

            <div class="mt-5 rounded-2xl border border-neutral-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-neutral-100 p-4">
                    <input
                        placeholder="Buscar por nº factura, cliente, NIF..."
                        class="w-96 rounded-xl border border-neutral-200 bg-neutral-50 px-3 py-2 text-sm outline-none focus:border-neutral-400"
                    >

                    <div class="flex gap-2">
                        <button class="rounded-xl border border-neutral-200 px-3 py-2 text-sm hover:bg-neutral-50">
                            Periodo
                        </button>
                        <button class="rounded-xl border border-neutral-200 px-3 py-2 text-sm hover:bg-neutral-50">
                            Cliente
                        </button>
                    </div>
                </div>

                {{-- Aquí va tu tabla actual --}}
                {{ $slot ?? '' }}
            </div>
        </section>
    </main>
</div>

<script>
function facturasPage() {
    return {
        sidebarCollapsed: false,
        filtersOpen: true,
        opened: ['facturas', 'informes'],

        filters: {
            search: '',
            status: '',
        },

        toggleGroup(group) {
            this.opened = this.opened.includes(group)
                ? this.opened.filter(item => item !== group)
                : [...this.opened, group];
        },

        applyFilters() {
            const params = new URLSearchParams();

            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            window.location.href = `${window.location.pathname}?${params.toString()}`;
        }
    }
}
</script>
<div
    x-data="{
        showStats: true,
        showFilters: false,
    }"
    @keydown.ctrl.n.window.prevent="$wire.newFactura()"
    @keydown.enter.window.prevent="$wire.save()"
    @keydown.ctrl.s.window.prevent="$wire.save()"
    @keydown.insert.window.prevent="$wire.newFactura()"
    @download-pdf.window="window.open($event.detail.url, '_blank')"
    class="flex h-full flex-col"
>
    {{-- ── TOP BAR ─────────────────────────────────────────────────── --}}
    <div class="shrink-0 border-b border-neutral-200 bg-white">

        {{-- Row 1: toggle | title | actions --}}
        <div class="flex h-12 items-center gap-2 px-4">

            {{-- Sidebar secondary toggle --}}
            <button
                type="button"
                @click="secondaryOpen = !secondaryOpen"
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700"
                title="Toggle panel"
            >
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>
                </svg>
            </button>

            <div class="h-4 w-px bg-neutral-200 shrink-0"></div>

            <span class="text-sm font-semibold text-neutral-800">Facturas</span>
            <span class="text-xs text-neutral-400">{{ $totalFacturas }} registros</span>

            {{-- Actions --}}
            <div class="ml-auto flex items-center gap-1.5">
                {{-- Filtrar --}}
                <x-ui.button variant="outline" size="sm" @click="showFilters = !showFilters"
                    ::class="showFilters ? 'bg-accent' : ''">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                    @if($search || $clienteFilter || $remesaFilter || $fechaDesde || $fechaHasta)
                        <x-ui.badge class="ml-1 size-4 p-0 flex items-center justify-center text-[9px]">!</x-ui.badge>
                    @endif
                </x-ui.button>

                <x-ui.button size="sm" variant="outline" @click="$wire.openRemesaModal()">
                    <x-lucide-calendar-plus class="size-3.5" />
                    Generar Remesa
                </x-ui.button>

                <x-ui.button size="sm" variant="outline" href="{{ route('facturacion.remesas') }}">
                    <x-lucide-calendar class="size-3.5" />
                    Remesas
                </x-ui.button>

                @if(count(array_filter($selectedFacturas)) > 0)
                    <div class="h-4 w-px bg-neutral-200 mx-0.5"></div>
                    <span class="text-xs text-neutral-400">{{ count(array_filter($selectedFacturas)) }} sel.</span>
                    <x-ui.button size="sm" variant="outline" wire:click="enviarVeriFactuSeleccionadas" wire:confirm="¿Enviar a VeriFactu?">
                        <x-lucide-send class="size-3.5" />
                    </x-ui.button>
                    <x-ui.button size="sm" variant="outline" wire:click="recalcularSeleccionadas" wire:confirm="¿Recalcular?">
                        <x-lucide-calculator class="size-3.5" />
                    </x-ui.button>
                    <x-ui.button size="sm" variant="outline" wire:click="generarPdfSeleccionadas" wire:confirm="¿Generar PDFs?">
                        <x-lucide-file-text class="size-3.5" />
                    </x-ui.button>
                    <x-ui.button size="sm" variant="outline" class="text-destructive border-destructive hover:bg-destructive/10" wire:click="confirmDeleteSelected">
                        <x-lucide-trash-2 class="size-3.5" />
                    </x-ui.button>
                @endif

                <x-ui.button size="sm" variant="outline" @click="$wire.openImportModal()">
                    <x-lucide-upload class="size-3.5" />
                    Importar PDF
                </x-ui.button>

                <x-ui.button size="sm" @click="$wire.newFactura()">
                    <x-lucide-plus class="size-3.5" />
                    Nueva Factura
                </x-ui.button>

                {{-- Stats toggle --}}
                <div class="h-4 w-px bg-neutral-200 mx-0.5"></div>
                <button
                    type="button"
                    @click="showStats = !showStats"
                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-600"
                    :title="showStats ? 'Ocultar estadísticas' : 'Mostrar estadísticas'"
                >
                    <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="showStats ? 'rotate-180' : ''"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="m18 15-6-6-6 6"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Row 2: Stats (collapsible) --}}
        <div
            x-show="showStats"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="grid grid-cols-2 gap-3 border-t border-neutral-100 px-4 py-3 sm:grid-cols-4"
        >
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl">
                        <x-lucide-file-text class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ $totalFacturas }}</div>
                        <div class="text-muted-foreground text-sm">Facturas emitidas</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
<x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl  bg-blue-50 text-blue-500">
                        <x-lucide-receipt class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ number_format($totalBase, 0, ',', '.') }} €</div>
                        <div class="text-muted-foreground text-sm">Base imponible</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
<x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl  bg-purple-50 text-purple-500">
                        <x-lucide-percent class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ number_format($totalIgic, 0, ',', '.') }} €</div>
                        <div class="text-muted-foreground text-sm">IGIC total</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
<x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 rounded-xl border border-neutral-100 bg-neutral-50 px-4 py-3">
                    <div class="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-xl  bg-orange-50 text-orange-600">
                        <x-lucide-circle-dollar-sign class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold tabular-nums leading-tight">{{ number_format($totalImporte, 0, ',', '.') }} €</div>
                        <div class="text-muted-foreground text-sm">Importe total</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
          
        </div>

        {{-- Row 3: Filters panel --}}
        <div
            x-show="showFilters"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            x-cloak
            class="border-t border-neutral-100 bg-neutral-50/50 px-4 py-3"
        >
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Nº factura, cliente, NIF…">
                        <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                    </x-ui.input>
                </div>
                <div
                    x-data="{
                        from: @js($fechaDesde),
                        to:   @js($fechaHasta),
                        init() {
                            this.$watch('from', v => { if (v) $wire.set('fechaDesde', v); });
                            this.$watch('to',   v => { if (v) $wire.set('fechaHasta', v); });
                        }
                    }"
                    @calendar-change.window="
                        const d = $event.detail;
                        if (d && 'from' in d) {
                            from = d.from ?? null;
                            to   = d.to   ?? null;
                            $wire.set('fechaDesde', from);
                            $wire.set('fechaHasta', to);
                        }
                    "
                >
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Período</label>
                    <x-ui.date-picker
                        mode="range"
                        number-of-months="2"
                        :value="['from' => $fechaDesde, 'to' => $fechaHasta]"
                        :max="'2026-12-31'"
                        width="w-72"
                    />
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Cliente</label>
                    <x-ui.select native size="sm" wire:model.live="clienteFilter" class="w-full">
                        <option value="">Todos los clientes</option>
                        @foreach($clientesFilter as $c)
                            <option value="{{ $c->id }}">{{ $c->nombretotal }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Remesa</label>
                    <x-ui.select native size="sm" wire:model.live="remesaFilter" class="w-full">
                        <option value="">Todas las remesas</option>
                        @foreach($remesasFilter as $r)
                            <option value="{{ $r->id }}">{{ $r->nombre }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                @if($search || $clienteFilter || $remesaFilter || $fechaDesde || $fechaHasta)
                    <x-ui.button variant="ghost" size="sm" wire:click="clearFilters" class="gap-1 text-xs text-muted-foreground">
                        <x-lucide-x class="size-3" />
                        Limpiar
                    </x-ui.button>
                @endif
            </div>
        </div>
    </div>

    {{-- ── SCROLLABLE CONTENT ──────────────────────────────────────────── --}}
    <div class="flex-1 overflow-auto">
        <div class="flex flex-1 flex-col gap-4 p-4 md:gap-5 md:p-6 max-w-7xl w-full mx-auto">

        {{-- LISTADO FACTURAS --}}
        <div class="flex flex-col gap-4">

        {{-- Tabla de facturas --}}
    <x-ui.card>
        <x-ui.card-content class="p-0">
            <x-ui.table x-data="{
                selected: [],
                get allPageSelected() { return this.selected.length > 0 && this.selected.length === this.$el.querySelectorAll('[data-row-id]').length; },
                toggleAll(val) { const ids = [...this.$el.querySelectorAll('[data-row-id]')].map(el => el.dataset.rowId); this.selected = val ? ids : []; }
            }">
                <x-ui.table-header>
                    <x-ui.table-row class="hover:bg-transparent">
                        <x-ui.table-head class="w-8 pl-4">
                            <div class="flex items-center justify-center">
                                <input type="checkbox" class="rounded border-input size-4 cursor-pointer"
                                    wire:model.live="selectAll"
                                    @click="$wire.toggleSelectAll()">
                            </div>
                        </x-ui.table-head>
                        <x-ui.table-head>Nº Factura</x-ui.table-head>
                        <x-ui.table-head>Fecha</x-ui.table-head>
                        <x-ui.table-head>Cliente</x-ui.table-head>
                        <x-ui.table-head>Remesa</x-ui.table-head>
                        <x-ui.table-head class="text-right">Base</x-ui.table-head>
                        <x-ui.table-head class="text-right">IGIC</x-ui.table-head>
                        <x-ui.table-head class="text-right">Total</x-ui.table-head>
                        <x-ui.table-head class="w-35"></x-ui.table-head>
                    </x-ui.table-row>
                </x-ui.table-header>
                <x-ui.table-body>
                    @forelse($facturasList as $factura)
                        <x-ui.table-row x-data="blatMenu()"
                            x-init="_trigger = $el"
                            @contextmenu.prevent="openAt($event)"
                            wire:key="factura-{{ $factura->id }}"
                            class="group border-b transition-colors hover:bg-muted/50"
                            @dblclick="$wire.editFactura({{ $factura->id }})">
                            <x-ui.table-cell class="pl-4">
                                <input type="checkbox" class="rounded border-input size-4 cursor-pointer"
                                    wire:click.prevent="selectFactura({{ $factura->id }})"
                                    @checked(!empty($selectedFacturas[$factura->id])) />
                            </x-ui.table-cell>
                            <x-ui.table-cell>
                                <span class="font-mono font-semibold text-sm {{ $factura->rectificativa ? 'text-destructive' : '' }}">{{ $factura->codfactura ?? '#'.$factura->id }}</span>
                                @if($factura->rectificativa)
                                    <span class="ml-1 inline-flex items-center rounded-full bg-destructive/10 px-1.5 py-0.5 text-[10px] font-medium text-destructive">Rect.</span>
                                @endif
                                @if($factura->verifactu_status === 'accepted')
                                    <button type="button" wire:click="verVeriFactu({{ $factura->id }})" class="ml-1 inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 cursor-pointer hover:bg-emerald-200">VF</button>
                                @elseif($factura->verifactu_status === 'sent')
                                    <button type="button" wire:click="verVeriFactu({{ $factura->id }})" class="ml-1 inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 cursor-pointer hover:bg-amber-200">VF</button>
                                @elseif($factura->verifactu_status === 'rejected')
                                    <button type="button" wire:click="verVeriFactu({{ $factura->id }})" class="ml-1 inline-flex items-center rounded-full bg-destructive/10 px-1.5 py-0.5 text-[10px] font-medium text-destructive cursor-pointer hover:bg-destructive/20">VF ✕</button>
                                @endif
                            </x-ui.table-cell>
                            <x-ui.table-cell class="text-muted-foreground text-sm whitespace-nowrap">
                                {{ optional($factura->fechaemitido)->format('d/m/Y') ?? '—' }}
                            </x-ui.table-cell>
                            <x-ui.table-cell>
                                <div class="font-medium text-sm {{ $factura->rectificativa ? 'text-destructive' : '' }}">{{ optional($factura->cliente)->nombretotal ?? '—' }}</div>
                                @if(optional($factura->cliente)->dni)
                                    <div class="text-[11px] text-muted-foreground font-mono">{{ $factura->cliente->dni }}</div>
                                @endif
                            </x-ui.table-cell>
                            <x-ui.table-cell class="text-sm text-muted-foreground">
                                {{ optional($factura->remesa)->nombre ?? '—' }}
                            </x-ui.table-cell>
                            <x-ui.table-cell class="text-right tabular-nums text-sm">
                                {{ number_format($factura->baseimponible, 2, ',', '.') }} €
                            </x-ui.table-cell>
                            <x-ui.table-cell class="text-right tabular-nums text-sm text-muted-foreground">
                                {{ number_format($factura->impuesto, 2, ',', '.') }} €
                            </x-ui.table-cell>
                            <x-ui.table-cell class="text-right tabular-nums font-bold text-sm">
                                {{ number_format($factura->importe, 2, ',', '.') }} €
                            </x-ui.table-cell>
                            <x-ui.table-cell class="w-45 pr-2">
                                <div wire:ignore class="ml-auto flex items-center gap-2">
                                    <x-ui.button size="sm" variant="outline" @click="$wire.editFactura({{ $factura->id }})"
                                        class="flex size-7 items-center justify-center rounded-md opacity-0 group-hover:opacity-100 hover:bg-accent transition-all text-muted-foreground hover:text-foreground">
                                        <x-lucide-pencil class="size-4" />
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="outline"
                                        class="flex size-7 items-center justify-center rounded-md opacity-0 group-hover:opacity-100 hover:bg-accent transition-all text-muted-foreground hover:text-foreground">
                                        <a href="{{ route('factura.pdf', $factura->codfactura) }}" target="_blank" class="w-full">
                                            <x-lucide-download class="size-4" />
                                        </a>
                                    </x-ui.button>
                                    <x-ui.button size="sm" variant="outline" @click="$wire.duplicateFactura({{ $factura->id }})"
                                        class="flex size-7 items-center justify-center rounded-md opacity-0 group-hover:opacity-100 hover:bg-accent transition-all text-muted-foreground hover:text-foreground">
                                        <x-lucide-copy class="size-4" />
                                    </x-ui.button>
                                    <x-ui.dropdown-menu>
                                        <x-ui.dropdown-menu-trigger>
                                            <button type="button"
                                                class="flex size-7 items-center justify-center rounded-md opacity-0 group-hover:opacity-100 hover:bg-accent transition-all text-muted-foreground hover:text-foreground">
                                                <x-lucide-ellipsis class="size-4" />
                                            </button>
                                        </x-ui.dropdown-menu-trigger>
                                        <x-ui.dropdown-menu-content align="end" class="w-40">
                                            <x-ui.dropdown-menu-item @click="$wire.editFactura({{ $factura->id }})">
                                                <x-lucide-pencil class="size-4 mr-2" />
                                                Editar
                                            </x-ui.dropdown-menu-item>
                                            <x-ui.dropdown-menu-separator />
                                            <x-ui.dropdown-menu-item>
                                                <x-lucide-file-text class="size-4 mr-2" />
                                                <a href="{{ route('factura.pdf', $factura->codfactura) }}" target="_blank" class="w-full">PDF</a>
                                            </x-ui.dropdown-menu-item>
                                            <x-ui.dropdown-menu-separator />
                                            <x-ui.dropdown-menu-item
                                                wire:click="enviarVeriFactu({{ $factura->id }})"
                                                wire:confirm="¿Enviar esta factura a VeriFactu?"
                                                wire:loading.attr="disabled"
                                                wire:target="enviarVeriFactu"
                                                :disabled="$factura->isVeriFactuSent()">
                                                <x-lucide-send class="size-4 mr-2" />
                                                Enviar VeriFactu
                                            </x-ui.dropdown-menu-item>
                                            <x-ui.dropdown-menu-item
                                                @click="$wire.verVeriFactu({{ $factura->id }})"
                                                :disabled="! $factura->verifactu_status">
                                                <x-lucide-info class="size-4 mr-2" />
                                                Detalles VeriFactu
                                            </x-ui.dropdown-menu-item>
                                            <x-ui.dropdown-menu-separator />
                                            <x-ui.dropdown-menu-item class="text-destructive"
                                                wire:click="cancelarFactura({{ $factura->id }})"
                                                wire:confirm="¿Crear rectificativa y cancelar esta factura?">
                                                <x-lucide-ban class="size-4 mr-2" />
                                                Cancelar
                                            </x-ui.dropdown-menu-item>
                                            <x-ui.dropdown-menu-item class="text-destructive"
                                                @click="$wire.confirmDeleteFactura({{ $factura->id }})">
                                                <x-lucide-trash-2 class="size-4 mr-2" />
                                                Eliminar
                                            </x-ui.dropdown-menu-item>
                                        </x-ui.dropdown-menu-content>
                                    </x-ui.dropdown-menu>
                                </div>
                            </x-ui.table-cell>
                            <x-ui.context-menu-content class="w-48">
                                <x-ui.context-menu-item @click="$wire.editFactura({{ $factura->id }})">
                                    <x-lucide-pencil class="size-4 mr-2" />
                                    Editar
                                </x-ui.context-menu-item>
                                <x-ui.context-menu-item @click="$wire.duplicateFactura({{ $factura->id }})">
                                    <x-lucide-copy class="size-4 mr-2" />
                                    Duplicar
                                </x-ui.context-menu-item>
                                <x-ui.context-menu-separator />
                                <x-ui.context-menu-item>
                                    <x-lucide-file-text class="size-4 mr-2" />
                                    <a href="{{ route('factura.pdf', $factura->codfactura) }}" target="_blank" class="w-full">Ver PDF</a>
                                </x-ui.context-menu-item>
                                <x-ui.context-menu-separator />
                                <x-ui.context-menu-item
                                    wire:click="enviarFactura({{ $factura->id }})"
                                    wire:confirm="¿Enviar esta factura?"
                                    wire:loading.attr="disabled"
                                    wire:target="enviarFactura"
                                >
                                    <x-lucide-send class="size-4 mr-2" />
                                    Enviar Factura
                                </x-ui.context-menu-item>                                <x-ui.context-menu-item
                                    wire:click="enviarVeriFactu({{ $factura->id }})"
                                    wire:confirm="¿Enviar esta factura a VeriFactu?"
                                    wire:loading.attr="disabled"
                                    wire:target="enviarVeriFactu"
                                    :disabled="$factura->isVeriFactuSent()">
                                    <x-lucide-send class="size-4 mr-2" />
                                    Enviar VeriFactu
                                </x-ui.context-menu-item>
                                <x-ui.context-menu-item
                                    @click="$wire.verVeriFactu({{ $factura->id }})"
                                    :disabled="! $factura->verifactu_status">
                                    <x-lucide-info class="size-4 mr-2" />
                                    Detalles VeriFactu
                                </x-ui.context-menu-item>
                                <x-ui.context-menu-separator />
                                <x-ui.context-menu-item class="text-destructive"
                                    wire:click="cancelarFactura({{ $factura->id }})"
                                    wire:confirm="¿Crear rectificativa y cancelar esta factura?">
                                    <x-lucide-ban class="size-4 mr-2" />
                                    Cancelar
                                </x-ui.context-menu-item>
                                <x-ui.context-menu-item class="text-destructive"
                                    @click="$wire.confirmDeleteFactura({{ $factura->id }})">
                                    <x-lucide-trash-2 class="size-4 mr-2" />
                                    Eliminar
                                </x-ui.context-menu-item>
                            </x-ui.context-menu-content>
                        </x-ui.table-row>
                    @empty
                        <x-ui.table-row>
                            <x-ui.table-cell colspan="9" class="py-12 text-center">
                                <x-ui.empty class="border-0 p-0">
                                    <x-lucide-file-search class="size-10 opacity-30 mx-auto mb-2" />
                                    <p class="text-sm font-medium text-muted-foreground">Sin facturas</p>
                                    <p class="text-xs text-muted-foreground opacity-60">Prueba a cambiar los filtros o crea una nueva factura.</p>
                                </x-ui.empty>
                            </x-ui.table-cell>
                        </x-ui.table-row>
                    @endforelse
                </x-ui.table-body>
                @if($facturasList->count() > 0)
                <x-ui.table-footer>
                    <x-ui.table-row class="hover:bg-transparent font-semibold">
                        <x-ui.table-cell colspan="5" class="pl-4 text-xs text-muted-foreground">
                            {{ $facturasList->count() }} factura(s)
                        </x-ui.table-cell>
                        <x-ui.table-cell class="text-right tabular-nums text-sm">
                            {{ number_format($facturasList->sum('baseimponible'), 2, ',', '.') }} €
                        </x-ui.table-cell>
                        <x-ui.table-cell class="text-right tabular-nums text-sm text-muted-foreground">
                            {{ number_format($facturasList->sum('impuesto'), 2, ',', '.') }} €
                        </x-ui.table-cell>
                        <x-ui.table-cell class="text-right tabular-nums text-sm text-primary font-bold">
                            {{ number_format($facturasList->sum('importe'), 2, ',', '.') }} €
                        </x-ui.table-cell>
                        <x-ui.table-cell></x-ui.table-cell>
                    </x-ui.table-row>
                </x-ui.table-footer>
                @endif
            </x-ui.table>
    </x-ui.card-content>
    </x-ui.card>

        </div>{{-- fin listado --}}

            </div>{{-- flex flex-1 flex-col content --}}

    {{-- EDITOR SLIDE-IN — BlatUI Sheet --}}
    <x-ui.sheet entangle="$wire.entangle('showEditor')" x-cloak>
        <x-ui.sheet-content
            side="right"
            :show-close="false"
            class="w-screen max-w-5xl flex flex-col gap-0 p-0 overflow-hidden"
        >




            {{-- Header BlatUI --}}
            <x-ui.sheet-header class="shrink-0 flex flex-row items-center justify-between px-4 py-2.5 border-b gap-0">
                <x-ui.sheet-title class="flex flex-wrap text-sm">
                    @if($editingId) Editar Factura @else Nueva Factura @endif


 {{-- Cod. Factura --}}
                    <div class="flex flex-1 items-top gap-4 ml-4 mt-1">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Nº Factura</label>
                        <x-ui.input
                            size="sm"
                            wire:model="form.codfactura"
                            placeholder="00001_2025"
                            class="w-38 font-mono"
                        />
                    </div>

                    {{-- Fecha --}}
                    <div class="flex flex-1 items-top gap-4 ml-4 mt-1">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Fecha</label>
                    <x-ui.date-picker
                            number-of-months="1"
                            :max="now()->format('Y-m-d')"
                             wire:model="form.fechaemitido"
                            width="w-72"
                        />
                        @error('form.fechaemitido') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                </x-ui.sheet-title>
                <button type="button" @click="open = false"
                    class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            {{-- Barra de campos: Cliente · Remesa · Observaciones --}}
            <div class="shrink-0 bg-muted/40 border-b px-4 py-2">
                <div class="flex flex-wrap items-end gap-x-4 gap-y-2">
                    {{-- Cliente --}}
                    <div class="flex flex-1 items-center gap-2 min-w-0">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Cliente</label>
                        <x-ui.select native size="sm" wire:model.live="form.cliente_id"
                            x-init="$el.focus()"
                            tabindex="1"
                            class="flex-1 min-w-0"
                        >
                            <option value="">— Seleccionar cliente —</option>
                            @foreach($this->clientes as $cliente)
                                <option value="{{ $cliente['id'] }}" @selected($form['cliente_id'] == $cliente['id'])>{{ $cliente['nombretotal'] }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('form.cliente_id') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    {{-- Remesa --}}
                    <div class="flex items-center gap-2 w-48">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Remesa</label>
                        <x-ui.select native size="sm" wire:model="form.remesa_id" class="flex-1 min-w-0">
                            <option value="">— Ninguna —</option>
                            @foreach($remesasFilter as $r)
                                <option value="{{ $r->id }}" @selected($form['remesa_id'] == $r->id)>{{ $r->nombre }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    {{-- Observaciones --}}
                    <div class="flex flex-1 items-center gap-2 min-w-0">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider whitespace-nowrap">Observaciones</label>
                        <x-ui.input size="sm" wire:model="form.observaciones" placeholder="Notas internas de la factura" class="flex-1 min-w-0" />
                    </div>
                </div>
            </div>

            {{-- Sub-barra "Detalle de venta" + botón añadir --}}
            <div class="shrink-0 flex items-center justify-between bg-muted border-b px-4 py-1">
                <span class="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Detalle de venta · Conceptos</span>
                @if($form['cliente_id'])
                    <x-ui.button variant="secondary" wire:click="addLinea" tabindex="-1" class="h-6 gap-1 px-2 text-[11px]">
                        <x-lucide-plus class="size-3" />
                        Añadir línea
                    </x-ui.button>
                @endif
            </div>

            {{-- Área de líneas (scrollable) --}}
            <div class="flex-1 overflow-y-auto">

                @if(!$form['cliente_id'])
                    {{-- Sin cliente seleccionado --}}
                    <x-ui.empty class="h-full border-0 rounded-none text-muted-foreground">
                        <x-lucide-users class="size-10 opacity-30 mx-auto mb-1" />
                        <p class="text-sm font-medium">Selecciona un cliente</p>
                        <p class="text-xs opacity-60">Los conceptos disponibles aparecerán aquí</p>
                    </x-ui.empty>

                @else
                    {{-- Cabecera columnas --}}
                    <div class="grid items-center text-[10px] font-bold text-muted-foreground uppercase tracking-wider bg-muted/60 border-b px-3 py-1"
                         style="grid-template-columns: 2fr 2fr 4rem 4rem 4rem 4rem 4rem 4rem 5rem 1.75rem">
                        <span>Concepto</span>
                        <span>Descripción</span>
                        <span class="text-left">Cant.</span>
                        <span class="text-left">Unidad</span>
                        <span class="text-left">Precio</span>
                        <span class="text-left">Dto.%</span>
                        <span class="text-left">IGIC%</span>
                        <span class="text-left">Ret.%</span>
                        <span class="text-left">Importe</span>
                        <span></span>
                    </div>

                    {{-- Filas --}}
                    <div class="divide-y divide-border">
                        @forelse($form['lineas'] as $index => $linea)
                            <div class="grid items-center gap-1 px-3 py-1.5 hover:bg-accent/30 transition-colors"
                                 style="grid-template-columns: 2fr 1fr 4rem 4rem 4rem 4rem 4rem 4rem 5rem 1.75rem">

                                {{-- Selector de concepto filtrado por cliente --}}
                                <x-ui.select native size="sm"
                                    wire:model.lazy="form.lineas.{{ $index }}.concepto_id"
                                    x-on:change="$wire.selectConceptoParaLinea({{ $index }}, $event.target.value)"
                                    class="w-full"
                                    tabindex="{{ 8 + $index * 7 }}" >
                                                            <option value="">— Concepto —</option>

                                    @foreach($this->conceptos as $c)
                                        <option value="{{ $c['id'] }}" @selected(($linea['concepto_id'] ?? null) == $c['id'])>
                                            {{ $c['label'] }}
                                        </option>
                                    @endforeach
                                </x-ui.select>

                                {{-- Descripción --}}
                                <x-ui.input size="sm" type="text"
                                    wire:model.lazy="form.lineas.{{ $index }}.descripcion"
                                    placeholder="Mes / concepto…"
                                    tabindex="{{ 9 + $index * 7 }}" />

                                {{-- Cantidad --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.cantidad"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 10 + $index * 6 }}" />

                                {{-- Unidad --}}
                                <x-ui.input size="sm" type="text"
                                    wire:model.lazy="form.lineas.{{ $index }}.unidad"
                                    class="text-right"
                                    tabindex="{{ 11 + $index * 6 }}" />

                                {{-- Precio --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.precio"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 12 + $index * 6 }}" />

                                {{-- Dto % --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.descuento"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 13 + $index * 6 }}" />

                                {{-- IGIC % --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.impuesto"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 14 + $index * 6 }}" />

                                {{-- Ret % --}}
                                <x-ui.input size="sm" type="number" step="1"
                                    wire:model.lazy="form.lineas.{{ $index }}.retenciones"
                                    class="text-right tabular-nums"
                                    tabindex="{{ 15 + $index * 6 }}" />

                                {{-- Importe (read-only) --}}
                                <div class="text-right text-sm font-semibold tabular-nums text-foreground pr-1">
                                    {{ number_format($linea['importe'] ?? 0, 2, ',', '.') }} €
                                </div>

                                {{-- Eliminar --}}
                                <button type="button" wire:click="removeLinea({{ $index }})"
                                    class="flex items-center justify-center text-destructive/60 hover:text-destructive rounded hover:bg-destructive/10 p-1 transition-colors cursor-pointer"
                                    title="Eliminar línea">
                                    <x-lucide-x class="size-3.5" />
                                </button>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center text-sm text-muted-foreground">
                                Sin líneas. Pulsa <kbd class="px-1.5 py-0.5 rounded border text-[10px] font-mono bg-muted">Añadir línea</kbd> para comenzar.
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>

            {{-- Barra de totales --}}
            <div class="shrink-0 border-t-2 border-border bg-muted/50 px-4 py-2">
                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-xs">
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Base Exenta</span>
                        <span class="font-semibold tabular-nums">{{ number_format($form['baseexenta'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">B. Imponible</span>
                        <span class="font-semibold tabular-nums">{{ number_format($form['baseimponible'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">IGIC</span>
                        <span class="font-semibold tabular-nums">{{ number_format($form['impuesto'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Retenciones</span>
                        <span class="font-semibold tabular-nums">-{{ number_format($form['retenciones'], 2, ',', '.') }} €</span>
                    </div>
                    <div class="ml-auto flex flex-col items-end">
                        <span class="text-[10px] font-bold text-muted-foreground uppercase tracking-wider">Total Factura</span>
                        <span class="text-xl font-extrabold tabular-nums text-destructive">{{ number_format($form['importe'], 2, ',', '.') }} €</span>
                    </div>
                </div>
            </div>

            {{-- Footer: acciones --}}
            <x-ui.sheet-footer class="shrink-0 border-t px-4 py-2 flex-row justify-between gap-2">
                <x-ui.button variant="secondary" type="button" @click="open = false">
                    Cancelar
                </x-ui.button>
                <x-ui.button  variant="secondary" class="gap-1.5">
                    <x-lucide-file-text class="size-4" />
                    <a href="{{ route('factura.pdf', $form['codfactura']) }}" target="_blank" class="w-full">PDF</a>
                </x-ui.button>
                <x-ui.button  variant="secondary" wire:click="eliminaFactura" class="gap-1.5">
                    <x-lucide-x class="size-4" />
                    Eliminar factura
                </x-ui.button>
                <x-ui.button wire:click="save" class="gap-1.5">
                    <x-lucide-check class="size-4" />
                    Guardar factura
                </x-ui.button>
            </x-ui.sheet-footer>

        </x-ui.sheet-content>
    </x-ui.sheet>

    {{-- MODAL NUEVO CLIENTE --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showClienteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Nuevo cliente</h2>
                </div>
                <button wire:click="$set('showClienteModal', false)" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre / Razón social</label>
                    <input type="text" wire:model="nuevoCliente.nombretotal" placeholder="Ej: Lanzaloe, S.L." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">DNI / NIF / CIF</label>
                    <input type="text" wire:model="nuevoCliente.dni" placeholder="Ej: B12345678" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Email de contacto</label>
                    <input type="email" wire:model="nuevoCliente.email" placeholder="correo@cliente.com" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" wire:model="nuevoCliente.telefono" placeholder="+34 600 000 000" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Domicilio</label>
                        <input type="text" wire:model="nuevoCliente.domicilio" placeholder="Calle, Nº..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Población</label>
                        <input type="text" wire:model="nuevoCliente.poblacion" placeholder="Ciudad" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="$set('showClienteModal', false)" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="saveNuevoCliente" type="button" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer">
                    Guardar cliente
                </button>
            </div>
        </div>
    </div>



    {{-- MODAL NUEVO CONCEPTO --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showConceptoModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Nuevo concepto</h2>
                </div>
                <button wire:click="$set('showConceptoModal', false)" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-3.5 text-xs">
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Nombre / Concepto</label>
                    <input type="text" wire:model="nuevoConcepto.concepto" placeholder="Ej: Servicios de desarrollo web" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Grupo / Categoría</label>
                        <input type="text" wire:model="nuevoConcepto.grupo" placeholder="Ej: Ingeniería" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Unidad de medida</label>
                        <input type="text" wire:model="nuevoConcepto.unidad" placeholder="Ej: UNID, HORA..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Precio (€)</label>
                        <input type="number" step="0.01" wire:model="nuevoConcepto.precio" placeholder="0.00" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Descuento %</label>
                        <input type="number" step="0.01" wire:model="nuevoConcepto.descuento" placeholder="0" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">IGIC %</label>
                        <input type="number" step="0.01" wire:model="nuevoConcepto.impuesto" placeholder="7" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Retención %</label>
                    <input type="number" step="0.01" wire:model="nuevoConcepto.retenciones" placeholder="15" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-2 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500 transition-all">
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="$set('showConceptoModal', false)" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="saveNuevoConcepto" type="button" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer">
                    Guardar concepto
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL DETALLES VERIFACTU --}}
    <div
        x-data
        @keydown.esc.window.prevent="$wire.showVeriFactuModal = false"

        x-cloak
        x-show="$wire.showVeriFactuModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-3xl max-h-[90vh] overflow-y-auto rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <x-lucide-info class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Detalles VeriFactu — {{ $veriFactuDetail['codfactura'] ?? '' }}</h2>
                </div>
                <button wire:click="$set('showVeriFactuModal', false)" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                @if(! empty($veriFactuDetail))
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Estado</label>
                            <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-medium
                                {{ ($veriFactuDetail['status'] ?? '') === 'accepted' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ ($veriFactuDetail['status'] ?? '') === 'sent' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ ($veriFactuDetail['status'] ?? '') === 'rejected' ? 'bg-destructive/10 text-destructive' : '' }}
                                {{ empty($veriFactuDetail['status']) ? 'bg-slate-100 text-slate-600' : '' }}">
                                {{ $veriFactuDetail['status'] ?? 'Sin enviar' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Enviado el</label>
                            <span class="text-slate-700">{{ $veriFactuDetail['sent_at'] ?? '—' }}</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Código respuesta</label>
                            <span class="text-slate-700">{{ $veriFactuDetail['response_code'] ?? '—' }}</span>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Mensaje</label>
                            <span class="text-slate-700">{{ $veriFactuDetail['response_message'] ?? '—' }}</span>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Hash</label>
                            <span class="font-mono text-[10px] text-slate-700 break-all">{{ $veriFactuDetail['hash'] ?? '—' }}</span>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Hash anterior</label>
                            <span class="font-mono text-[10px] text-slate-700 break-all">{{ $veriFactuDetail['previous_hash'] ?? '—' }}</span>
                        </div>
                        @if(! empty($veriFactuDetail['qr_url']))
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">URL QR</label>
                                <a href="{{ $veriFactuDetail['qr_url'] }}" target="_blank" class="text-indigo-600 hover:underline break-all">{{ $veriFactuDetail['qr_url'] }}</a>
                            </div>
                        @endif
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Respuesta AEAT (XML)</label>
                        <pre class="w-full max-h-48 overflow-auto rounded-lg bg-slate-50 border border-slate-200 p-3 text-[10px] font-mono text-slate-700">{{ $veriFactuDetail['response_xml'] ?? 'No disponible' }}</pre>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Petición enviada (XML)</label>
                        <pre class="w-full max-h-48 overflow-auto rounded-lg bg-slate-50 border border-slate-200 p-3 text-[10px] font-mono text-slate-700">{{ $veriFactuDetail['request_xml'] ?? 'No disponible' }}</pre>
                    </div>
                @else
                    <p class="text-slate-500">No hay información de VeriFactu para esta factura.</p>
                @endif
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="$set('showVeriFactuModal', false)" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cerrar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL GENERAR REMESA --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showRemesaModal"
        @keydown.esc.window.prevent="$wire.showRemesaModal = false"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <x-lucide-calendar-plus class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Generar facturas de remesa</h2>
                </div>
                <button wire:click="closeRemesaModal" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                @if($remesaConfirmarRegenerar)
                    <div class="rounded-lg bg-amber-50 border border-amber-200 p-3 text-amber-800">
                        <p class="font-semibold mb-1">Esta remesa ya tiene facturas generadas</p>
                        <p>Para volver a generarla primero se eliminarán las facturas existentes. Los números de factura se perderán y el contador no se ajusta automáticamente.</p>
                    </div>
                @else
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Remesa</label>
                        <x-ui.select native size="sm" wire:model="remesaSeleccionada" class="w-full">
                            <option value="">Selecciona una remesa</option>
                            @foreach($remesasFilter as $remesa)
                                <option value="{{ $remesa->id }}">{{ $remesa->nombre }} ({{ $remesa->estado ?? 'draft' }})</option>
                            @endforeach
                        </x-ui.select>
                    </div>

                    @if($remesaSeleccionada)
                        @php
                            $remesaSel = $remesasFilter->firstWhere('id', $remesaSeleccionada);
                            $remesaFacturasCount = $remesaSel?->facturas_count ?? \App\Models\Factura::where('remesa_id', $remesaSeleccionada)->count();
                        @endphp
                        @if($remesaSel)
                            <div class="rounded-lg bg-slate-50 border border-slate-100 p-3">
                                <p class="text-slate-600"><span class="font-semibold">Estado:</span> {{ $remesaSel?->estado ?? 'draft' }}</p>
                                <p class="text-slate-600"><span class="font-semibold">Facturas generadas:</span> {{ $remesaFacturasCount }}</p>
                            </div>
                        @endif
                    @endif
                @endif
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="closeRemesaModal" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                @if($remesaConfirmarRegenerar)
                    <button wire:click="confirmarRegenerarRemesa" type="button" class="rounded-xl bg-destructive hover:bg-destructive/90 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-destructive/10 active:scale-95 transition-all cursor-pointer">
                        Eliminar y regenerar
                    </button>
                @else
                    <button wire:click="iniciarGenerarRemesa" type="button" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer">
                        Generar
                    </button>
                @endif
            </div>
        </div>
    </div>

    {{-- MODAL CONFIRMACIÓN ELIMINAR FACTURA --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showDeleteModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-destructive/10 text-destructive rounded-lg">
                        <x-lucide-trash-2 class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Eliminar factura</h2>
                </div>
                <button wire:click="closeDeleteModal" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <p class="text-slate-500">¿Estás seguro de que quieres eliminar la(s) factura(s) seleccionada(s)?</p>

                <label class="flex items-start gap-2.5 cursor-pointer group">
                    <input type="checkbox" wire:model="ajustarContador" class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 size-4" />
                    <div>
                        <span class="block text-xs font-semibold text-slate-700">Ajustar contador de facturas</span>
                        <span class="block text-[10px] text-slate-500">Actualizar el contador del año al número de facturas restantes.</span>
                    </div>
                </label>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="closeDeleteModal" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="ejecutarDeleteFactura" type="button" class="rounded-xl bg-destructive hover:bg-destructive/90 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-destructive/10 active:scale-95 transition-all cursor-pointer">
                    Eliminar
                </button>
            </div>
        </div>
    </div>

    {{-- MODAL IMPORTAR PDF --}}
    <div
        x-data
        x-cloak
        x-show="$wire.showImportModal"
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white border border-slate-100 p-6 shadow-2xl transition-all transform scale-100">
            <div class="flex items-center justify-between mb-4 border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <x-lucide-upload class="size-4" />
                    </span>
                    <h2 class="text-sm font-bold text-slate-800">Importar factura desde PDF</h2>
                </div>
                <button wire:click="$set('showImportModal', false)" type="button" class="text-slate-400 hover:text-slate-600 rounded-lg p-1 hover:bg-slate-50 cursor-pointer">
                    <x-lucide-x class="size-4" />
                </button>
            </div>

            <div class="space-y-4 text-xs">
                <p class="text-slate-500">Selecciona un PDF de factura. Se crearán el cliente y el concepto si no existen.</p>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Archivo PDF</label>
                    <input type="file" accept="application/pdf" wire:model="pdfFile" class="w-full text-xs text-slate-700 file:mr-3 file:rounded-xl file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-600 file:text-xs file:font-semibold">
                    @error('pdfFile') <p class="text-destructive text-xs mt-1">{{ $message }}</p> @enderror
                    @if($importMessage)
                        <p class="text-xs mt-1 {{ str_starts_with($importMessage, 'Error') ? 'text-destructive' : 'text-green-600' }}">{{ $importMessage }}</p>
                    @endif
                    <div wire:loading wire:target="pdfFile" class="text-xs text-muted-foreground mt-1">Cargando…</div>
                </div>
            </div>

            <div class="mt-5 pt-3 border-t border-slate-100 flex justify-end gap-2.5">
                <button wire:click="$set('showImportModal', false)" type="button" class="rounded-xl border border-slate-200 hover:bg-slate-50 px-4 py-2 text-xs font-semibold text-slate-500 active:scale-95 transition-all cursor-pointer">
                    Cancelar
                </button>
                <button wire:click="importPdf" type="button" class="rounded-xl bg-indigo-600 hover:bg-indigo-700 px-4 py-2 text-xs font-bold text-white shadow-sm shadow-indigo-600/10 active:scale-95 transition-all cursor-pointer">
                    Importar factura
                </button>
            </div>
        </div>
    </div>

        </div>{{-- flex-col gap-4 --}}
        </div>{{-- p-4 max-w-7xl --}}
    </div>{{-- flex-1 overflow-auto --}}
</div>{{-- root x-data --}}
