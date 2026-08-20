@php
    use Filament\Support\Facades\FilamentAsset;
    use Guava\Calendar\Enums\Context;
    use Filament\Support\Facades\FilamentColor;
    use Filament\Support\View\Components\ButtonComponent;
@endphp

<x-filament-widgets::widget>
    {{-- Filters: Department + Dates + Buscar — single row --}}
    <form wire:submit.prevent="applyFilters" class="mb-4">
        <div class="flex flex-wrap items-end gap-3">
            <div class="min-w-[180px] flex-1">
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Departamento</label>
                <select
                    wire:model="departmentId"
                    class="fi-select-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
                    <option value="">Todos los departamentos</option>
                    @foreach ($this->getDepartmentOptions() as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[150px]">
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Desde</label>
                <input
                    type="date"
                    wire:model="filterStartDate"
                    class="fi-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
            </div>
            <div class="min-w-[150px]">
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Hasta</label>
                <input
                    type="date"
                    wire:model="filterEndDate"
                    class="fi-input w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"
                >
            </div>
            <div>
                <button
                    type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-red-500 focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 dark:bg-red-500 dark:hover:bg-primary-400"
                >
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/>
                    </svg>
                    Buscar
                </button>
            </div>
        </div>
    </form>

    {{-- Department color legend --}}
    @if (empty($this->departmentId) && count($this->getDepartmentOptions()) > 1)
        <div class="mb-4 flex flex-wrap items-center gap-2">
            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Leyenda:</span>
            @foreach ($this->getDepartmentColors() as $dept)
                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                      style="background-color: {{ $dept['color'] }}20; color: {{ $dept['color'] }};">
                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $dept['color'] }};"></span>
                    {{ $dept['name'] }}
                </span>
            @endforeach
        </div>
    @endif

    <x-filament::section
        :after-header="$this->getCachedHeaderActionsComponent()"
        :footer="$this->getCachedFooterActionsComponent()"
    >
        <style>
            .ec-event.ec-preview,
            .ec-now-indicator {
                z-index: 30;
            }
        </style>

        @if($heading = $this->getHeading())
            <x-slot name="heading">
                {{ $this->getHeading() }}
            </x-slot>
        @endif

        <div wire:key="calendar-{{ $this->calendarKey }}">
            <div
                wire:ignore
                x-load
                x-load-src="{{ FilamentAsset::getAlpineComponentSrc('calendar', 'guava/calendar') }}"
                x-data="calendar({
                view: @js($this->getCalendarView()),
                locale: @js($this->getLocale()),
                firstDay: @js($this->getFirstDay()),
                dayMaxEvents: @js($this->getDayMaxEvents()),
                eventContent: @js($this->getEventContentJs()),
                eventClickEnabled: @js($this->isEventClickEnabled()),
                eventDragEnabled: @js($this->isEventDragEnabled()),
                eventResizeEnabled: @js($this->isEventResizeEnabled()),
                noEventsClickEnabled: @js($this->isNoEventsClickEnabled()),
                dateClickEnabled: @js($this->isDateClickEnabled()),
                dateSelectEnabled: @js($this->isDateSelectEnabled()),
                datesSetEnabled: @js($this->isDatesSetEnabled()),
                viewDidMountEnabled: @js($this->isViewDidMountEnabled()),
                eventAllUpdatedEnabled: @js($this->isEventAllUpdatedEnabled()),
                hasDateClickContextMenu: @js($this->hasContextMenu(Context::DateClick)),
                hasDateSelectContextMenu: @js($this->hasContextMenu(Context::DateSelect)),
                hasEventClickContextMenu: @js($this->hasContextMenu(Context::EventClick)),
                hasNoEventsClickContextMenu: @js($this->hasContextMenu(Context::NoEventsClick)),
                resources: @js($this->getResourcesJs()),
                resourceLabelContent: @js($this->getResourceLabelContentJs()),
                theme: @js($this->getTheme()),
                options: @js($this->getOptions()),
                eventAssetUrl: @js(FilamentAsset::getAlpineComponentSrc('calendar-event', 'guava/calendar')),
            })"
                @class(FilamentColor::getComponentClasses(ButtonComponent::class, 'primary'))
            >
                <div data-calendar></div>
                @if($this->hasContextMenu())
                    <x-guava-calendar::context-menu/>
                @endif
            </div>
        </div>
    </x-filament::section>
    <x-filament-actions::modals/>
</x-filament-widgets::widget>
