@props([
    'record',
    'lockedColumn' => null,
    'actions' => [],
])

@php
    $name = (string) ($record->title ?: $record->file_name ?: $record->attachment_file_names ?: 'Sin archivo');
    $reference = (string) ($record->referencia ?: 'Sin referencia');
    $isFavorite = (bool) ($record->favorito ?? false);

    $typeName = (string) optional($record->getRelationValue('tipo'))->nombre;

    $typeName = $typeName
        ?: (filled($record->tipodoc ?? null) ? mb_strtoupper((string) $record->tipodoc) : null)
        ?: (filled($record->tipo ?? null) ? (string) $record->tipo : null)
        ?: 'Sin tipo';
@endphp

<div class="portal-row-tile">
    <div class="portal-row-tile__head">
        <p class="portal-row-tile__title">{{ $name }}</p>
        <div class="kanban-action flex">
            <button
                type="button"
                wire:click.stop.prevent="toggleFavorite({{ $record->getKey() }})"
                class="{{ $isFavorite ? 'text-amber-500' : 'text-gray-400' }} text-lg leading-none px-1"
                title="{{ $isFavorite ? 'Quitar favorito' : 'Marcar favorito' }}"
                aria-label="{{ $isFavorite ? 'Quitar favorito' : 'Marcar favorito' }}"
            >
                {{ $isFavorite ? '★' : '☆' }}
            </button>
            @foreach ($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>

    <p class="portal-row-tile__meta">{{ optional($record->created_at)->format('d/m/Y H:i') ?: '-' }}</p>
    <p class="portal-row-tile__meta">
        <span class="portal-row-tile__badge portal-badge-info">{{ $typeName }}</span>
        <span class="portal-row-tile__badge portal-badge-gray">{{ $reference }}</span>
    </p>
</div>
