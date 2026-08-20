@props([])

@php
$currentRoute = request()->route()?->getName();

$isActive = function (string $route) use ($currentRoute): bool {
    if ($currentRoute === $route) {
        return true;
    }

    if (str_starts_with($route, 'filament.')) {
        return str_starts_with($currentRoute ?? '', rtrim($route, '.index').'.');
    }

    return false;
};

$navItems = [
    ['title' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'novafactu.dashboard'],
    ['title' => 'Facturas', 'icon' => 'file-text', 'route' => 'filament.admin.facturacion.resources.facturas.index'],
    ['title' => 'Gastos', 'icon' => 'receipt', 'route' => 'filament.admin.facturacion.resources.gastos.index'],
    ['title' => 'Clientes', 'icon' => 'users', 'route' => 'filament.admin.facturacion.resources.clientes.index'],
    ['title' => 'Empresas', 'icon' => 'building-2', 'route' => 'filament.admin.facturacion.resources.empresas.index'],
    ['title' => 'Conceptos', 'icon' => 'tag', 'route' => 'filament.admin.facturacion.resources.conceptos.index'],
    ['title' => 'Formas de Cobro', 'icon' => 'credit-card', 'route' => 'filament.admin.facturacion.resources.forma-cobros.index'],
    ['title' => 'Estadísticas', 'icon' => 'bar-chart-3', 'route' => 'novafactu.dashboard'],
    ['title' => 'VeriFactu', 'icon' => 'shield-check', 'route' => 'facturacion.facturas2'],
];

$quickItems = [
    ['title' => 'Nueva factura', 'icon' => 'plus', 'route' => 'facturacion.nuevafactura'],
    ['title' => 'Remesas', 'icon' => 'calendar', 'route' => 'facturacion.remesas'],
];
@endphp

<x-ui.sidebar
    collapsible="icon"
    class="border-r-0 bg-black text-neutral-50"
    style="--sidebar: #000000; --sidebar-foreground: #fafafa; --sidebar-accent: #262626; --sidebar-accent-foreground: #fafafa; --sidebar-border: #262626; --sidebar-ring: #525252; --sidebar-width: 16rem; --sidebar-width-icon: 3.5rem;"
>
    <x-ui.sidebar-header class="gap-3 border-b border-neutral-800/50 p-4">
        <div class="flex items-center justify-between">
            <x-ui.sidebar-menu class="flex-1">
                <x-ui.sidebar-menu-item>
                    <x-ui.sidebar-menu-button size="lg" href="{{ route('novafactu.dashboard') }}" class="hover:bg-neutral-900">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-neutral-900 text-neutral-50">
                            <x-lucide-file-text class="h-5 w-5" />
                        </div>
                        <div class="flex flex-col leading-none">
                            <span class="font-semibold text-sm">NovaFactu</span>
                            <span class="text-[11px] text-neutral-500">Nova Hub</span>
                        </div>
                    </x-ui.sidebar-menu-button>
                </x-ui.sidebar-menu-item>
            </x-ui.sidebar-menu>

            <x-ui.sidebar-trigger class="shrink-0 text-neutral-500 hover:bg-neutral-900 hover:text-neutral-50" />
        </div>


        <x-ui.sidebar-menu>
            <x-ui.sidebar-menu-item>
                <x-ui.sidebar-menu-button class="hover:bg-neutral-900">
                    <x-lucide-search class="h-[18px] w-[18px]" />
                    <span>Buscar</span>
                </x-ui.sidebar-menu-button>
            </x-ui.sidebar-menu-item>
        </x-ui.sidebar-menu>
    </x-ui.sidebar-header>

    <x-ui.sidebar-content class="gap-4 py-4">
        <x-ui.sidebar-group>
            <x-ui.sidebar-group-label>Acciones</x-ui.sidebar-group-label>
            <x-ui.sidebar-menu>
                @foreach ($quickItems as $item)
                    <x-ui.sidebar-menu-item>
                        @if ($item['route'])
                            <x-ui.sidebar-menu-button
                                :href="route($item['route'])"
                                :is-active="$item['route'] && $isActive($item['route'])"
                                class="hover:bg-neutral-900"
                            >
                                <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-[18px] w-[18px]" />
                                <span>{{ $item['title'] }}</span>
                            </x-ui.sidebar-menu-button>
                        @else
                            <x-ui.sidebar-menu-button class="hover:bg-neutral-900">
                                <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-[18px] w-[18px]" />
                                <span>{{ $item['title'] }}</span>
                            </x-ui.sidebar-menu-button>
                        @endif
                    </x-ui.sidebar-menu-item>
                @endforeach
            </x-ui.sidebar-menu>
        </x-ui.sidebar-group>

        <x-ui.sidebar-separator class="bg-neutral-800/60" />

        <x-ui.sidebar-group>
            <x-ui.sidebar-group-label>Módulos</x-ui.sidebar-group-label>
            <x-ui.sidebar-menu>
                @foreach ($navItems as $item)
                    <x-ui.sidebar-menu-item>
                        <x-ui.sidebar-menu-button
                            :href="route($item['route'])"
                            :is-active="$item['route'] && $isActive($item['route'])"
                            class="hover:bg-neutral-900"
                        >
                            <x-dynamic-component :component="'lucide-'.$item['icon']" class="h-[18px] w-[18px]" />
                            <span>{{ $item['title'] }}</span>
                        </x-ui.sidebar-menu-button>
                    </x-ui.sidebar-menu-item>
                @endforeach
            </x-ui.sidebar-menu>
        </x-ui.sidebar-group>
    </x-ui.sidebar-content>

    <x-ui.sidebar-rail class="hover:after:bg-neutral-700" />

    <x-ui.sidebar-footer class="border-t border-neutral-800/50 p-4">
        <x-ui.sidebar-menu>
            <x-ui.sidebar-menu-item>
                <x-ui.sidebar-menu-button
                    href="{{ route('facturacion.ajustes') }}"
                    :is-active="$currentRoute === 'facturacion.ajustes'"
                    class="hover:bg-neutral-900"
                >
                    <x-lucide-settings class="h-[18px] w-[18px]" />
                    <span>Ajustes</span>
                </x-ui.sidebar-menu-button>
            </x-ui.sidebar-menu-item>
            <x-ui.sidebar-menu-item>
                <x-ui.sidebar-menu-button class="hover:bg-neutral-900">
                    <x-lucide-user class="h-[18px] w-[18px]" />
                    <div class="flex flex-col leading-none">
                        <span>{{ auth()->user()?->first_name ?? 'Usuario' }}</span>
                        <span class="text-[11px] text-neutral-500">NovaFactu</span>
                    </div>
                </x-ui.sidebar-menu-button>
            </x-ui.sidebar-menu-item>
        </x-ui.sidebar-menu>
    </x-ui.sidebar-footer>
</x-ui.sidebar>
