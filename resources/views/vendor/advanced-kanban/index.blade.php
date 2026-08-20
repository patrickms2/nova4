@php
    use Asmit\AdvancedKanban\Dtos\LockedColumn;use Asmit\AdvancedKanban\KanbanBuilder;use Filament\Actions\ActionGroup;
    use Filament\Support\Facades\FilamentAsset;
    use Asmit\AdvancedKanban\RenderHooks\KanbanRenderHook;
    use Filament\Support\Facades\FilamentView;
    use Illuminate\Support\Carbon;
    $plugin = (function_exists('filament') && filament()->isServing()) ? KanbanBuilder::get() : null;
    $kanban = $this->getKanban();
    $columns = $kanban->getColumns();
    $headerActions = $kanban->getColumnHeaderActions();
    $tabs = $this->getCachedTabs();
@endphp

<x-filament-panels::page class="kanban-panel" style="visibility: hidden;">
    {{-- TABS START --}}
    @if($tabs)
        <div class="fi-tabs-wrapper">
            <x-filament::tabs class="ak:inline-flex ak:ml-auto">
                @foreach ($tabs as $tabKey => $tab)
                    @php
                        $tabKey = strval($tabKey);
                    @endphp

                    <x-filament::tabs.item
                        :active="$activeTab === $tabKey"
                        :badge="$tab->getBadge()"
                        :badge-color="$tab->getBadgeColor()"
                        :badge-icon="$tab->getBadgeIcon()"
                        :badge-icon-position="$tab->getBadgeIconPosition()"
                        :icon="$tab->getIcon()"
                        :icon-position="$tab->getIconPosition()"
                        :wire:click="'selectTab(' . (filled($tabKey) ? ('\'' . $tabKey . '\'') : 'null') . ')'"
                        :attributes="$tab->getExtraAttributeBag()"
                    >
                        {{ $tab->getLabel() ?? $this->generateTabLabel($tabKey) }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>
        </div>
    @endif
    {{-- TABS END --}}
    <div x-data="{kanbanData: null}" x-resize="kanbanData && kanbanData.calculateKanbanTopSpace()">
        <div class="ak:flex ak:items-center ak:justify-end ak:gap-4 ak:mb-4">
            {{-- HEADER --}}
            {{ FilamentView::renderHook(KanbanRenderHook::KANBAN_SEARCH_BEFORE) }}

            <div class="ak:flex ak:items-center ak:gap-2">
                @unless(!$kanban->isSearchable())
                    <x-filament::input.wrapper
                        inline-prefix="true"
                        prefix-icon="heroicon-m-magnifying-glass"
                        class="ak:w-full fi-ta-search-field">
                        <x-filament::input
                            type="search"
                            wire:model.live.debounce.500ms="kanbanSearch"
                            placeholder="Search"
                        />
                    </x-filament::input.wrapper>
                @endunless
                @unless(!$kanban->isFilterable())
                    <div>
                        {{ $this->filterAction }}
                    </div>
                @endunless
            </div>
        </div>
        {{-- Filter Indicators --}}
        @if($kanban->isFilterIndicatorVisible())
            <x-advanced-kanban::filter-indicators :indicators="$this->getFilterIndicators()"/>
        @endif
        <div
            @if (FilamentView::hasSpaMode(url()->current()))
                x-load="visible || event (ax-modal-opened)"
            @else
                x-load
            @endif
            x-load-css="[@js(FilamentAsset::getStyleHref(id:'advanced-kanban', package: 'asmit/advanced-kanban'))]"
            x-load-src="{{ FilamentAsset::getAlpineComponentSrc(id:'advanced-kanban', package: 'asmit/advanced-kanban') }}"
            x-data="kanbanBoard({ wire: @this, dropAllowed: @js($kanban->getWorkflowJson())})"
            x-ref="kanbanBoardWrapper"
            x-resize="updateMinimapMaxScroll"
            x-resize.document="$nextTick(() => calculateKanbanTopSpace())"
            x-init="kanbanData = $data"
            class="kanban-wrapper"
            wire:loading.class="is-loading"
            wire:target="kanbanSearch"
        >
            <div class="kanban-shadows"
                 :class="{'left': minimapScrollLeft > 2, 'right': minimapScrollLeft < minimapMaxScroll}"></div>
            <div
                class="kanban-container"
                x-ref="kanbanBoardContainer"
                :style="() => ({ '--kanban-top-space': `${kanbanTopSpace}px` })"
                @scroll="onKanbanBoardScroll"
            >
                <div class="kanban-header kanban-grid">

                    @foreach($columns as $key => $column)
                        <div
                            @class([
                                'kanban-header-column',
                                'relative',
                                 ... $column->getExtraColumnHeadingClass()
                            ])
                            :class="{'show-border': kanbanBoardScrollY > 20}"
                        >
                            <!-- <button
                            wire:click="infoCol('{{ $column->getStatus() }}')"
                            class="kanban-load-more-btn"
                        ></button> -->
                            <x-dynamic-component
                                :component="$this->getColumnHeaderComponent()"
                                :column="$column"
                                :actions="$this->getPreparedColumHeaderActions($column->getStatus())"/>

                            {{-- LOADING INDICATOR --}}
                            @if($kanban->isLoadingIndicatorEnabled())
                                <div class="kanban-loading-indicator"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
                {{-- KANBAN STATUS --}}
                <div
                    class="kanban-board kanban-grid"
                    id="kanban-board"
                    wire:loading.class="is-moving"
                    wire:target="moveRecord"
                >
                    @foreach($columns as $key => $column)
                        <div class="kanban-column" data-status="{{ $column->getStatus() }}"
                             wire:key="kanban-column-{{ $column->getStatus() }}">
                            <div
                                class="kanban-column-content"
                                data-status="{{ $column->getStatus() }}"
                                data-kanban-column
                            >
                                @php // if($column->isHidden()) dd($column->getStatus()); dd($column->getStatus())."OCULTA"; @endphp

                                @php $visibleRecords = $this->getKanbanRecordsForColumn($column->getStatus()); @endphp

                                @if($visibleRecords->isNotEmpty())
                                    @foreach($visibleRecords as $key => $record)
                                        <div
                                            @class([
                                                  'kanban-item',
                                                  'locked' => $column->isCardLocked($record),
                                              ])
                                            data-record-id="{{ $record->id }}" data-kanban-item
                                            data-is-locked="{{ $column->isCardLocked($record) ? 'true' : 'false' }}"

                                            wire:key="kanban-item-{{ $column->getStatus() }}-{{ $record->getKey() }}">
                                            <x-dynamic-component :component="$this->getCardComponent()"
                                                                 :record="$record"
                                                                 :lockedColumn="$this->getLockedColumn($record, $column)"
                                                                 :actions="$this->getPreparedRecordActions($record)"
                                            />
                                        </div>
                                    @endforeach
                                    {{-- LOAD MORE --}}
                                    @if($this->hasMoreRecords($column->getStatus()))
                                        <div class="kanban-load-more locked">
                                            <button
                                                wire:click="loadMoreRecords('{{ $column->getStatus() }}')"
                                                class="kanban-load-more-btn"
                                            >
                                                    <span wire:target="loadMoreRecords('{{ $column->getStatus() }}')"
                                                          wire:loading>
                                                        <x-filament::loading-indicator class="ak:h-5 ak:w-5"/>
                                                    </span>
                                                <span>Cargar Más... ({{ $this->getTotalCount($column->getStatus()) - $visibleRecords->count() }} en total)</span>
                                            </button>
                                        </div>
                                    @endif
                                @else
                                    <div class="kanban-empty locked">
                                        <x-advanced-kanban::icons.empty-state class="ak:w-8 ak:h-8"/>
                                        <span class="kanban-empty-title">{{ $kanban->getEmptyStateTitle() }}</span>
                                        <p class="kanban-empty-description">{{ $kanban->getEmptyStateDescription() }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            <x-advanced-kanban::minimap :columns="count($columns)"/>
        </div>
        <x-filament-actions::modals/>
    </div>
    {{ FilamentView::renderHook(KanbanRenderHook::KANBAN_PAGE_FOOTER) }}
</x-filament-panels::page>


