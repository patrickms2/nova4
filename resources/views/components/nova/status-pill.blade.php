@props([
    'status',
    'tone' => null,
])

@php
    $normalized = strtolower((string) $status);

    $resolvedTone = $tone ?? match ($normalized) {
        'installed', 'connected', 'online', 'done', 'completed', 'success', 'healthy' => 'success',
        'running', 'planning', 'syncing', 'thinking', 'executing', 'preparing', 'detected' => 'active',
        'failed', 'error', 'disconnected' => 'danger',
        default => 'muted',
    };

    $toneClasses = match ($resolvedTone) {
        'success' => 'bg-green-500/15 text-green-400',
        'active' => 'bg-neutral-800 text-orange-400',
        'danger' => 'bg-neutral-800 text-red-400',
        default => 'bg-neutral-800 text-neutral-400',
    };

    $label = match ($normalized) {
        'detected' => 'Detectada',
        'planning' => 'Planificando',
        'waiting approval' => 'Esperando aprobación',
        'running' => 'En ejecución',
        'paused' => 'Pausada',
        'completed', 'done' => 'Completada',
        'failed' => 'Fallida',
        'cancelled' => 'Cancelada',
        'ready' => 'Preparado',
        'waiting' => 'En espera',
        'thinking' => 'Analizando',
        'executing' => 'Ejecutando',
        'preparing' => 'Preparando',
        'online', 'connected' => 'Conectado',
        'standby' => 'En espera',
        'available' => 'Disponible',
        default => (string) $status,
    };
@endphp

<span {{ $attributes->class(['rounded-2xl px-3 py-1 text-xs font-semibold uppercase tracking-widest shadow-sm', $toneClasses]) }}>
    {{ mb_strtoupper($label) }}
</span>
