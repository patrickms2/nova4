@props([
    'goal' => null,
    'steps' => [],
    'estimatedTime' => null,
    'agents' => [],
    'connectors' => [],
    'progress' => null,
    'status' => 'draft',
    'approveAction' => null,
    'approveLabel' => 'Aprobar plan',
])

<section {{ $attributes->class('grid gap-6') }}>
    <x-nova.section-heading
        eyebrow="Planificador"
        :title="$goal ?? 'Plan de ejecución'"
        :meta="$estimatedTime"
    />

    @if (count($agents) || count($connectors))
        <div class="flex flex-wrap items-center gap-2">
            @foreach ($agents as $agent)
                <span class="rounded-2xl border border-neutral-800 bg-neutral-900 px-3 py-1 text-xs text-neutral-400">
                    Agente · {{ $agent }}
                </span>
            @endforeach
            @foreach ($connectors as $connector)
                <span class="rounded-2xl border border-neutral-800 bg-neutral-900 px-3 py-1 text-xs text-neutral-400">
                    Conector · {{ $connector }}
                </span>
            @endforeach
        </div>
    @endif

    @if ($progress !== null)
        <div class="grid gap-2">
            <div class="flex items-center justify-between text-xs text-neutral-500">
                <span>Progreso</span>
                <span>{{ (int) $progress }}%</span>
            </div>
            <div class="h-1.5 w-full overflow-hidden rounded-2xl bg-neutral-800">
                <div
                    class="h-full rounded-2xl bg-orange-500 transition-all duration-500"
                    style="width: {{ max(0, min(100, (int) $progress)) }}%"
                ></div>
            </div>
        </div>
    @endif

    @if (count($steps))
        <ol class="grid">
            @foreach ($steps as $index => $step)
                <x-nova.planner-step
                    wire:key="nova-plan-step-{{ $index }}"
                    :index="$index + 1"
                    :icon="$step['icon'] ?? null"
                    :title="$step['title']"
                    :description="$step['description'] ?? null"
                    :tasks="$step['tasks'] ?? []"
                    :status="$step['status'] ?? 'pending'"
                    :agent="$step['agent'] ?? null"
                    :connector="$step['connector'] ?? null"
                    :duration="$step['duration'] ?? null"
                    :last="$loop->last"
                    :active="($step['status'] ?? null) === 'running'"
                />
            @endforeach
        </ol>
    @else
        <x-nova.empty-state
            title="Todavía no hay ningún plan"
            description="Describe un objetivo en el Centro de mando y Nova creará un plan de ejecución."
        />
    @endif

    @if ($approveAction && count($steps))
        <div class="flex items-center justify-between gap-4 border-t border-neutral-800 pt-6">
            <x-nova.status-pill :status="$status" />
            <button
                type="button"
                wire:click="{{ $approveAction }}"
                wire:loading.attr="disabled"
                wire:target="{{ $approveAction }}"
                class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-semibold text-black shadow-sm transition-colors hover:bg-orange-400 disabled:cursor-wait disabled:opacity-60"
            >
                {{ $approveLabel }}
            </button>
        </div>
    @endif
</section>
