@props([
    'status' => 'ready',
    'progress' => 0,
])

@php
$phases = [
    ['id' => 'setup', 'label' => 'Setup'],
    ['id' => 'build', 'label' => 'Build'],
    ['id' => 'configure', 'label' => 'Configure'],
    ['id' => 'launch', 'label' => 'Launch'],
];

$phaseStates = match ($status) {
    'Detected', 'Planning' => [
        'setup' => 'running',
        'build' => 'waiting',
        'configure' => 'waiting',
        'launch' => 'waiting',
    ],
    'Waiting Approval' => [
        'setup' => 'completed',
        'build' => 'running',
        'configure' => 'waiting',
        'launch' => 'waiting',
    ],
    'Running' => $progress < 50 ? [
        'setup' => 'completed',
        'build' => 'completed',
        'configure' => 'running',
        'launch' => 'waiting',
    ] : [
        'setup' => 'completed',
        'build' => 'completed',
        'configure' => 'completed',
        'launch' => 'running',
    ],
    'Completed' => [
        'setup' => 'completed',
        'build' => 'completed',
        'configure' => 'completed',
        'launch' => 'completed',
    ],
    default => [
        'setup' => 'waiting',
        'build' => 'waiting',
        'configure' => 'waiting',
        'launch' => 'waiting',
    ],
};
@endphp

<div {{ $attributes->class('grid grid-cols-4 gap-2') }}>
    @foreach ($phases as $index => $phase)
        @php($state = $phaseStates[$phase['id']])
        <div
            @class([
                'relative rounded-2xl border px-3 py-4 transition-all duration-500',
                'border-orange-500/20 bg-orange-500/5' => $state === 'running',
                'border-emerald-500/10 bg-emerald-500/5' => $state === 'completed',
                'border-neutral-800 bg-neutral-950' => $state === 'waiting',
            ])
        >
            <span class="text-[10px] font-semibold uppercase tracking-wider text-neutral-500">Step 0{{ $index + 1 }}</span>

            <div class="mt-2 flex items-center justify-between gap-2">
                <span
                    @class([
                        'text-xs font-semibold',
                        'text-white' => $state === 'running',
                        'text-emerald-400' => $state === 'completed',
                        'text-neutral-500' => $state === 'waiting',
                    ])
                >
                    {{ $phase['label'] }}
                </span>

                @if ($state === 'completed')
                    <span class="flex size-4 items-center justify-center rounded-full bg-emerald-500/10 text-[10px] text-emerald-400">✓</span>
                @elseif ($state === 'running')
                    <span class="size-1.5 animate-pulse rounded-full bg-orange-500"></span>
                @else
                    <span class="size-1.5 rounded-full border border-neutral-700"></span>
                @endif
            </div>

            <div class="absolute bottom-2 left-3 right-3 h-0.5 overflow-hidden rounded-full bg-neutral-800">
                <div
                    @class([
                        'h-full rounded-full',
                        'bg-orange-500' => $state === 'running',
                        'bg-emerald-500' => $state === 'completed',
                        'bg-neutral-700' => $state === 'waiting',
                    ])
                    style="width: {{ $state === 'completed' ? 100 : ($state === 'running' ? max(5, (int) $progress) : 0) }}%"
                ></div>
            </div>
        </div>
    @endforeach
</div>
