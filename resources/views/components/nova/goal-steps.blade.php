@props([
    'steps' => [],
    'status' => 'pending',
])

<ol {{ $attributes->class('grid gap-2') }}>
    @foreach ($steps as $step)
        <li
            @class([
                'rounded-2xl border px-4 py-4 transition-all duration-500',
                'border-orange-500/20 bg-orange-500/5' => $step['status'] === 'running',
                'border-neutral-800 bg-neutral-950' => $step['status'] === 'completed',
                'border-transparent' => $step['status'] === 'waiting',
            ])
        >
            <div class="flex items-start gap-4">
                <span class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full bg-neutral-900 text-xs font-semibold text-neutral-400">
                    {{ $loop->iteration }}
                </span>

                <div class="min-w-0 flex-1">
                    <div class="flex items-start justify-between gap-4">
                        <h3 @class([
                            'text-sm font-semibold',
                            'text-white' => $step['status'] === 'running',
                            'text-neutral-300' => $step['status'] === 'completed',
                            'text-neutral-500' => $step['status'] === 'waiting',
                        ])>
                            {{ $step['title'] }}
                        </h3>

                        @if ($step['status'] === 'completed')
                            <span class="flex size-5 items-center justify-center rounded-full bg-emerald-500/10 text-xs text-emerald-400">✓</span>
                        @elseif ($step['status'] === 'running')
                            <span class="flex size-5 items-center justify-center">
                                <span class="size-2 animate-pulse rounded-full bg-orange-500 shadow-[0_0_12px_rgba(249,115,22,.7)]"></span>
                            </span>
                        @else
                            <span class="size-2 rounded-full border border-neutral-700"></span>
                        @endif
                    </div>

                    @if (! empty($step['description']))
                        <p class="mt-1.5 text-sm leading-6 text-neutral-500">
                            {{ $step['description'] }}
                        </p>
                    @endif

                    @if (! empty($step['tasks']))
                        <ul class="mt-3 grid gap-1.5">
                            @foreach ($step['tasks'] as $task)
                                <li class="flex items-center gap-2 text-xs text-neutral-500">
                                    <span @class([
                                        'size-1 rounded-full',
                                        'bg-orange-500' => $step['status'] === 'running',
                                        'bg-emerald-500' => $step['status'] === 'completed',
                                        'bg-neutral-600' => $step['status'] === 'waiting',
                                    ])
                                    ></span>
                                    <span>{{ $task }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </li>
    @endforeach
</ol>
