@php
    use Archilex\AdvancedTables\Support\Config;
    use Archilex\AdvancedTables\View\AdvancedTablesIconAlias;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Facades\FilamentAsset;
    use Filament\Support\Icons\Heroicon;
    use Filament\Tables\View\TablesIconAlias;
    use Filament\Support\View\Components\InputComponent\WrapperComponent\IconComponent;
    use Illuminate\View\ComponentAttributeBag;
    use function Filament\Support\generate_icon_html;
    use function Filament\Support\generate_loading_indicator_html;
@endphp

@props([
    'debounce' => '500ms',
    'onBlur' => false,
    'placeholder' => __('filament-tables::table.fields.search.placeholder'),
    'wireModel' => 'tableSearch',
])

@php
    $advancedSearchIsEnabled = method_exists($this, 'advancedSearchIsEnabled') && $this->advancedSearchIsEnabled() && ! $this->getTable()->hasSearchUsingCallback();
    $isColumnSearch = str_starts_with($wireModel, 'tableColumnSearches.');

    $wireModelAttribute = $onBlur ? 'wire:model.blur' : "wire:model.live.debounce.{$debounce}";
@endphp

@if (! $advancedSearchIsEnabled || $isColumnSearch)
    <div
        x-id="['input']"
        {{ $attributes->class(['fi-ta-search-field']) }}
    >
        <label x-bind:for="$id('input')" class="fi-sr-only">
            {{ __('filament-tables::table.fields.search.label') }}
        </label>

        <x-filament::input.wrapper
            inline-prefix
            :prefix-icon="\Filament\Support\Icons\Heroicon::MagnifyingGlass"
            :prefix-icon-alias="\Filament\Tables\View\TablesIconAlias::SEARCH_FIELD"
            :wire:target="$wireModel"
        >
            <x-filament::input
                :attributes="
                    (new ComponentAttributeBag)->merge([
                        'autocomplete' => 'off',
                        'inlinePrefix' => true,
                        'maxlength' => 1000,
                        'placeholder' => $placeholder,
                        'type' => 'search',
                        'wire:key' => $this->getId() . '.table.' . $wireModel . '.field.input',
                        $wireModelAttribute => $wireModel,
                        'x-bind:id' => '$id(\'input\')',
                        'x-on:keyup' => 'if ($event.key === \'Enter\') { $wire.$refresh() }',
                    ], escape: false)
                "
            />
        </x-filament::input.wrapper>
    </div>
@else
    @php
        $loadingIndicatorTarget = 'advancedSearch';
        $loadingDelay = config('filament.livewire_loading_delay', 'default');
        $searchableColumnOptions = $this->getSearchableColumnOptions();
        $extraSearchableColumnOptions = $this->getExtraSearchableColumnOptions();
        $searchConstraintOptions = $this->getSearchConstraintDropdownOptions();
        $searchColumnsEnabled = Config::advancedSearchColumnsAreEnabled();
        $searchGroupingEnabled = Config::advancedSearchGroupingIsEnabled();
        $searchBooleanEnabled = Config::advancedSearchBooleanSyntaxIsEnabled();
        $searchDropdownOpensOnFocus = method_exists($this, 'advancedSearchDropdownOpensOnFocus') && $this->advancedSearchDropdownOpensOnFocus();
        $searchDropdownColumnsFirst = method_exists($this, 'advancedSearchDropdownShowsColumnsFirst') && $this->advancedSearchDropdownShowsColumnsFirst();
        $keybindings = Config::getAdvancedSearchKeybindings();
    @endphp

    <div
        wire:ignore
        x-id="['input']"
        x-load
        x-load-src="{{ FilamentAsset::getAlpineComponentSrc('advanced-search', 'archilex/advanced-tables') }}"
        x-data="advancedSearchField({
            debounce: {{ intval(str_replace('ms', '', $debounce)) }},
            opensOnFocus: @js($searchDropdownOpensOnFocus),
            columnsFirst: @js($searchDropdownColumnsFirst),
            columnsEnabled: @js($searchColumnsEnabled),
            groupingEnabled: @js($searchGroupingEnabled),
            booleanEnabled: @js($searchBooleanEnabled),
            columns: @js($searchColumnsEnabled ? $searchableColumnOptions : []),
            extraColumns: @js($searchColumnsEnabled ? $extraSearchableColumnOptions : []),
            allColumnKeys: @js($this->getAllSearchableColumnKeys()),
            constraints: @js($searchConstraintOptions),
            sectionHeaders: @js([
                'constraints' => __('advanced-tables::advanced-tables.advanced_search.dropdown.constraints_header'),
                'columns' => __('advanced-tables::advanced-tables.advanced_search.dropdown.columns_header'),
                'database' => __('advanced-tables::advanced-tables.advanced_search.dropdown.database_header'),
                'boolean' => __('advanced-tables::advanced-tables.advanced_search.dropdown.boolean_header'),
            ]),
            placeholder: @js($placeholder),
            maxGroups: {{ $searchGroupingEnabled ? Config::getAdvancedSearchMaxGroups() : 1 }},
            translations: @js([
                'and' => __('advanced-tables::advanced-tables.advanced_search.boolean.and'),
                'or' => __('advanced-tables::advanced-tables.advanced_search.boolean.or'),
            ]),
        })"
        @if ($keybindings)
            x-mousetrap.global.{{ collect($keybindings)->map(fn (string $keybinding): string => str_replace('+', '-', $keybinding))->implode('.') }}="focusAndOpenDropdown()"
        @endif
        {{ $attributes->class(['fi-ta-search-field advanced-tables-search-field w-full sm:w-auto']) }}
    >
        <label x-bind:for="$id('input')" class="fi-sr-only">
            {{ __('filament-tables::table.fields.search.label') }}
        </label>

        <div class="fi-input-wrp">
            <div class="fi-input-wrp-prefix fi-input-wrp-prefix-has-content fi-inline">
                {{
                    generate_icon_html(
                        Heroicon::MagnifyingGlass,
                        TablesIconAlias::SEARCH_FIELD,
                        (new ComponentAttributeBag)
                            ->merge([
                                'wire:loading.remove.delay.' . $loadingDelay => true,
                                'wire:target' => $loadingIndicatorTarget,
                            ], escape: false)
                            ->color(IconComponent::class, 'gray')
                    )
                }}

                {{
                    generate_loading_indicator_html(
                        (new ComponentAttributeBag([
                            'wire:loading.delay.' . $loadingDelay => true,
                            'wire:target' => $loadingIndicatorTarget,
                        ]))->color(IconComponent::class, 'gray')
                    )
                }}
            </div>

            <div class="fi-input-wrp-content-ctn">
                <div class="advanced-tables-search-container flex items-center gap-2 pe-2 min-h-[2.25rem]">
                    <div
                        x-ref="tagsContainer"
                        class="advanced-tables-search-badges flex items-center gap-1 overflow-x-auto min-w-0"
                    >
                        {{-- Boolean badge for active group (if non-first) --}}
                        <template x-if="searchGroups[activeGroupIndex]?.operator">
                            <x-filament::badge
                                size="sm"
                                color="warning"
                                class="flex-shrink-0 uppercase cursor-pointer"
                                x-on:click.stop="toggleBoolean()"
                            >
                                <span x-text="searchGroups[activeGroupIndex].operator === 'and' ? '{{ __('advanced-tables::advanced-tables.advanced_search.boolean.and') }}' : '{{ __('advanced-tables::advanced-tables.advanced_search.boolean.or') }}'"></span>
                            </x-filament::badge>
                        </template>

                        {{-- Column badges for active group --}}
                        <template x-for="(column, colIndex) in activeColumns" :key="column.name">
                            <x-filament::badge
                                size="sm"
                                color="primary"
                                class="flex-shrink-0"
                            >
                                <span x-text="column.label"></span>

                                <x-slot
                                    name="deleteButton"
                                    x-on:click.stop="removeColumn(colIndex)"
                                ></x-slot>
                            </x-filament::badge>
                        </template>

                        {{-- Constraint badge for active group --}}
                        <template x-if="activeConstraint">
                            <x-filament::badge
                                size="sm"
                                color="gray"
                                class="flex-shrink-0"
                            >
                                <span x-text="activeConstraint.label"></span>

                                <x-slot
                                    name="deleteButton"
                                    x-on:click.stop="removeConstraint()"
                                ></x-slot>
                            </x-filament::badge>
                        </template>
                    </div>

                    <div class="relative flex-1 min-w-[108px] sm:min-w-[164px]">
                        <input
                            x-ref="searchInput"
                            x-model="query"
                            x-on:input="handleInput()"
                            x-on:keydown="handleInputKeydown($event)"
                            x-on:focus="handleInputFocus()"
                            type="text"
                            role="combobox"
                            aria-autocomplete="list"
                            x-bind:aria-expanded="isDropdownOpen"
                            x-bind:aria-controls="$id('input') + '-listbox'"
                            x-bind:aria-activedescendant="highlightedItemIndex >= 0 ? $id('input') + '-option-' + highlightedItemIndex : null"
                            autocomplete="off"
                            maxlength="1000"
                            x-bind:placeholder="(activeColumns.length > 0 || activeConstraint || searchGroups[activeGroupIndex]?.operator) ? '' : placeholder"
                            class="fi-input w-full border-0 bg-transparent outline-none py-1.5 ps-0 pe-0"
                            x-bind:id="$id('input')"
                        />

                        <div
                            x-show="isDropdownOpen"
                            x-on:click.away="handleClickAway($event)"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="fi-dropdown-panel absolute top-full left-[-16px] z-10 rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
                            style="max-width: 256px !important;"
                        >
                            <ul x-ref="menuList" role="listbox" x-bind:id="$id('input') + '-listbox'" class="fi-dropdown-list p-1 max-h-96 overflow-y-auto">
                                <template x-for="(item, i) in dropdownItems" :key="i">
                                    <li
                                        x-bind:role="item.type === 'divider' || item.type === 'header' ? 'separator' : 'option'"
                                        x-bind:id="item.type !== 'divider' && item.type !== 'header' ? $id('input') + '-option-' + i : null"
                                    >
                                        <div
                                            x-show="item.type === 'header'"
                                            class="px-3 pt-2 font-medium text-gray-400 dark:text-gray-500"
                                            x-text="item.label"
                                        ></div>

                                        <hr
                                            x-show="item.type === 'divider'"
                                            class="my-1 -mx-1 border-gray-200 dark:border-white/10"
                                        />

                                        <div
                                            x-show="item.type === 'constraint'"
                                            x-on:click="selectConstraint(item)"
                                            x-bind:class="i === highlightedItemIndex && item.type === 'constraint' ? 'fi-active bg-gray-50 dark:bg-white/5' : ''"
                                            class="fi-dropdown-list-item flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 cursor-default"
                                        >
                                            {{
                                                generate_icon_html(
                                                    icon: Heroicon::Funnel,
                                                    alias: AdvancedTablesIconAlias::ADVANCED_SEARCH_CONSTRAINT,
                                                    attributes: (new ComponentAttributeBag)
                                                        ->color(IconComponent::class, 'gray'),
                                                    size: IconSize::Small,
                                                )
                                            }}
                                            <span x-text="item.label" class="truncate"></span>
                                            <span x-text="item.syntax" class="ms-auto font-mono text-xs text-info-600 dark:text-info-400 shrink-0"></span>
                                        </div>

                                        <div
                                            x-show="item.type === 'column'"
                                            x-on:click="selectColumn(item)"
                                            x-bind:class="i === highlightedItemIndex && item.type === 'column' ? 'fi-active bg-gray-50 dark:bg-white/5' : ''"
                                            class="fi-dropdown-list-item flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 cursor-default"
                                        >
                                            {{
                                                generate_icon_html(
                                                    icon: Heroicon::TableCells,
                                                    alias: AdvancedTablesIconAlias::ADVANCED_SEARCH_COLUMN,
                                                    attributes: (new ComponentAttributeBag)
                                                        ->color(IconComponent::class, 'gray'),
                                                    size: IconSize::Small,
                                                )
                                            }}
                                            <span x-text="item.label" class="truncate"></span>
                                        </div>

                                        <div
                                            x-show="item.type === 'extraColumn'"
                                            x-on:click="selectColumn(item)"
                                            x-bind:class="i === highlightedItemIndex && item.type === 'extraColumn' ? 'fi-active bg-gray-50 dark:bg-white/5' : ''"
                                            class="fi-dropdown-list-item flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 cursor-default"
                                        >
                                            {{
                                                generate_icon_html(
                                                    icon: Heroicon::CircleStack,
                                                    alias: AdvancedTablesIconAlias::ADVANCED_SEARCH_EXTRA_COLUMN,
                                                    attributes: (new ComponentAttributeBag)
                                                        ->color(IconComponent::class, 'gray'),
                                                    size: IconSize::Small,
                                                )
                                            }}
                                            <span x-text="item.label" class="truncate"></span>
                                        </div>

                                        {{-- AND boolean item --}}
                                        <div
                                            x-show="item.type === 'boolean' && item.value === 'and'"
                                            x-on:click="handleBooleanSelection(item)"
                                            x-bind:class="i === highlightedItemIndex && item.type === 'boolean' ? 'fi-active bg-gray-50 dark:bg-white/5' : ''"
                                            class="fi-dropdown-list-item flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 cursor-default"
                                        >
                                            {{
                                                generate_icon_html(
                                                    icon: Heroicon::PlusCircle,
                                                    alias: AdvancedTablesIconAlias::ADVANCED_SEARCH_BOOLEAN_AND,
                                                    attributes: (new ComponentAttributeBag)
                                                        ->color(IconComponent::class, 'gray'),
                                                    size: IconSize::Small,
                                                )
                                            }}
                                            <span x-text="item.label" class="truncate capitalize"></span>
                                            <span x-text="item.syntax" class="ms-auto font-mono text-xs text-info-600 dark:text-info-400 shrink-0"></span>
                                        </div>

                                        {{-- OR boolean item --}}
                                        <div
                                            x-show="item.type === 'boolean' && item.value === 'or'"
                                            x-on:click="handleBooleanSelection(item)"
                                            x-bind:class="i === highlightedItemIndex && item.type === 'boolean' ? 'fi-active bg-gray-50 dark:bg-white/5' : ''"
                                            class="fi-dropdown-list-item flex w-full items-center gap-2 rounded-lg px-3 py-2 text-sm text-gray-700 dark:text-gray-200 outline-none transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5 cursor-default"
                                        >
                                            {{
                                                generate_icon_html(
                                                    icon: Heroicon::MinusCircle,
                                                    alias: AdvancedTablesIconAlias::ADVANCED_SEARCH_BOOLEAN_OR,
                                                    attributes: (new ComponentAttributeBag)
                                                        ->color(IconComponent::class, 'gray'),
                                                    size: IconSize::Small,
                                                )
                                            }}
                                            <span x-text="item.label" class="truncate capitalize"></span>
                                            <span x-text="item.syntax" class="ms-auto font-mono text-xs text-info-600 dark:text-info-400 shrink-0"></span>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <x-filament::icon-button
                        icon="heroicon-m-x-circle"
                        :icon-alias="AdvancedTablesIconAlias::ADVANCED_SEARCH_CLEAR"
                        x-on:click.stop="clearAll()"
                        x-bind:class="hasActiveFilters() ? 'opacity-100' : 'opacity-0 pointer-events-none'"
                        color="gray"
                        size="xs"
                        class="shrink-0"
                    />
                </div>
            </div>

            <div class="fi-input-wrp-suffix">
                <div class="fi-input-wrp-actions">
                    @php
                        $triggerAction = $this->getAdvancedSearchTriggerAction();
                    @endphp

                    <x-filament::dropdown
                        width="sm"
                        placement="bottom-end"
                    >
                        <x-slot name="trigger">
                            {{ $triggerAction }}
                        </x-slot>

                        <div class="p-4">
                            @include('advanced-tables::components.advanced-search.search-syntax-reference')
                        </div>
                    </x-filament::dropdown>
                </div>
            </div>
        </div>
    </div>
@endif
