@php
$navItems = [
    [
        'key'   => 'facturas',
        'title' => 'Facturas',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
        'route' => 'facturacion.facturas2',
    ],
    [
        'key'   => 'gastos',
        'title' => 'Gastos',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3a1 1 0 0 1 1-1 1.3 1.3 0 0 1 .7.2l.933.6a1.3 1.3 0 0 0 1.4 0l.934-.6a1.3 1.3 0 0 1 1.4 0l.933.6a1.3 1.3 0 0 0 1.4 0l.933-.6a1.3 1.3 0 0 1 1.4 0l.934.6a1.3 1.3 0 0 0 1.4 0l.933-.6A1.3 1.3 0 0 1 19 2a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1 1.3 1.3 0 0 1-.7-.2l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.934.6a1.3 1.3 0 0 1-1.4 0l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-1.4 0l-.934-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-.7.2 1 1 0 0 1-1-1z"/><path d="M12 17V7"/><path d="M16 8h-6a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8"/></svg>',
        'route' => 'facturacion.expenses',
    ],
    [
        'key'   => 'clientes',
        'title' => 'Clientes',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'route' => 'facturacion.clientes',
    ],
    [
        'key'   => 'empresas',
        'title' => 'Empresas',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"/><path d="M10 8h4"/><path d="M10 12h4"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/></svg>',
        'route' => 'facturacion.empresas',
    ],
];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="{ dark: $store.theme?.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NovaFact | {{ config('app.name', 'NovaFact') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        .hidden-scrollbar::-webkit-scrollbar { display: none; }
        .hidden-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="h-screen overflow-hidden antialiased font-sans bg-neutral-900 text-slate-100 selection:bg-orange-500 selection:text-white">

{{-- ── ROOT WRAPPER ──────────────────────────────────────────────────── --}}
<div
    x-data="doubleSidebar()"
    class="flex h-screen"
>
    {{-- ── SIDEBAR CONTAINER ──────────────────────────────────────────── --}}
    <div class="m-3 mr-0 flex rounded-2xl overflow-hidden shadow-2xl bg-neutral-950 ring-1 ring-neutral-800/50">
        @include('components.sidebar.primary-nav')
    </div>

    {{-- ── MAIN CONTENT ──────────────────────────────────────────────── --}}
    <main id="main-content" class="flex-1 overflow-auto">
        {{ $slot ?? '' }}
    </main>
</div>

<x-ui.sonner position="bottom-right" />

@livewireScripts

<script>
function doubleSidebar() {
    return {
        activeModule: 'facturas',
        activeLink:   null,
        openedLinks:  [],
        secondaryOpen: true,
        loading: false,

        sidebars: {
            facturas: {
                label: 'Facturas',
                groups: [
                    {
                        title: '',
                        items: [
                            {
                                label: 'Facturas', icon: '📄', action: 'new',
                                children: [
                                    { label: 'Todas',      href: '{{ route('facturacion.facturas2') }}' },
                                    { label: 'Pendientes', href: '#' },
                                    { label: 'Vencidas',   href: '#' },
                                ]
                            },
                            { label: 'Nueva factura', href: '{{ route('facturacion.facturas2') }}?new=1', icon: '＋' },
                        ]
                    },
                    {
                        title: 'Informes',
                        items: [
                            { label: 'Ventas por cliente', href: '#', icon: '📊', action: 'new' },
                            { label: 'IVA repercutido',    href: '#', icon: '％' },
                        ]
                    }
                ]
            },
            gastos: {
                label: 'Gastos',
                groups: [
                    {
                        title: '',
                        items: [
                            { label: 'Todos los gastos',  href: '{{ route('facturacion.expenses') }}', icon: '🧾', action: 'new' },
                            { label: 'Nuevo gasto',       href: '#', icon: '＋' },
                            { label: 'Pendientes de OCR', href: '#', icon: '🔍' },
                        ]
                    }
                ]
            },
            clientes: {
                label: 'Clientes',
                groups: [
                    {
                        title: '',
                        items: [
                            { label: 'Todos los clientes', href: '{{ route('facturacion.clientes') }}', icon: '👥', action: 'new' },
                            { label: 'Nuevo cliente',      href: '#', icon: '＋' },
                        ]
                    }
                ]
            },
            empresas: {
                label: 'Empresas',
                groups: [
                    {
                        title: '',
                        items: [
                            { label: 'Todas las empresas', href: '{{ route('facturacion.empresas') }}', icon: '🏢', action: 'new' },
                            { label: 'Nueva empresa',      href: '#', icon: '＋' },
                        ]
                    }
                ]
            },
        },

        get moduleLabel() {
            return this.sidebars[this.activeModule]?.label ?? '';
        },

        get currentGroups() {
            return this.sidebars[this.activeModule]?.groups ?? [];
        },

        selectModule(id) {
            if (this.activeModule === id && this.secondaryOpen) {
                this.secondaryOpen = false;
                return;
            }
            this.activeModule  = id;
            this.activeLink    = null;
            this.openedLinks   = [];
            this.secondaryOpen = true;
            this.loading = true;
            setTimeout(() => { this.loading = false; }, 250);
        },

        toggleLink(link) {
            this.activeLink = link.label;

            if (link.href && link.href !== '#' && (!link.children || !link.children.length)) {
                window.location.href = link.href;
                return;
            }

            if (link.children && link.children.length) {
                this.openedLinks = this.openedLinks.includes(link.label)
                    ? this.openedLinks.filter(l => l !== link.label)
                    : [...this.openedLinks, link.label];
            }
        },
    }
}
</script>

</body>
</html>
