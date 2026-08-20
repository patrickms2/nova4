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

$isBusinessWorkspace = request()->routeIs('nova.nova-workspace');
$workspaceProfile = session('nova.workspace', [
    'business_name' => 'Mi negocio',
    'business_icon' => '✦',
    'navigation' => [
        ['id' => 'home', 'icon' => '⌂', 'name' => 'Inicio'],
        ['id' => 'nova', 'icon' => '✦', 'name' => 'NOVA'],
    ],
]);
$workspaceTransition = $workspaceTransition ?? [
    'active' => false,
    'new_capability_id' => null,
];
$workspaceChoices = $workspaceChoices ?? [$workspaceProfile];
$activeWorkspaceId = $activeWorkspaceId ?? ($workspaceProfile['id'] ?? null);
$workspaceUpdates = $workspaceUpdates ?? [];

if ($isBusinessWorkspace) {
    $navItems = [[
        'key' => 'nova',
        'title' => $workspaceProfile['business_name'],
        'icon' => '<span class="text-lg">'.$workspaceProfile['business_icon'].'</span>',
        'route' => 'nova.nova-workspace',
    ]];
}

$businessSidebar = [
    'label' => $workspaceProfile['business_name'],
    'actions' => [],
    'groups' => [[
        'title' => 'Workspace',
        'items' => array_map(
            static fn (array $item): array => [
                'id' => $item['id'],
                'label' => $item['name'],
                'href' => $item['id'] === 'home' ? '/nova' : '#nova-'.$item['id'],
                'icon' => '<span>'.$item['icon'].'</span>',
                'badge' => $workspaceUpdates[$item['id']]['count'] ?? null,
                'children' => in_array($item['id'], ['home', 'nova'], true)
                    ? []
                    : array_map(
                        static fn (string $tool): array => [
                            'label' => $tool,
                            'area' => $item['name'],
                            'href' => '#nova-tool-'.\Illuminate\Support\Str::slug($tool),
                        ],
                        $item['tools'] ?? [],
                    ),
                'introduced' => $item['id'] === ($workspaceTransition['new_capability_id'] ?? null),
                'isNew' => $item['id'] === ($workspaceTransition['new_capability_id'] ?? null),
                'isImproved' => ($item['improvements'] ?? []) !== [],
            ],
            $workspaceProfile['navigation'],
        ),
    ]],
];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="{ dark: $store.theme?.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NovaFact | {{ config('app.name', 'NovaFact') }}</title>
    @vite(['resources/css/app.css', 'resources/css/nova.css', 'resources/js/app.js', 'resources/js/front.js','resources/js/react-flow-panel-builder.jsx'])
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


        :root {
            --nova-950: #09090b;
            --nova-900: #111113;
            --nova-800: #1b1b1f;
            --nova-700: #29292f;
            --nova-accent: #ff6b1a;
        }

        body {
            background: var(--nova-950);
        }

        .nova-shell {
            min-width: 1180px;
            background: var(--nova-950);
        }

        .nova-primary-shell {
            width: 280px;
            margin: 0;
            border-radius: 0;
            background: var(--nova-900);
            box-shadow: none;
        }

        .nova-content {
            background: var(--nova-950);
        }

        .nova-primary-shell > aside:first-child {
            width: 56px;
        }

        .nova-primary-shell > aside:nth-child(2) {
            width: 224px !important;
            border-radius: 0;
            box-shadow: none;
        }

        .nova-primary-shell > aside:nth-child(2) > div[x-show="secondaryOpen"] {
            display: flex !important;
        }

        .nova-selection::selection,
        .nova-selection *::selection {
            background: var(--nova-accent);
            color: white;
        }

        @keyframes nova-boot-enter {
            from { opacity: 0; transform: translateY(8px) scale(.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
    </style>

</head>
<body class="nova-selection h-screen overflow-hidden bg-[#09090b] font-sans antialiased text-zinc-950">

<div x-data="doubleSidebar()" class="h-screen bg-[#09090b]">
    <div
        x-show="booting"
        x-cloak
        class="flex h-screen items-center justify-center bg-[#09090b] text-white"
    >
        <div class="text-center animate-[nova-boot-enter_.5s_ease-out]">
            <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-orange-600 text-2xl shadow-lg shadow-orange-600/20">
                {{ $workspaceProfile['business_icon'] }}
            </div>
            <p class="mt-6 text-xl font-semibold tracking-tight">{{ $workspaceProfile['business_name'] }}</p>
        </div>
    </div>

    <div
        x-show="!booting"
        x-cloak
        x-transition.opacity.duration.350ms
        class="nova-shell relative flex h-screen"
    >
        <div
            x-show="evolutionMessage"
            x-transition.opacity.duration.500ms
            aria-live="polite"
            class="pointer-events-none absolute inset-x-0 top-7 z-50 text-center"
        >
            <span class="rounded-full border border-white/5 bg-[#111113] px-5 py-2 text-sm font-medium text-neutral-300 shadow-xl" x-text="evolutionMessage"></span>
        </div>

        <div class="nova-primary-shell flex m-3 mr-0 overflow-hidden rounded-[22px] ring-1 ring-white/10">
            @include('components.sidebar.primary-nav')
        </div>

        <div class="nova-content flex flex-col flex-1 overflow-hidden">
            <main id="main-content" class="flex-1 overflow-auto bg-transparent">
                {{ $slot }}
            </main>
        </div>
    </div>
</div>

<x-ui.sonner position="bottom-right" />

@livewireScripts

<script>
function doubleSidebar() {
    return {
        workspaceTransition: @js($workspaceTransition),
        booting: @js((bool) ($workspaceTransition['active'] ?? false)),
        evolutionMessage: null,
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
            default => request()->is('nova*') ? 'nova' : 'facturas',
        }),
        activeLink: null,
        openedLinks: ['Facturas'],
        openedGroups: ['Workspace', 'Informes'],
        secondaryOpen: false,
        loading: false,
        showFilters: false,

        init() {
            window.addEventListener('toggle-sidebar-filters', () => {
                this.showFilters = !this.showFilters;
                this.secondaryOpen = true;
            });

            window.addEventListener('nova-workspace-area-opened', (event) => {
                const area = this.sidebars.nova?.groups?.[0]?.items?.find(
                    item => item.id === event.detail.areaId,
                );

                if (!area) return;

                this.activeModule = 'nova';
                this.activeLink = area.label;
                this.secondaryOpen = true;
                this.openedGroups = ['Workspace'];

                if (area.children?.length && !this.openedLinks.includes(area.label)) {
                    this.openedLinks.push(area.label);
                }
            });

            if (this.workspaceTransition.active) {
                this.startWorkspaceEvolution();
            }
        },
        filters: {
            search: '',
            status: '',
            from: '',
            to: '',
        },

        sidebars: {
            nova: @js($businessSidebar),
   
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

        startWorkspaceEvolution() {
            const workspaceGroup = this.sidebars.nova?.groups?.[0];

            if (!workspaceGroup) {
                this.booting = false;
                return;
            }

            const allItems = [...workspaceGroup.items];
            const home = allItems.find(item => item.id === 'home') ?? allItems[0];
            const featuredId = this.workspaceTransition.new_capability_id;
            const featured = allItems.find(item => item.id === featuredId);
            const regularItems = allItems.filter(item => item !== home && item !== featured);
            const buildQueue = featured ? [...regularItems, featured] : regularItems;

            workspaceGroup.items = home ? [home] : [];
            this.activeModule = 'nova';
            this.activeLink = home?.label ?? null;
            this.openedGroups = ['Workspace'];
            this.secondaryOpen = true;

            setTimeout(() => {
                this.booting = false;
                this.evolutionMessage = 'Adaptando NOVA a tu negocio...';

                buildQueue.forEach((item, index) => {
                    setTimeout(() => {
                        item.visible = false;
                        workspaceGroup.items.push(item);
                        this.$nextTick(() => {
                            item.visible = true;
                        });

                        if (index === buildQueue.length - 1) {
                            this.evolutionMessage = 'Todo listo. Ya puedes empezar a trabajar.';

                            setTimeout(() => {
                                this.evolutionMessage = null;
                            }, 1800);

                            if (item.isNew) {
                                setTimeout(() => {
                                    item.isNew = false;
                                }, 2000);
                            }
                        }
                    }, 240 + (index * 260));
                });
            }, 500);
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

            if (link.introduced) {
                window.dispatchEvent(new CustomEvent('nova-capability-opened', {
                    detail: {
                        id: link.id,
                        name: link.label,
                    },
                }));
            }

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

        activateWorkspaceTool(child) {
            this.activeLink = child.label;

            window.Livewire?.dispatch('nova-tool-selected', {
                tool: child.label,
                area: child.area ?? '',
            });
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
