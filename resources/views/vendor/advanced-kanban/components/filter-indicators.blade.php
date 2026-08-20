@props([
    'indicators' => [],
])

@if(count($indicators) > 0)
    <div class="ak:flex ak:max-sm:flex-col ak:gap-2 ak:mb-4">
        <span class="ak:text-sm ak:pt-px ak:font-medium ak:opacity-80 ak:shrink-0">Active Filters:</span>
        <div class="ak:flex ak:items-center ak:gap-2 ak:flex-wrap">
            @foreach($indicators as $indicator)
                <x-filament::badge
                    color="primary"
                    size="lg"
                    class="ak:!rounded-md"
                >
                    <div class="ak:flex ak:items-center ak:gap-1">
                        <span class="ak:text-xs ak:truncate ak:font-medium">
                            <strong>{{ $indicator['label'] }}:</strong> {{ $indicator['displayValue'] }}
                        </span>

                        <x-filament::icon
                            x-tooltip.placement-top="{ content: 'Remove {{ $indicator['label'] }} filter' }"
                            wire:click="removeFilter('{{ $indicator['name'] }}')"
                            icon="heroicon-m-x-mark"
                            class="ak:size-4 ak:opacity-80 ak:!outline-0 ak:cursor-pointer ak:-mr-1"
                        />
                    </div>
                </x-filament::badge>
            @endforeach
                <x-filament::button
                    wire:click="clearAllFilters"
                    icon="heroicon-m-x-mark"
                    size="xs"
                    color="gray"
                    class="ak:!py-1 ak:!pl-1 ak:!rounded-md"
                >
                    Clear all
                </x-filament::button>
        </div>
    </div>
@endif
