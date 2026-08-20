<div
    class="nova-workspace min-h-full bg-black text-white"
    x-data="{ pulse: null }"
    x-on:nova-runtime-updated.window="pulse = $event.detail.id; setTimeout(() => pulse = null, 700)"
    @if ($activeMission && ! in_array($activeMission['status'], ['Waiting Approval', 'Paused', 'Completed', 'Failed', 'Cancelled'], true))
        wire:poll.1000ms="advanceMission"
    @endif
>

    <main class="mx-auto w-full max-w-5xl px-6 py-12 lg:px-10 lg:py-16">
        @if ($activeWorkspaceArea)
            <section class="mx-auto max-w-4xl">
                <button wire:click="runShowcase" type="button" class="text-sm text-neutral-600 transition hover:text-white">← Volver a NOVA</button>

                <div class="mt-10 flex items-start justify-between gap-6 border-b border-neutral-900 pb-8">
                    <div>
                        <span class="text-4xl">{{ $activeWorkspaceArea['icon'] }}</span>
                        <h1 class="mt-5 text-4xl font-semibold tracking-tight">{{ $activeWorkspaceArea['name'] }}</h1>
                        <p class="mt-3 text-neutral-500">Continúa trabajando exactamente donde NOVA lo dejó.</p>
                    </div>

                    @if (($workspaceUpdates[$activeWorkspaceArea['id']]['count'] ?? 0) > 0)
                        <span class="rounded-full bg-orange-500/10 px-3 py-1.5 text-xs font-semibold text-orange-300">
                            +{{ $workspaceUpdates[$activeWorkspaceArea['id']]['count'] }} actualizado
                        </span>
                    @endif
                </div>

                <div class="mt-10 grid gap-3 sm:grid-cols-2">
                    @foreach ($activeWorkspaceArea['tools'] as $tool)
                        <button wire:click="activateTool('{{ $tool }}', '{{ $activeWorkspaceArea['name'] }}')" type="button" class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5 text-left transition hover:border-orange-500/50">
                            <span class="text-orange-500">✦</span>
                            <span class="mt-4 block text-sm font-medium text-neutral-200">{{ $tool }}</span>
                        </button>
                    @endforeach
                </div>
            </section>
        @elseif (! $activeMission)
            <section class="mx-auto max-w-5xl">
                <div class="text-center">
                    <span class="inline-flex size-12 items-center justify-center rounded-2xl bg-orange-500 text-xl font-semibold text-black">✦</span>
                    <p class="mt-7 text-sm font-semibold uppercase tracking-[0.28em] text-orange-500">NOVA</p>
                    <h1 class="mt-5 text-4xl font-semibold tracking-tight sm:text-5xl">Buenos días, {{ $userName }}.</h1>
                    <p class="mt-4 text-xl text-neutral-400">¿Qué quieres conseguir hoy?</p>
                </div>

                <div class="mt-10">
                    <x-nova.command-center
                        title="Cuéntame tu objetivo"
                        eyebrow=""
                        placeholder="Por ejemplo: crea una reserva para mañana."
                        :suggestions="$suggestions"
                        submit-label="Empezar"
                        loading-label="Entendiendo…"
                        :submit-on-enter="true"
                    />
                </div>

                <div class="mt-12 flex items-center justify-between border-b border-neutral-900">
                    <div class="flex gap-6">
                        <button wire:click="setActiveTab('capabilities')" type="button" @class([
                            'pb-3 text-sm font-semibold transition',
                            'text-orange-500 border-b-2 border-orange-500' => $activeTab === 'capabilities',
                            'text-neutral-500 hover:text-neutral-300' => $activeTab !== 'capabilities',
                        ])>
                            Capacidades
                        </button>
                        <button wire:click="setActiveTab('representations')" type="button" @class([
                            'pb-3 text-sm font-semibold transition',
                            'text-orange-500 border-b-2 border-orange-500' => $activeTab === 'representations',
                            'text-neutral-500 hover:text-neutral-300' => $activeTab !== 'representations',
                        ])>
                            Representaciones
                        </button>
                    </div>
                    <a href="{{ route('nova.graph') }}" class="mb-3 text-xs font-semibold uppercase tracking-wide text-neutral-500 transition hover:text-orange-400">
                        Ver Grafo →
                    </a>
                </div>

                @if ($activeTab === 'capabilities')
                    <div class="mt-10 grid gap-10 lg:grid-cols-2">
                        <section class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Qué gestiono</p>
                            <h2 class="mt-3 text-xl font-semibold">Capacidades activas</h2>
                            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                @foreach ($workspaceProfile['capabilities'] ?? [] as $capability)
                                    <div class="rounded-xl border border-neutral-800 bg-black/40 p-4">
                                        <span class="text-xl">{{ $capability['icon'] ?? '✦' }}</span>
                                        <p class="mt-3 font-medium">{{ $capability['name'] ?? $capability['id'] }}</p>
                                        <p class="mt-1 text-xs text-neutral-500">{{ $capability['description'] ?? '' }}</p>
                                        @if (! empty($capability['tools']))
                                            <ul class="mt-3 space-y-1 text-xs text-neutral-600">
                                                @foreach ($capability['tools'] as $tool)
                                                    <li>{{ $tool }}</li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </section>

                        <section class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Modelo</p>
                            <h2 class="mt-3 text-xl font-semibold">Entidades y relaciones</h2>
                            <div class="mt-5 space-y-4 text-sm text-neutral-300">
                                <p><span class="text-orange-500">Entidades:</span> {{ implode(', ', $workspaceProfile['operational_model']['entities'] ?? []) ?: 'Ninguna aún' }}</p>
                                <p><span class="text-orange-500">Procesos:</span> {{ implode(', ', $workspaceProfile['operational_model']['processes'] ?? []) ?: 'Ninguno aún' }}</p>
                                @if (! empty($workspaceProfile['operational_model']['relations']))
                                    <ul class="space-y-1 text-xs text-neutral-500">
                                        @foreach ($workspaceProfile['operational_model']['relations'] as $relation)
                                            <li>{{ $relation['from'] }} <span class="text-neutral-700">{{ $relation['type'] }}</span> {{ $relation['to'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </div>
                        </section>

                        <section class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Automatizaciones</p>
                            <h2 class="mt-3 text-xl font-semibold">Eventos y flujos</h2>
                            <div class="mt-5 space-y-2">
                                @forelse ($workspaceProfile['automations'] ?? [] as $automation)
                                    <div class="flex items-center gap-3 rounded-xl border border-neutral-800 bg-black/40 px-4 py-3">
                                        <span class="text-orange-500">⚡</span>
                                        <div>
                                            <p class="text-sm font-medium">{{ $automation['name'] ?? '' }}</p>
                                            <p class="text-xs text-neutral-500">Trigger: {{ $automation['trigger'] ?? '' }}</p>
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-sm text-neutral-500">Aún no hay automatizaciones configuradas.</p>
                                @endforelse
                            </div>
                        </section>

                        <section class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Fuentes de datos</p>
                            <h2 class="mt-3 text-xl font-semibold">Fuentes conectadas</h2>
                            <div class="mt-5 space-y-2">
                                @forelse ($workspaceProfile['data_sources'] ?? [] as $source)
                                    <div class="flex items-center gap-3 rounded-xl border border-neutral-800 bg-black/40 px-4 py-3">
                                        <span class="text-xl">{{ $source['icon'] ?? '✦' }}</span>
                                        <p class="text-sm font-medium">{{ $source['name'] ?? '' }}</p>
                                    </div>
                                @empty
                                    <p class="text-sm text-neutral-500">Ninguna fuente de datos conectada.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                @else
                    <div class="mt-6">
                        <div class="flex flex-wrap gap-2">
                            @foreach (['admin' => '🖥 Admin', 'web' => '🌐 Web', 'copilot' => '🤖 Copilot', 'mcp' => '🔌 MCP', 'ia' => '✨ IA'] as $key => $label)
                                <button wire:click="setActiveRepresentation('{{ $key }}')" type="button" @class([
                                    'rounded-full px-4 py-2 text-sm font-semibold transition',
                                    'bg-orange-500 text-black' => $activeRepresentation === $key,
                                    'border border-neutral-700 text-neutral-300' => $activeRepresentation !== $key,
                                ])>
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>

                        <div class="mt-8">
                            @if ($activeRepresentation === 'admin')
                                <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">{{ $workspaceProfile['representations']['admin']['title'] ?? 'Admin' }}</p>
                                    <p class="mt-2 text-sm text-neutral-400">{{ $workspaceProfile['representations']['admin']['description'] ?? '' }}</p>

                                    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($workspaceProfile['representations']['admin']['sidebar'] ?? [] as $area)
                                            <div class="rounded-xl border border-neutral-800 p-4">
                                                <span class="text-xl">{{ $area['icon'] }}</span>
                                                <p class="mt-3 font-medium">{{ $area['name'] }}</p>
                                                @if (! empty($area['tools']))
                                                    <ul class="mt-3 space-y-1 text-xs text-neutral-500">
                                                        @foreach ($area['tools'] as $tool)
                                                            <li>{{ $tool }}</li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="mt-6 flex flex-wrap gap-2">
                                        @foreach ($workspaceProfile['representations']['admin']['dashboards'] ?? [] as $dashboard)
                                            <span class="rounded-full border border-neutral-800 bg-black/40 px-3 py-1 text-xs text-neutral-400">{{ $dashboard }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif ($activeRepresentation === 'web')
                                <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">{{ $workspaceProfile['representations']['web']['title'] ?? 'Web' }}</p>
                                    <p class="mt-2 text-sm text-neutral-400">{{ $workspaceProfile['representations']['web']['description'] ?? '' }}</p>
                                    <div class="mt-5 grid gap-3 sm:grid-cols-2">
                                        @foreach ($workspaceProfile['representations']['web']['sections'] ?? [] as $section)
                                            <div class="rounded-xl border border-neutral-800 bg-black/40 p-4">
                                                <p class="font-medium">{{ $section['name'] }}</p>
                                                <p class="mt-1 text-xs text-neutral-500">{{ $section['description'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif ($activeRepresentation === 'copilot')
                                <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">{{ $workspaceProfile['representations']['copilot']['title'] ?? 'Copilot' }}</p>
                                    <p class="mt-2 text-sm text-neutral-400">{{ $workspaceProfile['representations']['copilot']['description'] ?? '' }}</p>

                                    <div class="mt-5 flex flex-wrap gap-2">
                                        @foreach ($workspaceProfile['representations']['copilot']['channels'] ?? [] as $channel)
                                            <span class="rounded-full border border-neutral-800 bg-black/40 px-3 py-1 text-sm text-neutral-300">{{ $channel['icon'] }} {{ $channel['name'] }}</span>
                                        @endforeach
                                    </div>

                                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                                        @foreach ($workspaceProfile['representations']['copilot']['intents'] ?? [] as $intent)
                                            <button type="button" wire:click="runMissionFromExample('{{ $intent['id'] }}')" class="rounded-xl border border-neutral-800 bg-black/40 p-4 text-left transition hover:border-orange-500/50">
                                                <p class="text-sm font-medium text-white">{{ $intent['label'] }}</p>
                                                <p class="mt-1 text-xs text-neutral-500">{{ $intent['example'] }}</p>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif ($activeRepresentation === 'mcp')
                                <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">{{ $workspaceProfile['representations']['mcp']['title'] ?? 'MCP' }}</p>
                                    <p class="mt-2 text-sm text-neutral-400">{{ $workspaceProfile['representations']['mcp']['description'] ?? '' }}</p>
                                    <p class="mt-4 text-sm text-orange-400 animate-pulse">{{ $workspaceProfile['representations']['mcp']['status'] ?? 'Publicando capacidades…' }}</p>
                                    <div class="mt-5 grid gap-2">
                                        @foreach ($workspaceProfile['representations']['mcp']['tools'] ?? [] as $tool)
                                            <div class="rounded-xl border border-neutral-800 bg-black/40 px-4 py-3 font-mono text-sm text-emerald-400">
                                                {{ $tool['id'] }}
                                                <span class="ml-2 text-xs text-neutral-500">{{ $tool['description'] }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @elseif ($activeRepresentation === 'ia')
                                <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6">
                                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">{{ $workspaceProfile['representations']['ia']['title'] ?? 'IA' }}</p>
                                    <p class="mt-2 text-sm text-neutral-400">{{ $workspaceProfile['representations']['ia']['description'] ?? '' }}</p>

                                    <div class="mt-5 rounded-xl border border-neutral-800 bg-black/40 p-4">
                                        <p class="text-xs text-neutral-600">Prompt inicial</p>
                                        <p class="mt-2 text-sm text-neutral-300">{{ $workspaceProfile['representations']['ia']['prompt'] ?? '' }}</p>
                                    </div>

                                    <div class="mt-6">
                                        <p class="text-xs text-neutral-600">Ejemplos de sugerencias</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($workspaceProfile['representations']['ia']['suggestions'] ?? [] as $suggestion)
                                                <span class="rounded-full border border-neutral-800 bg-black/40 px-3 py-1 text-xs text-neutral-400">{{ $suggestion }}</span>
                                            @endforeach
                                        </div>
                                    </div>

                                    <div class="mt-6">
                                        <p class="text-xs text-neutral-600">Capacidades detectadas</p>
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($workspaceProfile['representations']['ia']['capabilities'] ?? [] as $capability)
                                                <span class="rounded-full border border-orange-500/20 bg-orange-500/5 px-3 py-1 text-xs text-orange-200">{{ $capability['icon'] ?? '✦' }} {{ $capability['name'] }}</span>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <section class="mt-16 border-t border-neutral-900 pt-10" aria-labelledby="recent-work-title">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Trabajo reciente</p>
                    <h2 id="recent-work-title" class="mt-3 text-xl font-semibold">{{ $completedResults ? 'Hoy' : 'Ayer' }}</h2>

                    <ol class="mt-6 grid gap-1">
                        @forelse ($completedResults as $result)
                            <li><x-nova.mission-result-card :result="$result" /></li>
                        @empty
                            @foreach ($recentWork as $item)
                                <li class="flex items-center gap-4 rounded-xl px-3 py-3 text-sm text-neutral-400">
                                    <span class="flex size-6 items-center justify-center rounded-full bg-emerald-500/10 text-xs text-emerald-400">✓</span>
                                    <span>{{ $item['title'] }}</span>
                                </li>
                            @endforeach
                        @endforelse
                    </ol>
                </section>
            </section>
        @else
            @php($goalSteps = $this->goalSteps())

            <section>
                <div class="border-b border-neutral-900 pb-8">
                    <div class="flex items-start justify-between gap-6">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Objetivo</p>
                            <h1 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">{{ $activeMission['goal'] }}</h1>
                        </div>

                        @if ($activeMission['status'] === 'Completed')
                            <span class="shrink-0 rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-400">Completado</span>
                        @elseif ($activeMission['status'] === 'Waiting Approval')
                            <span class="shrink-0 rounded-full bg-orange-500/10 px-3 py-1.5 text-xs font-semibold text-orange-300">Necesita tu aprobación</span>
                        @elseif (in_array($activeMission['status'], ['Failed', 'Cancelled'], true))
                            <span class="shrink-0 rounded-full bg-red-500/10 px-3 py-1.5 text-xs font-semibold text-red-300">{{ $this->stateLabel($activeMission['status']) }}</span>
                        @else
                            <span class="flex shrink-0 items-center gap-2 rounded-full bg-orange-500/10 px-3 py-1.5 text-xs font-semibold text-orange-300">
                                <span class="size-1.5 animate-pulse rounded-full bg-orange-500"></span>
                                Trabajando
                            </span>
                        @endif
                    </div>
                </div>

                @if ($activeMission['status'] === 'Completed')
                    <section class="py-12" aria-labelledby="mission-result-title">
                        <div class="flex size-12 items-center justify-center rounded-full bg-emerald-500/10 text-xl text-emerald-400">✓</div>
                        <p class="mt-7 text-sm font-medium text-emerald-400">Misión completada</p>
                        <h2 id="mission-result-title" class="mt-2 text-4xl font-semibold tracking-tight">{{ $activeMission['goal'] }}</h2>

                        @if ($activeResult)
                            <section class="mt-14" aria-labelledby="outcomes-title">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Resultados</p>
                                <h3 id="outcomes-title" class="mt-3 text-2xl font-semibold">Lo que NOVA ha conseguido</h3>
                                <ol class="mt-6 grid gap-3 sm:grid-cols-2">
                                    @foreach ($activeResult['outcomes'] as $outcome)
                                        <li class="flex items-start gap-3 rounded-2xl border border-neutral-800 bg-neutral-950 px-5 py-4 text-sm leading-6 text-neutral-300">
                                            <span class="mt-0.5 text-emerald-400">✓</span>
                                            <span>{{ $outcome }}</span>
                                        </li>
                                    @endforeach
                                </ol>
                            </section>

                            <section class="mt-12 rounded-2xl border border-neutral-800 bg-[#080808] p-6" aria-labelledby="impact-title">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-orange-500">Tu Workspace se ha actualizado</p>
                                <h3 id="impact-title" class="mt-3 text-2xl font-semibold">El trabajo ya forma parte de tu negocio.</h3>
                                <ul class="mt-6 grid gap-3 text-sm text-neutral-400 sm:grid-cols-2">
                                    @foreach ($activeResult['impact'] as $impact)
                                        <li class="flex gap-3"><span class="text-orange-500">•</span><span>{{ $impact }}</span></li>
                                    @endforeach
                                </ul>
                            </section>

                            <section class="mt-12" aria-labelledby="actions-title">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Continuar trabajando</p>
                                <h3 id="actions-title" class="mt-3 text-2xl font-semibold">¿Qué quieres hacer ahora?</h3>
                                <div class="mt-6 flex flex-wrap gap-3">
                                    <button wire:click="openResultArea('{{ $activeResult['target_area_id'] }}')" type="button" class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-orange-400">
                                        Abrir {{ $activeResult['target_area_name'] }} →
                                    </button>
                                    @if ($activeResult['files'])
                                        <button
                                            type="button"
                                            x-on:click="const details = document.getElementById('mission-technical-details'); details.open = true; details.scrollIntoView({ behavior: 'smooth' })"
                                            class="rounded-xl border border-neutral-700 px-5 py-3 text-sm font-semibold text-neutral-300 transition hover:border-neutral-500"
                                        >
                                            Ver {{ count($activeResult['files']) === 1 ? 'archivo' : 'archivos' }}
                                        </button>
                                    @endif
                                    <button wire:click="runShowcase" type="button" class="rounded-xl border border-neutral-800 px-5 py-3 text-sm font-semibold text-neutral-500 transition hover:text-white">
                                        Crear otra misión
                                    </button>
                                </div>
                            </section>

                            <section class="mt-12 border-t border-neutral-900 pt-10" aria-labelledby="next-step-title">
                                <p class="text-sm text-neutral-500">Siguiente paso sugerido</p>
                                <h3 id="next-step-title" class="mt-2 text-xl font-semibold">¿Quieres que NOVA también se encargue de esto?</h3>
                                <button wire:click="startSuggestedMission" type="button" class="mt-5 flex w-full items-center justify-between rounded-2xl border border-orange-500/20 bg-orange-500/5 px-5 py-4 text-left text-sm font-medium text-orange-200 transition hover:border-orange-500/40">
                                    <span>{{ $activeResult['suggested_goal'] }}</span>
                                    <span>→</span>
                                </button>
                            </section>

                            <section class="mt-14 border-t border-neutral-900 pt-10" aria-labelledby="mission-history-title">
                                <p class="text-xs font-semibold uppercase tracking-[0.24em] text-neutral-600">Historial de trabajo</p>
                                <h3 id="mission-history-title" class="mt-3 text-xl font-semibold">Hoy</h3>
                                <div class="mt-5 grid gap-2">
                                    @foreach ($completedResults as $result)
                                        <x-nova.mission-result-card :result="$result" />
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </section>
                @elseif (in_array($activeMission['status'], ['Failed', 'Cancelled'], true))
                    <section class="py-12">
                        <p class="text-sm font-medium text-red-400">No he podido terminar este trabajo.</p>
                        <h2 class="mt-2 text-3xl font-semibold">Podemos intentarlo de otra forma.</h2>
                        <button wire:click="runShowcase" type="button" class="mt-8 rounded-xl border border-neutral-700 px-5 py-3 text-sm font-semibold text-neutral-300">
                            Volver a empezar
                        </button>
                    </section>
                @else
                    <section class="py-12" aria-labelledby="mission-progress-title">
                        @if ($activeMission['status'] === 'Waiting Approval')
                            <p class="text-sm font-medium text-orange-400">He preparado cómo hacerlo.</p>
                            <h2 id="mission-progress-title" class="mt-2 text-3xl font-semibold">Solo necesito tu aprobación.</h2>
                        @else
                            <p class="text-sm font-medium text-orange-400">Entendido.</p>
                            <h2 id="mission-progress-title" class="mt-2 text-3xl font-semibold">Estoy trabajando en ello...</h2>
                        @endif

                        <x-nova.mission-phases
                            class="mt-8"
                            :status="$activeMission['status']"
                            :progress="$activeMission['progress']"
                        />

                        <div class="mt-8 grid gap-2">
                            <div class="flex items-center justify-between text-xs text-neutral-500">
                                <span>Progreso</span>
                                <span class="font-semibold text-white">{{ (int) $activeMission['progress'] }}%</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-neutral-900">
                                <div class="h-full rounded-full bg-orange-500 transition-all duration-700" style="width: {{ max(3, $activeMission['progress']) }}%"></div>
                            </div>
                        </div>

                        <x-nova.goal-steps class="mt-8" :steps="$goalSteps" />

                        @if ($activeMission['status'] === 'Waiting Approval')
                            <div class="mt-10 flex flex-wrap gap-3 border-t border-neutral-900 pt-8">
                                <button wire:click="approveMission" type="button" class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-orange-400">
                                    Aprobar y continuar
                                </button>
                                <button wire:click="editPlan" type="button" class="rounded-xl border border-neutral-700 px-5 py-3 text-sm font-semibold text-neutral-300 transition hover:border-neutral-500">
                                    Revisar
                                </button>
                                <button wire:click="rejectMission" type="button" class="px-4 py-3 text-sm font-medium text-neutral-600 transition hover:text-red-300">
                                    Cancelar
                                </button>
                            </div>
                        @endif
                    </section>
                @endif

                <details id="mission-technical-details" class="group border-t border-neutral-900 py-7">
                    <summary class="flex cursor-pointer list-none items-center gap-2 text-sm font-medium text-neutral-600 transition hover:text-neutral-300">
                        <span>{{ $activeMission['status'] === 'Completed' ? 'Ver cómo NOVA completó esta misión' : 'Ver cómo lo hizo NOVA' }}</span>
                        <span class="transition group-open:rotate-180">⌄</span>
                    </summary>

                    <div class="mt-8 grid gap-12 rounded-2xl border border-neutral-800 bg-[#080808] p-6 lg:p-8">
                        <section>
                            <x-nova.section-heading eyebrow="Detalles de la misión" title="Plan y cronología" :meta="$activeMission['id']" />
                            <div class="mt-6">
                                <x-nova.planner-timeline
                                    :goal="$activeMission['goal']"
                                    :steps="$timeline"
                                    :estimated-time="$activeMission['planner']['estimated_duration'] ?? $activeMission['estimated_time']"
                                    :agents="$activeMission['agents']"
                                    :connectors="$activeMission['connectors']"
                                    :progress="$activeMission['progress']"
                                    :status="$activeMission['status']"
                                />
                            </div>
                        </section>

                        @if ($activeMission['capabilities'])
                            <section class="border-t border-neutral-800 pt-8">
                                <x-nova.section-heading eyebrow="Habilidades del negocio" title="Trabajo resuelto por NOVA" />
                                <div class="mt-6 grid gap-3 md:grid-cols-2">
                                    @foreach ($activeMission['capabilities'] as $capability)
                                        <article class="rounded-xl border border-neutral-800 p-4">
                                            <p class="text-sm font-semibold">{{ $capability['name'] }}</p>
                                            <p class="mt-2 text-xs leading-5 text-neutral-500">{{ $capability['description'] }}</p>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($agents)
                            <section class="border-t border-neutral-800 pt-8">
                                <x-nova.section-heading eyebrow="Trabajo interno" title="Agentes" />
                                <div class="mt-6 grid gap-4 md:grid-cols-2">
                                    @foreach ($agents as $agent)
                                        <x-nova.agent-card
                                            :name="$agent['name']"
                                            :status="$agent['status']"
                                            :progress="$agent['progress']"
                                            :current-mission="$agent['currentMission']"
                                            :current-tool="$agent['currentTool']"
                                            :last-event="$agent['lastEvent']"
                                        />
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($connectors)
                            <section class="border-t border-neutral-800 pt-8">
                                <x-nova.section-heading eyebrow="Conexiones" title="Servicios utilizados" />
                                <div class="mt-6 grid gap-4 md:grid-cols-2">
                                    @foreach ($connectors as $connector)
                                        <x-nova.connector-card
                                            :name="$connector['provider']"
                                            :provider="$connector['provider']"
                                            :status="$connector['status']"
                                            :latency="$connector['latency']"
                                            :last-sync="$connector['lastSync']"
                                            :health="$connector['health']"
                                            :current-request="$connector['currentRequest']"
                                            :current-mission="$connector['currentMission']"
                                        />
                                    @endforeach
                                </div>
                            </section>
                        @endif

                        @if ($events)
                            <section class="border-t border-neutral-800 pt-8">
                                <x-nova.activity-feed :events="$events" title="Registro de la misión" />
                            </section>
                        @endif

                        @if ($activeMission['artifacts'])
                            <section class="border-t border-neutral-800 pt-8">
                                <x-nova.section-heading eyebrow="Archivos generados" title="Resultados técnicos" />
                                <div class="mt-6 grid gap-3 md:grid-cols-2">
                                    @foreach ($activeMission['artifacts'] as $artifact)
                                        <article class="rounded-xl border border-neutral-800 p-4">
                                            <p class="truncate text-sm font-semibold">{{ $artifact['name'] }}</p>
                                            <p class="mt-2 truncate font-mono text-xs text-neutral-600">{{ $artifact['path'] }}</p>
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    </div>
                </details>
            </section>
        @endif
    </main>
</div>
