<div>
    @php
        $navigation = filament()->getNavigation();
        $isRtl = __('filament-panels::layout.direction') === 'rtl';
        $isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
        $isSidebarFullyCollapsibleOnDesktop = filament()->isSidebarFullyCollapsibleOnDesktop();
        $hasNavigation = filament()->hasNavigation();
        $hasTopbar = filament()->hasTopbar();

        // Level 1: which navigation group labels get drill-down treatment
        //$drilledGroupLabels = filament('drilldown-sidebar')?->getDrilledGroups() ?? [];

        // Level 2: map of [parentGroupLabel => [subGroupLabel, ...]]
        // subGroupLabel matches $navigationGroup on resources/pages
        //$drilldownSubGroups = filament('drilldown-sidebar')?->getSubGroups() ?? [];

        // Build a lookup: subGroupLabel => parentGroupLabel (for active detection)
        $subGroupParentMap = [];
        foreach ($drilldownSubGroups as $parent => $children) {
            foreach ($children as $child) {
                $subGroupParentMap[$child] = $parent;
            }
        }

        // Determine active level-1 group and level-2 sub-group from current navigation
        $activeGroupLabel = null;
        $activeSubGroupLabel = null;
        foreach ($navigation as $group) {
            if (blank($group->getLabel()) || ! $group->isActive()) {
                continue;
            }
            $label = $group->getLabel();
            if (in_array($label, $drilledGroupLabels)) {
                $activeGroupLabel = $label;
                break;
            }
            if (isset($subGroupParentMap[$label])) {
                $activeGroupLabel = $subGroupParentMap[$label];
                $activeSubGroupLabel = $label;
                break;
            }
        }

        $initialView = $activeSubGroupLabel ? 'subdetail' : 'main';
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
            <header
                class="fi-sidebar-header"
            >
                @if ((! $hasTopbar) && $isSidebarCollapsibleOnDesktop)
                    <x-filament::icon-button
                        color="gray"
                        :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronLeft : \Filament\Support\Icons\Heroicon::OutlinedChevronRight"
                        {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_EXPAND_BUTTON` for RTL. --}}
                        :icon-alias="
                            $isRtl
                            ? [
                                \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON_RTL,
                                \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON,
                            ]
                            : \Filament\View\PanelsIconAlias::SIDEBAR_EXPAND_BUTTON
                        "
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
                        :icon="$isRtl ? \Filament\Support\Icons\Heroicon::OutlinedChevronRight : \Filament\Support\Icons\Heroicon::OutlinedChevronLeft"
                        {{-- @deprecated Use `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL` instead of `PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON` for RTL. --}}
                        :icon-alias="
                            $isRtl
                            ? [
                                \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON_RTL,
                                \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON,
                            ]
                            : \Filament\View\PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON
                        "
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
                    view: @js($initialView),
                    activeGroup: @js($activeGroupLabel),
                    activeSubGroup: @js($activeSubGroupLabel),
                    subGroupParentMap: @js($subGroupParentMap),
                    goToSubGroup(subLabel) {
                        this.activeGroup = this.subGroupParentMap[subLabel] ?? null;
                        this.activeSubGroup = subLabel;
                        this.view = 'subdetail';
                    },
                    goBack() {
                        this.activeSubGroup = null;
                        this.activeGroup = null;
                        this.view = 'main';
                    }
                }"
                class="fi-dd-nav-wrapper"
            >
                {{-- ========================
                     MAIN VIEW: Level 1 list
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
                                            <x-slot name="icon">{{ $item->getIcon() }}</x-slot>
                                        @endif
                                    </x-filament-panels::sidebar.item>
                                @endforeach
                            @endif
                        @endforeach
                    </ul>

                    {{-- Labeled groups --}}
                    <ul class="fi-sidebar-nav-groups fi-dd-groups-list">

                        {{-- Level-1 drilled groups: collapsible label + sub-group drilldown buttons inside --}}
                        @foreach ($drilledGroupLabels as $drilledLabel)
                            @php
                                $subGroupsForParent = $drilldownSubGroups[$drilledLabel] ?? [];
                                $parentIsActive = isset($subGroupParentMap) && collect($subGroupsForParent)->contains(fn ($sg) => collect($navigation)->contains(fn ($g) => $g->getLabel() === $sg && $g->isActive()));
                            @endphp
                            @if (! empty($subGroupsForParent))
                                <li
                                    class="fi-sidebar-group fi-collapsible fi-dd-parent-group"
                                    x-data="{ label: @js($drilledLabel) }"
                                    data-group-label="{{ $drilledLabel }}"
                                >
                                    <div
                                        x-on:click="$store.sidebar.toggleCollapsedGroup(@js($drilledLabel))"
                                        x-show="$store.sidebar.isOpen"
                                        x-transition:enter="fi-transition-enter"
                                        x-transition:enter-start="fi-transition-enter-start"
                                        x-transition:enter-end="fi-transition-enter-end"
                                        class="fi-sidebar-group-btn"
                                    >
                                        <span class="fi-sidebar-group-label">{{ $drilledLabel }}</span>
                                        <x-filament::icon-button
                                            color="gray"
                                            icon="heroicon-m-chevron-up"
                                            icon-size="sm"
                                            :label="__('filament-panels::layout.actions.sidebar.collapse.label')"
                                            tag="button"
                                            type="button"
                                            wire:loading.attr="disabled"
                                            x-bind:aria-expanded="! $store.sidebar.groupIsCollapsed(@js($drilledLabel))"
                                            x-on:click.stop="$store.sidebar.toggleCollapsedGroup(@js($drilledLabel))"
                                            class="fi-sidebar-group-collapse-btn"
                                        />
                                    </div>

                                    <ul
                                        class="fi-sidebar-group-items"
                                        x-show="! $store.sidebar.groupIsCollapsed(@js($drilledLabel))"
                                        x-transition:enter="fi-transition-enter"
                                        x-transition:enter-start="fi-transition-enter-start"
                                        x-transition:enter-end="fi-transition-enter-end"
                                    >
                                        @foreach ($subGroupsForParent as $subGroupLabel)
                                            @php
                                                $subNavGroup = collect($navigation)->first(fn ($g) => $g->getLabel() === $subGroupLabel);
                                                $subIsActive = $subNavGroup?->isActive() ?? false;
                                                $subIcon = $subNavGroup?->getIcon() ?? collect($subNavGroup?->getItems() ?? [])->first()?->getIcon();
                                            @endphp
                                            @if ($subNavGroup && count($subNavGroup->getItems()) > 0)
                                                <li class="fi-sidebar-item fi-sidebar-item-has-url">
                                                    <button
                                                        type="button"
                                                        x-on:click="goToSubGroup(@js($subGroupLabel))"
                                                        @class(['fi-dd-group-btn', 'fi-active' => $subIsActive])
                                                    >
                                                    <div class="fi-sidebar-item-grouped-border">
                <!--[if BLOCK]><![endif]--><!--[if ENDBLOCK]><![endif]-->
                <!--[if BLOCK]><![endif]-->                    <div class="fi-sidebar-item-grouped-border-part-not-last"></div>
                <!--[if ENDBLOCK]><![endif]-->
                <div class="fi-sidebar-item-grouped-border-part"></div>
            </div>
                                                        @if ($subIcon)
                                                            <x-filament::icon :icon="$subIcon" class="fi-dd-btn-icon" />
                                                        @endif
                                                        <span class="fi-dd-btn-label">{{ $subGroupLabel }}</span>
                                                        <x-filament::icon
                                                            :icon="$isRtl ? 'heroicon-m-chevron-left' : 'heroicon-m-chevron-right'"
                                                            class="fi-dd-btn-chevron"
                                                        />
                                                    </button>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        @endforeach

                        {{-- Standard groups: not drilled, not a sub-group --}}
                        @foreach ($navigation as $group)
                            @if (filled($group->getLabel()) && count($group->getItems()) > 0 && ! isset($subGroupParentMap[$group->getLabel()]) && ! in_array($group->getLabel(), $drilledGroupLabels))
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
                        @endforeach

                    </ul>
                </div>

                {{-- ===================================
                     SUBDETAIL VIEW: Level 3 page items
                     =================================== --}}
                <div
                    x-show="view === 'subdetail'"
                    x-transition:enter="fi-dd-detail-enter"
                    x-transition:enter-start="fi-dd-detail-enter-start"
                    x-transition:enter-end="fi-dd-detail-enter-end"
                    x-transition:leave="fi-dd-detail-leave"
                    x-transition:leave-start="fi-dd-detail-leave-start"
                    x-transition:leave-end="fi-dd-detail-leave-end"
                >
                    <button type="button" x-on:click="goBack()" class="fi-dd-back-btn">
                        <x-filament::icon :icon="$isRtl ? 'heroicon-m-chevron-right' : 'heroicon-m-chevron-left'" />
                        <span>{{ __('Back') }}</span>
                    </button>

                    @foreach ($navigation as $group)
                        @if (filled($group->getLabel()) && count($group->getItems()) > 0 && isset($subGroupParentMap[$group->getLabel()]))
                            @php
                                $groupIcon = $group->getIcon();
                            @endphp
                            <div
                                x-show="activeSubGroup === @js($group->getLabel())"
                                x-cloak
                                class="fi-dd-detail-panel"
                            >
                                <div class="fi-dd-detail-header">
                                    @if ($groupIcon)
                                        <x-filament::icon :icon="$groupIcon" class="fi-dd-detail-icon" />
                                    @endif
                                    <h3 class="fi-dd-detail-title">{{ $group->getLabel() }}</h3>
                                </div>
                                <hr class="fi-dd-detail-divider" />
                                <ul class="fi-dd-detail-items">
                                    @foreach ($group->getItems() as $item)
                                        @php
                                            $itemIcon = $groupIcon ? null : $item->getIcon();
                                            $itemActiveIcon = $groupIcon ? null : $item->getActiveIcon();
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
                                                <x-slot name="icon">{{ $itemIcon }}</x-slot>
                                            @endif
                                            @if ($itemActiveIcon instanceof \Illuminate\Contracts\Support\Htmlable)
                                                <x-slot name="activeIcon">{{ $itemActiveIcon }}</x-slot>
                                            @endif
                                        </x-filament-panels::sidebar.item>
                                    @endforeach
                                </ul>
                            </div>
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
