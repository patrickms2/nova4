@php
$currentRoute = request()->route()?->getName();

$navItems = [
    ['key' => 'facturas', 'title' => 'Facturas', 'icon' => 'file-text', 'route' => 'facturacion.facturas2'],
    ['key' => 'bundles', 'title' => 'Bundles', 'icon' => 'receipt', 'route' => 'facturacion.bundles'],
    ['key' => 'gastos', 'title' => 'Gastos', 'icon' => 'receipt', 'route' => 'facturacion.expenses'],
    ['key' => 'clientes', 'title' => 'Clientes', 'icon' => 'users', 'route' => 'facturacion.clientes'],
    ['key' => 'empresas', 'title' => 'Empresas', 'icon' => 'building-2', 'route' => 'facturacion.empresas'],
];

$quickItems = [
    ['title' => 'Nueva factura', 'icon' => 'plus', 'route' => 'facturacion.nuevafactura'],
    ['title' => 'Filtros', 'icon' => 'filter', 'route' => null],
];
@endphp
<div
    x-data="doubleSidebar()"
    class="flex h-screen bg-neutral-900 text-white"
>
    {{-- Sidebar principal --}}
    <aside class="m-6 mr-0 flex w-14 flex-col items-center rounded-l-2xl bg-black border-r border-neutral-800">
        <div class="mt-6 mb-8">
            <div class="h-6 w-6 rounded border border-white"></div>
        </div>

        <nav class="flex flex-1 flex-col items-center gap-3">
            <template x-for="item in primaryItems" :key="item.id">
                <button
                    @click="selectPrimary(item.id)"
                    class="flex h-10 w-10 items-center justify-center rounded-xl transition"
                    :class="activePrimary === item.id ? 'bg-neutral-800 text-white' : 'text-neutral-400 hover:bg-neutral-900 hover:text-white'"
                >
                    <span x-html="item.icon"></span>
                </button>
            </template>
        </nav>

        <div class="mb-4 flex flex-col gap-3">
            <button class="text-neutral-400 hover:text-white">
                ⚙
            </button>

            <button class="flex h-9 w-9 items-center justify-center rounded-full border border-neutral-800 text-neutral-300 hover:bg-neutral-900">
                👤
            </button>
        </div>
    </aside>

    {{-- Segundo sidebar --}}
    <aside
        class="my-6 overflow-hidden rounded-r-2xl bg-black transition-all duration-300"
        :class="secondaryCollapsed ? 'w-0' : 'w-72'"
    >
        <div class="h-full w-72 p-4">
            {{-- Header --}}
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-xl font-bold" x-text="currentSidebar.title"></h2>

                <button
                    @click="secondaryCollapsed = true"
                    class="rounded-lg p-2 text-neutral-400 hover:bg-neutral-900 hover:text-white"
                >
                    ‹
                </button>
            </div>

            {{-- Search --}}
            <div class="mb-6">
                <div class="flex items-center gap-2 rounded-lg border border-neutral-800 px-3 py-2">
                    <span class="text-neutral-400">⌕</span>
                    <input
                        type="text"
                        placeholder="Search tasks, projects..."
                        class="w-full bg-transparent text-sm text-white placeholder-neutral-400 outline-none"
                    >
                </div>
            </div>

            {{-- Loading --}}
            <div x-show="loading" class="space-y-5">
                <template x-for="i in 4">
                    <div class="space-y-3">
                        <div class="h-3 w-28 animate-pulse rounded bg-neutral-800"></div>
                        <div class="h-9 w-full animate-pulse rounded-lg bg-neutral-900"></div>
                        <div class="h-9 w-11/12 animate-pulse rounded-lg bg-neutral-900"></div>
                    </div>
                </template>
            </div>

            {{-- Content --}}
            <nav x-show="!loading" x-transition class="space-y-7">
                <template x-for="group in currentSidebar.groups" :key="group.title">
                    <div>
                        <h3 class="mb-3 px-4 text-sm font-semibold text-neutral-400" x-text="group.title"></h3>

                        <div class="space-y-1">
                            <template x-for="link in group.items" :key="link.label">
                                <div>
                                    <button
                                        @click="toggleItem(link)"
                                        class="flex w-full items-center justify-between rounded-lg px-4 py-2.5 text-sm font-semibold transition"
                                        :class="activeItem === link.label ? 'bg-neutral-900 text-white' : 'text-neutral-100 hover:bg-neutral-900'"
                                    >
                                        <span class="flex items-center gap-3">
                                            <span x-html="link.icon"></span>
                                            <span x-text="link.label"></span>
                                        </span>

                                        <span
                                            x-show="link.children"
                                            class="transition"
                                            :class="opened.includes(link.label) ? 'rotate-180' : ''"
                                        >
                                            ˅
                                        </span>
                                    </button>

                                    <div
                                        x-show="link.children && opened.includes(link.label)"
                                        x-collapse
                                        class="ml-9 mt-1 space-y-1"
                                    >
                                        <template x-for="child in link.children" :key="child">
                                            <a
                                                href="#"
                                                class="block rounded-lg px-3 py-2 text-sm text-neutral-400 hover:bg-neutral-900 hover:text-white"
                                                x-text="child"
                                            ></a>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </nav>
        </div>
    </aside>

    {{-- Botón para abrir segundo sidebar --}}
    <button
        x-show="secondaryCollapsed"
        @click="secondaryCollapsed = false"
        class="fixed left-24 top-8 rounded-xl bg-black px-3 py-2 text-white shadow-lg hover:bg-neutral-900"
    >
        Abrir
    </button>

    {{-- Contenido principal --}}
    <main class="flex-1 p-8">
        <h1 class="text-2xl font-bold" x-text="activeItem || currentSidebar.title"></h1>
    </main>
</div>

</div>

<script>
    function doubleSidebar() {
        return {
            activePrimary: 'facturas',
            activeItem: 'Todas las facturas',
            loading: false,
            secondaryCollapsed: false,
            opened: ['Principal'],

            primaryItems: [
                {
                    id: 'facturas',
                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M4 5h16v14H4zM4 10h16"/></svg>`
                },
                {
                    id: 'gastos',
                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/></svg>`
                },
                {
                    id: 'clientes',
                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M4 19l5-6 4 3 7-9"/></svg>`
                },
                {
                    id: 'clientes',
                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M4 19l5-6 4 3 7-9"/></svg>`
                },
                {
                    id: 'clientes',
                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M4 19l5-6 4 3 7-9"/></svg>`
                }
            ],

            sidebars: {
            facturas: {
                title: 'Facturas',
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todas las facturas', href: '{{ route('facturacion.facturas2') }}', icon: '📄' },
                            { label: 'Nueva factura', href: '{{ route('facturacion.nuevafactura') }}', icon: '＋' },
                            { label: 'Pendientes', href: '#', icon: '⏱' },
                            { label: 'Vencidas', href: '#', icon: '⚠' },
                        ]
                    },
                    {
                        title: 'Informes',
                        items: [
                            { label: 'Ventas por cliente', href: '#', icon: '📊' },
                            { label: 'IVA repercutido', href: '#', icon: '％' },
                        ]
                    }
                ]
            },

            gastos: {
                title: 'Gastos',
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todos los gastos', href: '{{ route('facturacion.expenses') }}', icon: '🧾' },
                            { label: 'Nuevo gasto', href: '#', icon: '＋' },
                            { label: 'Pendientes OCR', href: '#', icon: '🔍' },
                        ]
                    }
                ]
            },

            clientes: {
                title: 'Clientes',
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todos los clientes', href: '{{ route('facturacion.clientes') }}', icon: '👥' },
                            { label: 'Nuevo cliente', href: '#', icon: '＋' },
                        ]
                    }
                ]
            },
            empresas: {
                title: 'Empresas',
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todas las empresas', href: '{{ route('facturacion.empresas') }}', icon: '🏢' },
                            { label: 'Nueva empresa', href: '#', icon: '＋' },
                        ]
                    }
                }

            },
            },

                      get currentSidebar() {

                return this.sidebars[this.activePrimary];

            },

            selectPrimary(id) {

                this.loading = true;

                this.activePrimary = id;

                this.activeItem = null;

                this.opened = [];

                this.secondaryCollapsed = false;

                setTimeout(() => {

                    this.loading = false;

                }, 450);

            },

            toggleItem(link) {

                this.activeItem = link.label;

                if (!link.children) return;

                if (this.opened.includes(link.label)) {

                    this.opened = this.opened.filter(item => item !== link.label);

                } else {

                    this.opened.push(link.label);

                }

            }
        }
    }
 function doubleSidebar() {

        return {

            activePrimary: 'dashboard',

            activeItem: 'Overview',

            loading: false,

            secondaryCollapsed: false,

            opened: ['Executive Summary'],

            primaryItems: [

                {

                    id: 'dashboard',

                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M4 5h16v14H4zM4 10h16"/></svg>`

                },

                {

                    id: 'reports',

                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M6 4h12v16H6zM9 8h6M9 12h6M9 16h4"/></svg>`

                },

                {

                    id: 'analytics',

                    icon: `<svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-width="1.7" d="M4 19l5-6 4 3 7-9"/></svg>`

                }

            ],

            sidebars: {

                dashboard: {

                    title: 'Dashboard',

                    groups: [

                        {

                            title: 'Dashboard Types',

                            items: [

                                { label: 'Overview', icon: '◎' },

                                {

                                    label: 'Executive Summary',

                                    icon: '▣',

                                    children: ['CEO Overview', 'KPIs', 'Board Report']

                                },

                                {

                                    label: 'Operations Dashboard',

                                    icon: '▤',

                                    children: ['Logistics', 'Production', 'Quality']

                                },

                                {

                                    label: 'Financial Dashboard',

                                    icon: '⌁',

                                    children: ['Revenue', 'Expenses', 'Forecast']

                                }

                            ]

                        },

                        {

                            title: 'Report Summaries',

                            items: [

                                {

                                    label: 'Weekly Reports',

                                    icon: '▧',

                                    children: ['This Week', 'Last Week']

                                },

                                {

                                    label: 'Monthly Insights',

                                    icon: '★',

                                    children: ['January', 'February', 'March']

                                },

                                {

                                    label: 'Quarterly Analysis',

                                    icon: '◎',

                                    children: ['Q1', 'Q2', 'Q3', 'Q4']

                                }

                            ]

                        }

                    ]

                },

                reports: {

                    title: 'Reports',

                    groups: [

                        {

                            title: 'Management',

                            items: [

                                { label: 'Daily Report', icon: '▧' },

                                { label: 'Team Report', icon: '◫', children: ['Sales Team', 'Ops Team'] },

                                { label: 'Custom Reports', icon: '✦', children: ['Builder', 'Templates'] }

                            ]

                        }

                    ]

                },

                analytics: {

                    title: 'Analytics',

                    groups: [

                        {

                            title: 'Business Intelligence',

                            items: [

                                { label: 'Performance Metrics', icon: '▤', children: ['Traffic', 'Sales', 'Retention'] },

                                { label: 'Predictive Analytics', icon: '⌁', children: ['Forecasts', 'Trends'] }

                            ]

                        }

                    ]

                }

            },

            get currentSidebar() {

                return this.sidebars[this.activePrimary];

            },

            selectPrimary(id) {

                this.loading = true;

                this.activePrimary = id;

                this.activeItem = null;

                this.opened = [];

                this.secondaryCollapsed = false;

                setTimeout(() => {

                    this.loading = false;

                }, 450);

            },

            toggleItem(link) {

                this.activeItem = link.label;

                if (!link.children) return;

                if (this.opened.includes(link.label)) {

                    this.opened = this.opened.filter(item => item !== link.label);

                } else {

                    this.opened.push(link.label);

                }

            }

        }

    }
</script>
