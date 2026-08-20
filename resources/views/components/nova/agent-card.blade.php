@props([
    'name',
    'responsibility' => null,
    'status' => 'idle',
    'connectors' => [],
    'lastRun' => null,
    'progress' => 0,
    'lastActivity' => null,
    'currentMission' => null,
    'currentTool' => null,
    'lastEvent' => null,
])

<x-nova.card interactive class="grid gap-4">
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <h3 class="truncate font-semibold text-white">{{ $name }}</h3>
            @if ($responsibility)
                <p class="mt-2 text-sm leading-6 text-neutral-400">{{ $responsibility }}</p>
            @endif
        </div>
        <x-nova.status-pill :status="$status" class="shrink-0" />
    </div>

    <div class="grid gap-2">
        <div class="flex justify-between text-xs text-neutral-500">
            <span>Progreso</span>
            <span>{{ (int) $progress }}%</span>
        </div>
        <div class="h-1.5 overflow-hidden rounded-2xl bg-neutral-800">
            <div class="h-full rounded-2xl bg-orange-500 transition-all duration-500" style="width: {{ max(0, min(100, (int) $progress)) }}%"></div>
        </div>
    </div>

    @if ($currentMission || $lastActivity || $currentTool || $lastEvent)
        <div class="grid gap-1 text-xs text-neutral-500">
            @if ($currentMission)<span class="truncate">Misión · {{ $currentMission }}</span>@endif
            @if ($currentTool)<span>Herramienta actual · {{ $currentTool }}</span>@endif
            @if ($lastEvent)<span>Último evento · {{ $lastEvent }}</span>@endif
            @if ($lastActivity)<span>Última actividad · {{ $lastActivity }}</span>@endif
        </div>
    @endif

    @if (count($connectors) || $lastRun)
        <div class="flex flex-wrap items-center justify-between gap-4 border-t border-neutral-800 pt-4 text-xs text-neutral-500">
            <div class="flex flex-wrap items-center gap-2">
                @foreach ($connectors as $connector)
                    <span class="rounded-2xl bg-neutral-800 px-2 py-1">{{ $connector }}</span>
                @endforeach
            </div>
            @if ($lastRun)
                <span>Última ejecución · {{ $lastRun }}</span>
            @endif
        </div>
    @endif

    {{ $slot }}
</x-nova.card>
