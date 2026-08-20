@props([
    'name',
    'provider' => null,
    'status' => 'available',
    'description' => null,
    'lastSync' => null,
    'action' => null,
    'actionLabel' => 'Conectar',
    'latency' => null,
    'capabilities' => [],
    'currentMission' => null,
    'health' => null,
    'currentRequest' => null,
])

<x-nova.card interactive class="grid gap-4">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h3 class="truncate font-semibold text-white">{{ $name }}</h3>
            @if ($provider)
                <p class="mt-1 text-xs uppercase tracking-widest text-neutral-500">{{ $provider }}</p>
            @endif
            @if ($description)
                <p class="mt-2 text-sm leading-6 text-neutral-400">{{ $description }}</p>
            @endif
        </div>
        <x-nova.status-pill :status="$status" class="shrink-0" />
    </div>

    @if ($latency || count($capabilities) || $currentMission || $health || $currentRequest)
        <div class="grid gap-2 text-xs text-neutral-500">
            @if ($health)
                <span class="flex items-center gap-2">
                    <span @class([
                        'size-2 rounded-full',
                        'bg-green-500' => $health === 'healthy',
                        'bg-orange-500' => in_array($health, ['degraded', 'standby'], true),
                        'bg-red-500' => $health === 'offline',
                    ])></span>
                    Salud · {{ $health === 'healthy' ? 'Correcta' : ($health === 'degraded' ? 'Degradada' : 'En espera') }}
                </span>
            @endif
            @if ($latency)<span>Latencia · {{ $latency }}</span>@endif
            @if ($currentRequest)<span>Solicitud actual · {{ $currentRequest }}</span>@endif
            @if ($currentMission)<span class="truncate">Misión · {{ $currentMission }}</span>@endif
            @if (count($capabilities))<span>{{ implode(' · ', $capabilities) }}</span>@endif
        </div>
    @endif

    <div class="flex items-center justify-between gap-4 border-t border-neutral-800 pt-4">
        <span class="text-xs text-neutral-500">
            {{ $lastSync ? 'Última sincronización · '.$lastSync : 'Nunca sincronizado' }}
        </span>

        @if ($action)
            <button
                type="button"
                wire:click="{{ $action }}"
                class="rounded-2xl bg-neutral-800 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-neutral-700"
            >
                {{ $actionLabel }}
            </button>
        @endif
    </div>
</x-nova.card>
