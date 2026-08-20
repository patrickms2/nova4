@php
    use App\Enums\CitaStatus;use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Carbon;

    //dd($record);
@endphp
{{-- TaskResource Kanban Card Component --}}
{{-- This component is based on the Advanced Kanban package card --}}
{{-- You can customize this component to display your model's data --}}

@props([
    'record',
    'lockedColumn',
    'actions' => []
])


@php
    @endphp
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
                Cita de tipo {{ $record->tipo->nombre }}, con
                Departamento {{ Str::limit($record->departamento->nombre, 80) }}.
                {{ $record->notes }}
            </p>
            <div class="flex shrink-0 justify-around items-center gap-x-2">
                <div class="flex shrink-0   gap-x-2">
                    <x-filament::badge
                        size="small"
                        :color="'danger'"
                        :icon="Heroicon::Calendar"
                    >
                        <span class="text-md"> {{ $record->appointment_date->format('d/m/Y') }}</span>
                    </x-filament::badge>
                </div>

                <div class="flex shrink-0 items-center gap-x-2">
                    <x-filament::badge
                        size="sm"
                        :color="'danger'"
                        :icon="'clock'"
                    >
                        <span class="text-md"> {{ Carbon::parse($record->slot_id)->format('H:i') }}</span>
                    </x-filament::badge>
                </div>

            </div>

    </div>
    <div class="flex items-center justify-between gap-2 mt-2 w-full">
        @if($name = $record->departamento->nombre)
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
                            :icon="$lockedColumn->icon"
                            class="h-3 w-3 text-gray-500 dark:text-gray-200"
                        />
                        <span
                            class="!text-xs font-medium text-gray-500 dark:text-gray-200">{{ $lockedColumn->label }}</span>
                    </div>
                @endif
            </div>
        @endif
        <div>
            @php
                $color=$record->tipo->color;
                $icon=$record->tipo->icono;
            @endphp
            <div>
                <x-filament::badge
                    size="sm"
                    :color="$record->tipo->color"
                    :icon="$record->tipo->icono"
                >
                    {{ $record->tipo->nombre }}
                </x-filament::badge>
            </div>
        </div>
        @endif
    </div>
</div>

