{{-- Cita Kanban Card Component --}}
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

    @if($record->{$this->getKanban()->getDescriptionField()})
        <p class="kanban-item-description">
            {{ Str::limit($record->{$this->getKanban()->getDescriptionField()}, 80) }}
        </p>
    @endif

    <div class="kanban-item-meta">
        <div class="kanban-item-footer">
            @if($lockedColumn->isCardLocked)
            <div class="locked-info">
                <x-filament::icon
                    :icon=" $lockedColumn->icon"
                    class="h-3 w-3 text-gray-500 dark:text-gray-400"
                />
                <span>{{ $lockedColumn->label }}</span>
            </div>
            @endif
        </div>
    </div>
</div>
