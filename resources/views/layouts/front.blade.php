@php
$navItems = [
    [
        'key'   => 'nova',
        'title' => 'Nova',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 4.5 7.2 12 11.4l7.5-4.2L12 3Z"/><path d="m4.5 12 7.5 4.2 7.5-4.2"/><path d="m4.5 16.8 7.5 4.2 7.5-4.2"/></svg>',
        'route' => 'nova.workspace',
    ],
    [
        'key'   => 'facturas',
        'title' => 'Facturas',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/></svg>',
        'route' => 'facturacion.facturas2',
    ],
    [
        'key'   => 'tareas',
        'title' => 'Tareas',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>',
        'route' => 'facturacion.tareas',
    ],
    [
        'key'   => 'gastos',
        'title' => 'Gastos',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 3a1 1 0 0 1 1-1 1.3 1.3 0 0 1 .7.2l.933.6a1.3 1.3 0 0 0 1.4 0l.934-.6a1.3 1.3 0 0 1 1.4 0l.933.6a1.3 1.3 0 0 0 1.4 0l.933-.6a1.3 1.3 0 0 1 1.4 0l.934.6a1.3 1.3 0 0 0 1.4 0l.933-.6A1.3 1.3 0 0 1 19 2a1 1 0 0 1 1 1v18a1 1 0 0 1-1 1 1.3 1.3 0 0 1-.7-.2l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.934.6a1.3 1.3 0 0 1-1.4 0l-.933-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-1.4 0l-.934-.6a1.3 1.3 0 0 0-1.4 0l-.933.6a1.3 1.3 0 0 1-.7.2 1 1 0 0 1-1-1z"/><path d="M12 17V7"/><path d="M16 8h-6a2 2 0 0 0 0 4h4a2 2 0 0 1 0 4H8"/></svg>',
        'route' => 'facturacion.expenses',
    ],
    [
        'key'   => 'bundles',
        'title' => 'Productos',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 16-9 5-9-5V8l9-5 9 5Z"/><path d="m3 8 9 5 9-5"/><path d="M12 13V3"/></svg>',
        'route' => 'facturacion.bundle-products',
    ],
    [
        'key'   => 'pedidos',
        'title' => 'Pedidos',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'route' => 'facturacion.bundles',
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
    [
        'key'   => 'explore',
        'title' => 'Explora',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>',
        'route' => 'public.explore',
    ],
    [
        'key'   => 'ai-bot',
        'title' => 'AI Bot',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="10" y1="17" x2="14" y2="17"/></svg>',
        'route' => 'ai-bot.view',
    ],
    [
        'key'   => 'admin',
        'title' => 'Admin',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'route' => 'filament.admin.pages.dashboard',
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
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/front.js'])
    @stack('styles')
    <style>
        .hidden-scrollbar::-webkit-scrollbar { display: none; }
        .hidden-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        [x-cloak] { display: none !important; }
            @supports (color: color-mix(in lab, red, red)) {}
        .bg-orange-400/20 {
            background-color: color-mix(in oklab, var(--color-orange-400) 75%, transparent) !important;
        }
        .bg-orange-600 {
            background-color: oklch(0.69 0.15 47.19);
        }
    </style>

</head>
<body class="h-screen overflow-hidden font-sans antialiased text-slate-900 selection:bg-orange-500 selection:text-white">

<div
    x-data="doubleSidebar()"
    class="flex h-screen"
>
    <div class="flex m-3 mr-0 overflow-hidden shadow-2xl rounded-2xl bg-neutral-950 ring-1 ring-neutral-800/50">
        @include('components.sidebar.primary-nav')
    </div>

    <div class="flex flex-col flex-1 overflow-hidden">
        <main id="main-content" class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>
</div>

<x-ui.sonner position="bottom-right" />

@livewireScripts

<script>
function doubleSidebar() {
    return {
        activeModule: @js(match (true) {
                        request()->routeIs('nova.workspace') => 'nova',

            request()->routeIs('facturacion.bundle-products', 'facturacion.imported-products') => 'bundles',
            request()->routeIs('facturacion.bundles', 'public.bundle') => 'pedidos',
            request()->routeIs('facturacion.expenses', 'facturacion.transactions', 'facturacion.categories', 'facturacion.recurring', 'facturacion.budget') => 'gastos',
            request()->routeIs('facturacion.clientes') => 'clientes',
            request()->routeIs('facturacion.empresas') => 'empresas',
            request()->routeIs('public.explore', 'public.explore.places', 'public.explore.availability', 'public.explore.transfer-estimate') => 'explore',
            request()->routeIs('ai-bot.view') => 'ai-bot',
            request()->routeIs('filament.admin.pages.dashboard', 'filament.admin.facturacion', 'filament.admin.facturacion.resources.*') => 'admin',
            request()->routeIs('facturacion.facturas2', 'facturacion.nuevafactura', 'facturacion.remesas', 'factura.zip', 'facturacion.facturas', 'factura.pdf') => 'facturas',
            default => 'facturas',
        }),
        activeLink: null,
        openedLinks: ['Facturas'],
        openedGroups: ['Principal'],
        secondaryOpen: true,
        loading: false,
        showFilters: false,

        init() {
            window.addEventListener('toggle-sidebar-filters', () => {
                this.showFilters = !this.showFilters;
                this.secondaryOpen = true;
            });
        },
        filters: {
            search: '',
            status: '',
            from: '',
            to: '',
        },

        sidebars: {

 nova: {
                label: 'Nova',
                actions: [
                    {
                        label: 'Nueva evolución',
                        primary: true,
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>',
                        handler: '_novaFocusConversation',
                    },
                    {
                        label: 'Ejecutar Showcase',
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 3 14 9-14 9V3Z"/></svg>',
                        handler: '_novaRunShowcase',
                    },
                    {
                        label: 'Instalar .nova',
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 3v12"/><path d="m7 10 5 5 5-5"/><path d="M5 21h14"/></svg>',
                        handler: '_novaInstallPackage',
                    },
                ],
                groups: [
                    {
                        title: 'Workspace',
                        items: [
                            { label: 'Overview', href: '/nova', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7" rx="2"/><rect x="14" y="3" width="7" height="7" rx="2"/><rect x="3" y="14" width="7" height="7" rx="2"/><rect x="14" y="14" width="7" height="7" rx="2"/></svg>' },
                            { label: 'Conversation', href: '#nova-conversation', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4Z"/></svg>' },
                            { label: 'Activity', href: '#nova-activity', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h4l3-8 4 16 3-8h4"/></svg>' },
                        ],
                    },
                    {
                        title: 'Capabilities',
                        items: [
                            { label: 'Capabilities', href: '#nova-capabilities', badge: 6, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3 8 4.5-8 4.5-8-4.5L12 3Z"/><path d="m4 12 8 4.5 8-4.5"/><path d="m4 16.5 8 4.5 8-4.5"/></svg>' },
                            { label: 'Packages', href: '#nova-packages', badge: 5, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8l-9-5-9 5v8l9 5 9-5Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>' },
                            { label: 'Providers', href: '#nova-providers', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 9h8"/><path d="M8 15h8"/><rect x="3" y="4" width="18" height="16" rx="3"/></svg>' },
                            { label: 'Generated files', href: '#nova-generated', badge: 5, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Z"/><path d="M14 2v6h6"/></svg>' },
                        ],
                    },
                    {
                        title: 'System',
                        items: [
                            { label: 'Knowledge', href: '#nova-knowledge', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2Z"/></svg>' },
                            { label: 'Reports', href: '#nova-reports', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 19V9"/><path d="M10 19V5"/><path d="M16 19v-7"/><path d="M22 19H2"/></svg>' },
                            { label: 'Doctor', href: '#nova-doctor', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 12h6"/><path d="M12 9v6"/><circle cx="12" cy="12" r="9"/></svg>' },
                            { label: 'Learn more', href: 'https://nova.example.com/start', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3h7v7"/><path d="M10 14 21 3"/><path d="M21 14v5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5"/></svg>' },
                        ],
                    },
                ],
            },
facturas: {
                label: 'Facturas',
                actions: [
                    {
                        label: 'Nueva factura',
                        primary: true,
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>',
                        handler: '_sidebarNewFactura',
                    },
                    {
                        label: 'Listado',
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>',
 href: '{{ route('facturacion.facturas2') }}',
                    },
                    {
                        label: 'Filtrar',
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>',
                        handler: '_sidebarToggleFilters',
                    },
                    {
                        label: 'Remesa',
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>',
                        handler: '_sidebarOpenRemesa',
                    },
                    {
                        label: 'Importar',
                        icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8 12 3 7 8"/><path d="M12 3v12"/></svg>',
                        handler: '_sidebarOpenImport',
                    },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            {
                                label: 'Facturas',
                                icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>',
                                children: [
                                    { label: 'Todas', href: '{{ route('facturacion.facturas2') }}', badge: 40 },
                                    { label: 'Borradores', href: '#', badge: 10 },
                                    { label: 'Emitidas', href: '#', badge: 28 },
                                    { label: 'Vencidas', href: '#', badge: 2 },
                                ],
                            },
                            { label: 'Nueva factura', href: '{{ route('facturacion.nuevafactura') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>' },
                            { label: 'Remesas', href: '{{ route('facturacion.remesas') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>' },
                            { label: 'Descargar ZIP', href: '{{ route('factura.zip') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>' },
                        ],
                    },
                    {
                        title: 'Informes',
                        items: [
                            { label: 'Dashboard', href: '{{ route('facturacion.facturas') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/></svg>' },
                            { label: 'Ventas por cliente', href: '#', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 20V10"/><path d="M12 20V4"/><path d="M6 20v-6"/></svg>' },
                            { label: 'IVA repercutido', href: '#', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>' },
                        ],
                    }
                ],
            },

    tareas: {
        label: 'Tareas',
            actions: [
            { label: 'Nuevo tarea', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('facturacion.nuevafactura') }}' },
                { label: 'Nuevo proyecto', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('facturacion.nuevafactura') }}' },
                { label: 'Nuevo nota', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('facturacion.nuevafactura') }}' },

                { label: 'Filtrar', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', handler: '_sidebarToggleFilters' },
        ],
            groups: [
            {
                title: 'Principal',
                items: [
                    { label: 'Proyectos', href: '{{ route('facturacion.projects.index') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="M9 3v18"/><path d="M15 3v18"/><path d="M3 9h18"/><path d="M3 15h18"/></svg>' },
                    { label: 'Tareas', href: '{{ route('facturacion.tasks.index') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>' },
                    { label: 'Notas', href: '{{ route('facturacion.notes.index') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/></svg>' },
                ],
            },
        ],
    },
            pedidos: {
                label: 'Pedidos',
                actions: [
                    { label: 'Nuevo pedido', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('public.bundle') }}' },
                    { label: 'Filtrar', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', handler: '_sidebarToggleFilters' },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Pedidos cruzados', href: '{{ route('facturacion.bundles') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>' },
                        ],
                    },
                ],
            },
            bundles: {
                label: 'Bundles',
                actions: [
                    { label: 'Nuevo producto', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('facturacion.bundle-products') }}?new=1' },
                    { label: 'Filtrar', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', handler: '_sidebarToggleFilters' },
                ],
                groups: [
                    {
                        title: 'Catálogo',
                        items: [
                            { label: 'Bundle Products', href: '{{ route('facturacion.bundle-products') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21 16-9 5-9-5V8l9-5 9 5Z"/><path d="m3 8 9 5 9-5"/><path d="M12 13V3"/></svg>' },
                            { label: 'Productos importados', href: '{{ route('facturacion.imported-products') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10 12 15 17 10"/><path d="M12 15V3"/></svg>' },
                        ],
                    },
                    {
                        title: 'Enlaces',
                        items: [
                            { label: 'Formulario público', href: '{{ route('public.bundle') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>' },
                        ],
                    },
                ],
            },
            gastos: {
                label: 'Gastos',
                actions: [
                    { label: 'Nuevo gasto', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('facturacion.expenses') }}?new=1' },
{ label: 'Todos los gastos',primary: false, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l4-2 4 2 4-2 4 2V2H4z"/><path d="M16 8H8"/><path d="M16 12H8"/><path d="M10 16H8"/></svg>', href: '{{ route('facturacion.expenses') }}' },
                    { label: 'Filtrar', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', handler: '_sidebarToggleFilters' },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todos los gastos', href: '{{ route('facturacion.expenses') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 2v20l4-2 4 2 4-2 4 2V2H4z"/><path d="M16 8H8"/><path d="M16 12H8"/><path d="M10 16H8"/></svg>' },
                            { label: 'Transacciones', href: '{{ route('facturacion.transactions') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>' },
                            { label: 'Categorías', href: '{{ route('facturacion.categories') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2z"/><circle cx="7" cy="7" r="2"/></svg>' },
                            { label: 'Recurrentes', href: '{{ route('facturacion.recurring') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/></svg>' },
                            { label: 'Presupuesto', href: '{{ route('facturacion.budget') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 12V8H6a2 2 0 0 1-2-2c0-1.1.9-2 2-2h12v4"/><path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6"/><path d="M16 12h6"/></svg>' },
                        ],
                    },
                ],
            },

            clientes: {
                label: 'Clientes',
                actions: [
                    { label: 'Nuevo cliente', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '#' },
{ label: 'Listado clientes', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>', href: '{{ route('facturacion.clientes') }}' },
                    { label: 'Filtrar', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', handler: '_sidebarToggleFilters' },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todos los clientes', href: '{{ route('facturacion.clientes') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' },
                            { label: 'Activos', href: '#', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>' },
                            { label: 'Sin facturación', href: '#', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/></svg>' },
                        ],
                    },
                ],
            },

            empresas: {
                label: 'Empresas',
                actions: [
                    { label: 'Nueva empresa', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '#' },
                    { label: 'Filtrar', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', handler: '_sidebarToggleFilters' },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Todas las empresas', href: '{{ route('facturacion.empresas') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 22V10a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12"/><path d="M6 10H4a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2h-2"/><path d="M10 8V6a2 2 0 0 1 2-2h0a2 2 0 0 1 2 2v2"/><path d="M14 22v-4a2 2 0 0 0-4 0v4"/></svg>' },
                            { label: 'Configuración fiscal', href: '#', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>' },
                        ],
                    },
                ],
            },
            explore: {
                label: 'Explora',
                actions: [
                    { label: 'Buscar', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>', href: '{{ route('public.explore') }}' },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Inicio', href: '{{ route('public.explore') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"/></svg>' },
                            { label: 'Lugares', href: '{{ route('public.explore.places') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' },
                            { label: 'Disponibilidad', href: '{{ route('public.explore.availability') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>' },
                        ],
                    },
                ],
            },
            'ai-bot': {
                label: 'AI Bot',
                actions: [
                    { label: 'Nuevo chat', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>', href: '{{ route('ai-bot.view') }}' },
                ],
                groups: [
                    {
                        title: 'Principal',
                        items: [
                            { label: 'Chat', href: '{{ route('ai-bot.view') }}', icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h3a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V9a2 2 0 0 1 2-2h3V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2z"/><line x1="9" y1="13" x2="15" y2="13"/><line x1="10" y1="17" x2="14" y2="17"/></svg>' },
                        ],
                    },
                ],
            },
            admin: {
                label: 'Admin',
                actions: [
                    { label: 'Filament', primary: true, icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>', href: '/admin' },
                ],
                groups: [
                    {
                        title: 'Paneles',
                        items: [
                              ],
                    },
                ],
            },
        },

        get moduleLabel() {
            return this.sidebars[this.activeModule]?.label ?? '';
        },

        get currentGroups() {
            return this.sidebars[this.activeModule]?.groups ?? [];
        },

        get currentActions() {
            return this.sidebars[this.activeModule]?.actions ?? [];
        },

        selectModule(id) {
            if (this.activeModule === id) {
                this.secondaryOpen = !this.secondaryOpen;
                return;
            }

            this.activeModule = id;
            this.activeLink = null;
            this.openedLinks = [];
            this.openedGroups = ['Principal', 'Informes'];
            this.showFilters = false;
            this.secondaryOpen = true;
            this.loading = true;

            setTimeout(() => {
                this.loading = false;
            }, 260);
        },

        toggleGroup(title) {
            this.openedGroups = this.openedGroups.includes(title)
                ? this.openedGroups.filter(item => item !== title)
                : [...this.openedGroups, title];
        },

        toggleLink(link) {
            this.activeLink = link.label;

            if (link.children?.length) {
                this.openedLinks = this.openedLinks.includes(link.label)
                    ? this.openedLinks.filter(item => item !== link.label)
                    : [...this.openedLinks, link.label];

                return;
            }

            if (link.href && link.href !== '#') {
                window.location.href = link.href;
            }
        },

        applyFilters() {
            const params = new URLSearchParams();

            Object.entries(this.filters).forEach(([key, value]) => {
                if (value) params.append(key, value);
            });

            window.location.href = `${window.location.pathname}?${params.toString()}`;
        },

        resetFilters() {
            this.filters = {
                search: '',
                status: '',
                from: '',
                to: '',
            };
        },
    }
}

window._sidebarToggleFilters = () => {
    window.dispatchEvent(new CustomEvent('toggle-sidebar-filters'));
};
window._sidebarNewFactura = () => {
    window.location.href = @js(route('facturacion.facturas2') . '?new=1');
};

window._sidebarOpenRemesa = () => {
    window.dispatchEvent(new CustomEvent('open-remesa-modal'));
};

window._sidebarOpenImport = () => {
    window.dispatchEvent(new CustomEvent('open-import-modal'));
};


window._novaFocusConversation = () => {
    document.querySelector('#nova-conversation input')?.focus();
};

window._novaRunShowcase = () => {
    window.Livewire?.dispatch?.('run-nova-showcase');
    document.querySelector('[wire\:click="runShowcase"]')?.click();
};

window._novaInstallPackage = () => {
    window.dispatchEvent(new CustomEvent('open-nova-package-installer'));
};
</script>

</body>
</html>
