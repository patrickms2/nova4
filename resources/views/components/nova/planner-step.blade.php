@props([
    'index' => null,
    'icon' => null,
    'title',
    'description' => null,
    'tasks' => [],
    'status' => 'pending',
    'agent' => null,
    'connector' => null,
    'duration' => null,
    'last' => false,
    'active' => false,
])

<li
    @class([
        'grid grid-cols-[24px_minmax(0,1fr)] gap-4 rounded-2xl transition-all duration-500',
        'bg-neutral-900 px-4 pt-4 shadow-sm' => $active,
        'opacity-55' => ! $active && strtolower((string) $status) !== 'completed',
    ])
    {{ $attributes }}
>
    <div class="flex flex-col items-center">
        <span
            @class([
                'mt-1 size-3 shrink-0 rounded-2xl',
                'bg-orange-500' => strtolower((string) $status) === 'running',
                'bg-green-500' => in_array(strtolower((string) $status), ['done', 'completed'], true),
                'animate-pulse' => strtolower((string) $status) === 'running',
                'bg-neutral-700' => ! in_array(strtolower((string) $status), ['done', 'completed', 'running'], true),
            ])
        ></span>
        @unless ($last)
            <span class="mt-2 w-px flex-1 bg-neutral-800"></span>
        @endunless
    </div>

    <div class="min-w-0 pb-6">
        <div class="flex items-start justify-between gap-4">
            <div class="min-w-0">
                <h3 class="truncate font-semibold text-white">
                    @if ($icon)
                        <span class="mr-2 text-neutral-400" aria-hidden="true">◆</span>
                    @endif
                    @if ($index !== null)
                        <span class="mr-2 text-neutral-500">{{ $index }}</span>
                    @endif
                    {{ $title }}
                </h3>
                @if ($description)
                    <p class="mt-2 text-sm leading-6 text-neutral-400">
                        {{ $description }}
                    </p>
                @endif

                @if (count($tasks))
                    <ul class="mt-3 grid gap-1.5">
                        @foreach ($tasks as $task)
                            <li class="flex items-center gap-2 text-xs text-neutral-500">
                                <span class="size-1 rounded-full bg-neutral-600"></span>
                                <span>{{ $task }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
            <x-nova.status-pill :status="$status" class="shrink-0" />
        </div>

        @if ($agent || $connector || $duration)
            <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-neutral-500">
                @if ($agent)
                    <span>Agente · {{ $agent }}</span>
                @endif
                @if ($connector)
                    <span>Conector · {{ $connector }}</span>
                @endif
                @if ($duration)
                    <span>{{ $duration }}</span>
                @endif
            </div>
        @endif
    </div>
</li>
