@php
$navItems = [

    [
        'key'   => 'dashboard',
        'title' => 'Dashboard',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'route' => 'comunigest.dashboard',
    ],
    [
        'key'   => 'incidents',
        'title' => 'Incidencias',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
        'route' => 'comunigest.admin.incidents',
    ],  
    [
        'key'   => 'workorders',
        'title' => 'Órdenes',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><line x1="10" y1="9" x2="8" y2="9"/></svg>',
        'route' => 'comunigest.admin.work-orders',
    ],  
      [
        'key'   => 'usuarios',
        'title' => 'Usuarios',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',
        'route' => 'comunigest.admin.users',

    ],
      [
        'key'   => 'communities',
        'title' => 'Comunidades',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2"/><path d="M10 8h4"/><path d="M10 12h4"/><path d="M14 21v-3a2 2 0 0 0-4 0v3"/></svg>',
        'route' => 'comunigest.admin.communities',

    ],
    [
        'key'   => 'settings',
        'title' => 'Ajustes',
        'icon'  => '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>',
        'route' => 'comunigest.admin.settings',
    ],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="{ dark: $store.theme?.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nova Community | {{ config('app.name', 'Comunigest') }}</title>
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
        @include('components.sidebar.nav')
    </div>

    <div class="flex flex-col flex-1 overflow-hidden">
        <main id="main-content" class="flex-1 overflow-auto">
            {{ $slot }}
        </main>
    </div>
</div>


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
            request()->routeIs('comunigest.dashboard') => 'dashboard',
            request()->routeIs('comunigest.admin.users') => 'usuarios',
            request()->routeIs('comunigest.admin.communities') => 'comunidades',
            request()->routeIs('comunigest.admin.work-orders') => 'workorders',
            request()->routeIs('comunigest.admin.work-orders.*') => 'workorders',
            request()->routeIs('comunigest.admin.incidents') => 'incidents',
            request()->routeIs('comunigest.admin.settings', 'comunigest.admin.order-types', 'comunigest.admin.task-types') => 'settings',
            request()->routeIs('comunigest.*') => 'comunigest',
            default => 'facturas',
        }),
        activeLink: null,
        openedLinks: ['Dashboard'],
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
             comunidades: {
                label: 'Comunidades',
icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>', 
href: '{{ route('comunigest.admin.users') }}'
 },
             usuarios: {
                label: 'Usuarios',
                icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>',

href: '{{ route('comunigest.admin.users') }}' 
            },
workorders: {
                label: 'Órdenes',
                href: '{{ route('comunigest.admin.work-orders') }}', 
                icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>' 
            },
incidents: {
                label: 'Incidencias',
                href: '{{ route('comunigest.admin.incidents') }}', 
                icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
             },

            dashboard: {
                label: 'Trabajo del día',
                href: '{{ route('comunigest.dashboard') }}', 
                icon: '<svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' 
            }

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
            this.secondaryOpen = false;
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
