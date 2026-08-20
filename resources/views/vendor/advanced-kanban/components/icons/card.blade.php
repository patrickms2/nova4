@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Filament\Support\Icons\Heroicon;
@endphp
{{-- TaskResource Kanban Card Component --}}
{{-- This component is based on the Advanced Kanban package card --}}
{{-- You can customize this component to display your model's data --}}

@props([
    'record',
    'lockedColumn',
    'actions' => []
])

<div>
    <div class="kanban-item-header">
        <h4 class="kanban-item-title">
            {{ $record->{$this->getKanban()->getTitleField()} }}
        </h4>
        <div class="kanban-action flex">
            @foreach($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>

    <div wire:click="mountAction('viewAction', {recordId: {{ $record->id }} })">
        @if($record->{$this->getKanban()->getDescriptionField()})
            <p class="kanban-item-description">
                {{ Str::limit($record->{$this->getKanban()->getDescriptionField()}, 80) }}
            </p>
            <div>
                <x-filament::badge
                    size="sm"
                    :color="$record->priority->getColor()"
                    :icon="$record->priority->getIcon()"
                >
                    {{ $record->priority->getLabel() }}
                </x-filament::badge>
            </div>
            <div class="flex items-center justify-between gap-2 mt-2 w-full">
                @if($name = $record->tipo?->nombre)
                    <div class="flex items-center gap-2">
                        <x-filament::avatar
                            :size="Size::Small->value"
                            src="https://api.dicebear.com/9.x/adventurer/svg?seed={{ $name }}"
                            alt="Advanced Kanban"
                        />
                        <span class="text-xs">{{ $name }}</span>
                    </div>
                @else
                    <div class="kanban-item-footer">
                        @if($lockedColumn->isCardLocked)
                            <div class="flex items-center gap-x-1 bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded-full">
                                <x-filament::icon
                                    :icon=" $lockedColumn->icon"
                                    class="h-3 w-3 text-gray-500 dark:text-gray-200"
                                />
                                <span
                                    class="!text-xs font-medium text-gray-500 dark:text-gray-200">{{ $lockedColumn->label }}</span>
                            </div>
                        @endif
                    </div>
                @endif
                <div class="flex shrink-0 items-center gap-x-2">
                    <x-filament::icon
                        :icon="Heroicon::Calendar"
                        :size="IconSize::Small"
                        class="text-zinc-500"
                    />
                    <span class="text-xs">{{ $record->due_date?->format('M d, Y') }}</span>
                </div>
            </div>
        @endif
    </div>
</div>
