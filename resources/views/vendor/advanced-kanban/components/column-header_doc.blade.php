{{-- This component is based on the Advanced Kanban package column header --}}
{{-- You can customize this component to match your design requirements --}}

@php use Filament\Support\Enums\IconSize;


@endphp
@props([
    'column' => null,
    'status' => null,
    'actions' => [],
])
<div>
    <div class="max-w-screen-xl mx-auto px-4 md:px-8">

    </div>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="kanban-column-label">

                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <x-filament::icon :icon="$column->getIcon()"
                                          color="{{$column->getIconColor()}}"
                                          :size="IconSize::Small"
                        />
                        <h3 class="kanban-column-title">
                            {{ $column->getLabel() ?? $column->getStatus() }}
                        </h3>
                        <x-filament::badge
                            color="primary"
                            size="sm"
                        >
                            {{ $this->getKanban()->getTotalCount($column->getStatus()) ?? 0 }}
                        </x-filament::badge>
                    </div>
                    <p class="kanban-column-description text-xs text-gray-500">
                        {{ $column->getDescription() }}
                    </p>
                </div>
            </div>

        </div>
        <div class="flex items-center gap-2">
            @foreach($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>
</div>
