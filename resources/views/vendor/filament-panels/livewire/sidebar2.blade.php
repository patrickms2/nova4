<div>
    @php
        $navigation = filament()->getNavigation();
        $isRtl = __('filament-panels::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
        $hasNavigation = filament()->hasNavigation();
        $hasTopbar = filament()->hasTopbar();

        // Groups marked as drilled via DrilldownSidebarPlugin get drill-down navigation
        $drilledGroupLabels = filament('drilldown-sidebar')?->getDrilledGroups() ?? [];
        $drilldownSubGroups = method_exists(filament('drilldown-sidebar'), 'getSubGroups')
            ? filament('drilldown-sidebar')->getSubGroups()
            : [
                'Tourist' => ['Tours', 'Taxi', 'Activities', 'Restaurants', 'Hotels', 'Locations','Transfer'],
                'Bookings' => ['Bookings'],
                'Nova' => ['Admin'],
                'Settings' => ['Activities', 'Bookings', 'Analytics'],
            ];
        $drilldownSubGroupItemLabels = [
            'Tourist' => [
                'Tours' => ['Tours', 'Tour Bookings','Bookings', 'Public requests', 'Tour Schedules', 'Tour Images', 'Tour Categories','Tour Translations'],
                'Taxi' => [ 'Taxi Bookings','Bookings', 'Public requests', 'External Bookings','Taxi Services', 'Driver Vehicle Assignments'],
                'Activities' => ['Activities'],
                'Restaurants' => ['Restaurants'],
                'Hotels' => ['Hotels'],
                'Locations' => ['Locations'],
                'Transfer' => ['Transfer'],
            ],
            'Bookings' => [
                'Bookings' => ['Tarifas transfers','Bookings', 'External Bookings', 'Package Bookings', 'Public requests', 'Tour Bookings', 'Taxi Bookings'],
            ],
            'Nova' => [
                'Cliente' => ['Clientes Nova','Tarifas transfers'],
                'Facturas' => ['Facturas','Clientes','Conceptos','Empresas'],
                'MCP' => ['MCP Servers', 'Business Hub','Dashboard MCP'],
                'API' => ['MCP Servers', 'Business Hub','Dashboard MCP'],

                'IA' => ['IA', 'Conocimiento IA', 'Reglas de Intent'],
                'Listing' => ['Listing Config', 'Cross-selling'],
                'Integraciones' => ['External Sources', 'External Catalog Items', 'External Orders', 'External Payments', 'Integraciones externas', 'Catálogo externo', 'Pedidos externos', 'Reservas externas', 'Logs de sync'],
            ],
        ];

        $drilldownGroups = collect($navigation)->filter(
            fn (\Filament\Navigation\NavigationGroup $group) =>
                filled($group->getLabel()) && count($group->getItems()) > 0
                && in_array($group->getLabel(), $drilledGroupLabels)
        );

        // Auto-drill to the active group on page load
        $activeNavGroup = $drilldownGroups
            ->first(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isActive() && filled($group->getLabel()));
        $activeGroupLabel = $activeNavGroup?->getLabel();
        $activeSubGroupLabel = null;

        if ($activeNavGroup && filled($activeGroupLabel)) {
            $activeItems = collect($activeNavGroup->getItems())
                ->filter(fn ($item): bool => $item->isActive() || $item->isChildItemsActive())
                ->map(fn ($item): string => $item->getLabel());

            foreach (($drilldownSubGroupItemLabels[$activeGroupLabel] ?? []) as $subGroup => $itemLabels) {
                if ($activeItems->intersect($itemLabels)->isNotEmpty()) {
                    $activeSubGroupLabel = $subGroup;

                    break;
                }
            }
        }
    @endphp

    {{-- format-ignore-start --}}
    <aside
        x-data="{}"
        @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
            x-cloak
        @else
            x-cloak="-lg"
        @endif
        x-bind:class="{ 'fi-sidebar-open': $store.sidebar.isOpen }"
        class="fi-sidebar fi-main-sidebar"
    >
        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_START) }}

        <div class="fi-sidebar-header-ctn">
            <header class="fi-sidebar-header">
                @if ((! $hasTopbar) && $isSidebarCollapsibleOnDesktop)
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? 'heroicon-o-chevron-left' : 'heroicon-o-chevron-right'"
                        icon-size="lg"
                        :label="__('filament-panels::layout.actions.sidebar.expand.label')"
                        x-cloak
                        x-data="{}"
                        x-on:click="$store.sidebar.open()"
                        x-show="! $store.sidebar.isOpen"
                        class="fi-sidebar-open-collapse-sidebar-btn"
                    />
                @endif

                @if ((! $hasTopbar) && ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop))
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? 'heroicon-o-chevron-right' : 'heroicon-o-chevron-left'"
                        icon-size="lg"
                        :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                        x-cloak
                        x-data="{}"
                        x-on:click="$store.sidebar.close()"
                        x-show="$store.sidebar.isOpen"
                        class="fi-sidebar-close-collapse-sidebar-btn"
                    />
                @endif

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_BEFORE) }}

                <div
                    @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                        x-show="$store.sidebar.isOpen"
                    @endif
                    class="fi-sidebar-header-logo-ctn"
                >
                    @if ($homeUrl = filament()->getHomeUrl())
                        <a {{ \Filament\Support\generate_href_html($homeUrl) }}>
                            <x-filament-panels::logo />
                        </a>
                    @else
                        <x-filament-panels::logo />
                    @endif
                </div>

                {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_LOGO_AFTER) }}
            </header>
        </div>

        @if (filament()->hasTenancy() && filament()->hasTenantMenu())
            <x-filament-panels::tenant-menu />
        @endif

        @if (filament()->isGlobalSearchEnabled() && filament()->getGlobalSearchPosition() === \Filament\Enums\GlobalSearchPosition::Sidebar)
            <div
                @if ($isSidebarCollapsibleOnDesktop || $isSidebarFullyCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                @endif
            >
                @livewire(Filament\Livewire\GlobalSearch::class)
            </div>
        @endif

        <nav class="fi-sidebar-nav">
            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_START) }}

            {{-- ============================================================
                 Collapsed sidebar: original group dropdowns (icon-only mode)
                 ============================================================ --}}
            @if ($isSidebarCollapsibleOnDesktop)
                <ul
                    x-show="! $store.sidebar.isOpen"
                    class="fi-sidebar-nav-groups"
                >
                    @foreach ($navigation as $group)
                        <x-filament-panels::sidebar.group
                            :active="$group->isActive()"
                            :collapsible="$group->isCollapsible()"
                            :icon="$group->getIcon()"
                            :items="$group->getItems()"
                            :label="$group->getLabel()"
                            :attributes="\Filament\Support\prepare_inherited_attributes($group->getExtraSidebarAttributeBag())"
                        />
                    @endforeach
                </ul>
            @endif

            {{-- ============================================================
                 Expanded sidebar: standard groups + optional drill-down
                 ============================================================ --}}
            <div
                @if ($isSidebarCollapsibleOnDesktop)
                    x-show="$store.sidebar.isOpen"
                    x-transition:enter="fi-transition-enter"
                    x-transition:enter-start="fi-transition-enter-start"
                    x-transition:enter-end="fi-transition-enter-end"
                @endif
                x-data="{
                    view: @js($activeSubGroupLabel ? 'subdetail' : 'main'),
                    activeGroup: @js($activeGroupLabel),
                    activeSubGroup: @js($activeSubGroupLabel),
                    goToGroup(label) {
                        this.activeGroup = label;
                        this.activeSubGroup = null;
                        this.view = 'detail';
                    },
                    goToSubGroup(label) {
                        this.activeSubGroup = label;
                        this.view = 'subdetail';
                    },
                    goBack() {
                        if (this.view === 'subdetail') {
                            this.view = 'main';
                            this.activeSubGroup = null;

                            return;
                        }

                        this.view = 'main';
                        this.activeGroup = null;
                    }
                }"
                class="fi-dd-nav-wrapper"
            >
                {{-- ========================
                     MAIN VIEW: group list
                     ======================== --}}
                <div
                    x-show="view === 'main'"
                    x-transition:enter="fi-dd-main-enter"
                    x-transition:enter-start="fi-dd-main-enter-start"
                    x-transition:enter-end="fi-dd-main-enter-end"
                    x-transition:leave="fi-dd-main-leave"
                    x-transition:leave-start="fi-dd-main-leave-start"
                    x-transition:leave-end="fi-dd-main-leave-end"
                >
                    {{-- Ungrouped items (Dashboard, etc.) --}}
                    <ul class="fi-sidebar-group-items">
                        @foreach ($navigation as $group)
                            @if (blank($group->getLabel()) && count($group->getItems()) > 0)
                                @foreach ($group->getItems() as $item)
                                    <x-filament-panels::sidebar.item
                                        :active="$item->isActive()"
                                        :active-child-items="$item->isChildItemsActive()"
                                        :active-icon="$item->getActiveIcon()"
                                        :badge="$item->getBadge()"
                                        :badge-color="$item->getBadgeColor()"
                                        :badge-tooltip="$item->getBadgeTooltip()"
                                        :child-items="$item->getChildItems()"
                                        :first="$loop->first"
                                        :grouped="false"
                                        :icon="$item->getIcon()"
                                        :last="$loop->last"
                                        :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                                        :sidebar-collapsible="false"
                                        :url="$item->getUrl()"
                                    >
                                        {{ $item->getLabel() }}

                                        @if ($item->getIcon() instanceof \Illuminate\Contracts\Support\Htmlable)
                                            <x-slot name="icon">
                                                {{ $item->getIcon() }}
                                            </x-slot>
                                        @endif
                                    </x-filament-panels::sidebar.item>
                                @endforeach
                            @endif
                        @endforeach
                    </ul>

                    {{-- Labeled groups: rendered in original order, each as drilldown or standard --}}
                    <ul class="fi-sidebar-nav-groups fi-dd-groups-list">
                        @foreach ($navigation as $group)
                            @if (filled($group->getLabel()) && count($group->getItems()) > 0)
                                @if (in_array($group->getLabel(), $drilledGroupLabels))
                                    @php
                                        $configuredSubGroups = collect($drilldownSubGroups[$group->getLabel()] ?? []);
                                    @endphp
                                    <li class="fi-sidebar-group fi-dd-group">
                                        <p class="fi-dd-group-label">
                                            {{ $group->getLabel() }}
                                        </p>

                                        <ul class="fi-sidebar-group-items">
                                            @foreach ($configuredSubGroups as $subGroup)
                                                @continue(blank($drilldownSubGroupItemLabels[$group->getLabel()][$subGroup] ?? []))
                                                @php
                                                    $subGroupIsActive = $activeGroupLabel === $group->getLabel() && $activeSubGroupLabel === $subGroup;
                                                @endphp

                                                <li class="fi-sidebar-group fi-dd-group">
                                                    <button
                                                        type="button"
                                                        x-on:click="activeGroup = @js($group->getLabel()); goToSubGroup(@js($subGroup))"
                                                        @class([
                                                            'fi-dd-group-btn',
                                                            'fi-active' => $subGroupIsActive,
                                                        ])
                                                    >
                                                        <span class="fi-dd-btn-label">
                                                            {{ $subGroup }}
                                                        </span>
                                                        <x-filament::icon
                                                            :icon="$isRtl ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right'"
                                                            class="fi-dd-btn-chevron"
                                                        />
                                                    </button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </li>
                                @else
                                    {{-- Standard collapsible group --}}
                                    @php
                                        $hasItemIcons = collect($group->getItems())->contains(fn ($item) => filled($item->getIcon()));
                                    @endphp
                                    <x-filament-panels::sidebar.group
                                        :active="$group->isActive()"
                                        :collapsible="$group->isCollapsible()"
                                        :icon="$hasItemIcons ? null : $group->getIcon()"
                                        :items="$group->getItems()"
                                        :label="$group->getLabel()"
                                        :attributes="\Filament\Support\prepare_inherited_attributes($group->getExtraSidebarAttributeBag())"
                                    />
                                @endif
                            @endif
                        @endforeach
                    </ul>
                </div>

                <div
                    x-show="view === 'subdetail'"
                    x-transition:enter="fi-dd-detail-enter"
                    x-transition:enter-start="fi-dd-detail-enter-start"
                    x-transition:enter-end="fi-dd-detail-enter-end"
                    x-transition:leave="fi-dd-detail-leave"
                    x-transition:leave-start="fi-dd-detail-leave-start"
                    x-transition:leave-end="fi-dd-detail-leave-end"
                >
                    <button
                        type="button"
                        x-on:click="goBack()"
                        class="fi-dd-back-btn"
                    >
                        <x-filament::icon
                            :icon="$isRtl ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left'"
                        />
                        <span>{{ __('Back') }}</span>
                    </button>

                    @foreach ($navigation as $group)
                        @if (filled($group->getLabel())
                            && count($group->getItems()) > 0
                            && in_array($group->getLabel(), $drilledGroupLabels))
                            @php
                                $items = collect($group->getItems());
                                $groupIcon = $group->getIcon();
                            @endphp

                            @foreach (collect($drilldownSubGroups[$group->getLabel()] ?? []) as $subGroup)
                                @php
                                    $subGroupItems = collect($drilldownSubGroupItemLabels[$group->getLabel()][$subGroup] ?? [$subGroup])
                                        ->map(fn (string $itemLabel): mixed => $items->first(fn ($item): bool => $item->getLabel() === $itemLabel))
                                        ->filter()
                                        ->values();
                                @endphp
                                @continue($subGroupItems->isEmpty())

                                <div
                                    x-show="activeGroup === @js($group->getLabel()) && activeSubGroup === @js($subGroup)"
                                    x-cloak
                                    class="fi-dd-detail-panel"
                                >
                                    <div class="fi-dd-detail-header">
                                        <h3 class="fi-dd-detail-title">
                                            {{ $subGroup }}
                                        </h3>
                                    </div>

                                    <hr class="fi-dd-detail-divider" />

                                    <ul class="fi-dd-detail-items">
                                        @foreach ($subGroupItems as $item)
                                            @php
                                                $itemIcon = $item->getIcon();
                                                $itemActiveIcon = $item->getActiveIcon();
                                                if ($groupIcon) {
                                                    $itemIcon = null;
                                                    $itemActiveIcon = null;
                                                }
                                            @endphp
                                            <x-filament-panels::sidebar.item
                                                :active="$item->isActive()"
                                                :active-child-items="$item->isChildItemsActive()"
                                                :active-icon="$itemActiveIcon"
                                                :badge="$item->getBadge()"
                                                :badge-color="$item->getBadgeColor()"
                                                :badge-tooltip="$item->getBadgeTooltip()"
                                                :child-items="$item->getChildItems()"
                                                :first="$loop->first"
                                                :grouped="true"
                                                :icon="$itemIcon"
                                                :last="$loop->last"
                                                :should-open-url-in-new-tab="$item->shouldOpenUrlInNewTab()"
                                                :sidebar-collapsible="false"
                                                :url="$item->getUrl()"
                                            >
                                                {{ $item->getLabel() }}

                                                @if ($itemIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                                    <x-slot name="icon">
                                                        {{ $itemIcon }}
                                                    </x-slot>
                                                @endif

                                                @if ($itemActiveIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                                    <x-slot name="activeIcon">
                                                        {{ $itemActiveIcon }}
                                                    </x-slot>
                                                @endif
                                            </x-filament-panels::sidebar.item>
                                        @endforeach
                                    </ul>
                                </div>
                            @endforeach
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Initialize collapsedGroups localStorage for standard collapsible groups --}}
            <script>
                var collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups'))

                if (collapsedGroups === null || collapsedGroups === 'null') {
                    localStorage.setItem(
                        'collapsedGroups',
                        JSON.stringify(@js(
                            collect($navigation)
                                ->filter(fn (\Filament\Navigation\NavigationGroup $group): bool => $group->isCollapsed())
                                ->map(fn (\Filament\Navigation\NavigationGroup $group): string => $group->getLabel())
                                ->values()
                                ->all()
                        )),
                    )
                }

                collapsedGroups = JSON.parse(localStorage.getItem('collapsedGroups'))

                document
                    .querySelectorAll('.fi-sidebar-group')
                    .forEach((group) => {
                        if (
                            !collapsedGroups.includes(group.dataset.groupLabel)
                        ) {
                            return
                        }

                        var items = group.querySelector('.fi-sidebar-group-items')
                        if (items) items.style.display = 'none'
                        group.classList.add('fi-collapsed')
                    })
            </script>

            {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_NAV_END) }}
        </nav>

        @php
            $isAuthenticated = filament()->auth()->check();
            $hasDatabaseNotificationsInSidebar = filament()->hasDatabaseNotifications() && filament()->getDatabaseNotificationsPosition() === \Filament\Enums\DatabaseNotificationsPosition::Sidebar;
            $hasUserMenuInSidebar = filament()->hasUserMenu() && filament()->getUserMenuPosition() === \Filament\Enums\UserMenuPosition::Sidebar;
            $shouldRenderFooter = $isAuthenticated && ($hasDatabaseNotificationsInSidebar || $hasUserMenuInSidebar);
        @endphp

        @if ($shouldRenderFooter)
            <div class="fi-sidebar-footer">
                @if ($hasDatabaseNotificationsInSidebar)
                    @livewire(filament()->getDatabaseNotificationsLivewireComponent(), [
                        'lazy' => filament()->hasLazyLoadedDatabaseNotifications(),
                    ])
                @endif

                @if ($hasUserMenuInSidebar)
                    <x-filament-panels::user-menu />
                @endif
            </div>
        @endif

        {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::SIDEBAR_FOOTER) }}
    </aside>
    {{-- format-ignore-end --}}

    <x-filament-actions::modals />
</div>
