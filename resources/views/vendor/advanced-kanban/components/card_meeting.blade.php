@php
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Enums\Size;
    use Filament\Support\Icons\Heroicon;
    use Illuminate\Support\Str;

    $status = (string) ($record->status ?? 'pendiente');
    $statusLabel = ucfirst($status);
    $statusColor = match ($status) {
        'confirmado' => 'info',
        'finalizada' => 'success',
        'cancelada' => 'danger',
        default => 'gray',
    };
    $statusIcon = match ($status) {
        'confirmado' => Heroicon::CheckCircle,
        'finalizada' => Heroicon::CheckBadge,
        'cancelada' => Heroicon::XCircle,
        default => Heroicon::Clock,
    };
@endphp

@props([
    'record',
    'lockedColumn',
    'actions' => []
])

<div>
    <div class="kanban-item-header">
        <h4 class="kanban-item-title">
            {{ $record->{$this->getKanban()->getTitleField()} ?: 'Sin motivo' }}
        </h4>
        <div class="kanban-action flex">
            @foreach($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>

    @if($record->{$this->getKanban()->getDescriptionField()})
        <p class="kanban-item-description">
            {{ Str::limit($record->{$this->getKanban()->getDescriptionField()}, 90) }}
        </p>
    @endif

    <div class="mt-2">
        <x-filament::badge size="sm" :color="$statusColor" :icon="$statusIcon">
            {{ $statusLabel }}
        </x-filament::badge>
    </div>

    <div class="flex items-center justify-between gap-2 mt-2 w-full">
        @php($departmentName = $record->department?->name ?: 'Sin departamento')
        <div class="flex items-center gap-2 min-w-0">
            <x-filament::avatar
                :size="Size::Small->value"
                src="https://api.dicebear.com/9.x/adventurer/svg?seed={{ $departmentName }}"
                alt="Departamento"
            />
            <span class="text-xs truncate">{{ $departmentName }}</span>
        </div>

        <div class="flex shrink-0 items-center gap-x-2">
            <x-filament::icon
                :icon="Heroicon::Calendar"
                :size="IconSize::Small"
                class="text-zinc-500"
            />
            <span class="text-xs">
                {{ $record->scheduled_start_at?->format('d/m/Y H:i') ?: '-' }}
            </span>
        </div>
    </div>

    @if($record->tipo?->nombre)
        <div class="mt-2">
            <x-filament::badge size="sm" color="gray" icon="heroicon-o-rectangle-stack">
                {{ $record->tipo->nombre }}
            </x-filament::badge>
        </div>
    @endif
</div>
