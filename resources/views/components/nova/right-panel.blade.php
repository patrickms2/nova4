@props([
    'artifacts' => [],
    'events' => [],
    'activeMission' => null,
    'missionsCount' => 0,
])

<aside
    id="nova-activity"
    x-data="{ tab: 'context' }"
    class="sticky top-0 h-screen overflow-hidden border-l border-neutral-800 bg-neutral-900 px-5 py-6 text-white"
>
    <div class="flex h-full flex-col gap-6">
        <div>
            <h2 class="text-lg font-semibold">Nova</h2>
            <p class="mt-1 text-xs text-neutral-400">Contexto del motor de misiones</p>
        </div>

        <nav class="grid grid-cols-2 gap-2" aria-label="Inspector de misión">
            @foreach (['context' => 'Contexto', 'capabilities' => 'Capacidades', 'artifacts' => 'Artefactos', 'events' => 'Eventos'] as $key => $label)
                <button
                    type="button"
                    @click="tab = '{{ $key }}'"
                    :class="tab === '{{ $key }}' ? 'bg-orange-500 text-black' : 'bg-neutral-800 text-neutral-400 hover:text-white'"
                    class="rounded-2xl px-3 py-2 text-left text-xs font-semibold transition-colors"
                >
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <div class="min-h-0 flex-1 overflow-y-auto">
            <div x-show="tab === 'context'" class="grid gap-6">
                <section class="border-t border-neutral-700 py-6">
                    <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Misión activa</p>
                    <p class="mt-4 text-sm leading-6 text-white">
                        {{ $activeMission['goal'] ?? 'No hay ninguna misión en ejecución.' }}
                    </p>
                    <div class="mt-6 grid gap-3 text-xs text-neutral-400">
                        <p>{{ $missionsCount }} {{ $missionsCount === 1 ? 'misión' : 'misiones' }} en este espacio de trabajo</p>
                        @if ($activeMission)
                            <p>Planificador · {{ [
                                'detected' => 'detectada',
                                'thinking' => 'analizando',
                                'ready' => 'preparado',
                                'revising' => 'revisando',
                            ][$activeMission['planner']['status']] ?? $activeMission['planner']['status'] }}</p>
                            <p>Aprobación · {{ [
                                'not_requested' => 'no solicitada',
                                'pending' => 'pendiente',
                                'approved' => 'aprobada',
                                'rejected' => 'rechazada',
                                'editing' => 'en edición',
                            ][$activeMission['approval']['status']] ?? $activeMission['approval']['status'] }}</p>
                            @if ($activeMission['result'])
                                <p class="leading-5 text-white">{{ $activeMission['result'] }}</p>
                            @endif
                        @endif
                    </div>
                </section>
            </div>

            <div x-show="tab === 'capabilities'" x-cloak class="grid gap-6">
                @if ($activeMission)
                    <section class="border-t border-neutral-700 py-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Modelo de negocio</p>
                        <p class="mt-2 text-sm font-semibold text-white">{{ $activeMission['blueprint']['name'] }}</p>
                    </section>

                    <section class="border-t border-neutral-700 py-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Capacidades resueltas</p>
                        <ol class="mt-4 grid gap-4">
                            @foreach ($activeMission['capabilities'] as $capability)
                                <li wire:key="capability-{{ $capability['id'] }}" class="grid gap-2">
                                    <div class="flex items-center justify-between gap-4">
                                        <span class="text-sm font-semibold text-white">{{ $capability['name'] }}</span>
                                        <span class="text-xs text-neutral-500">{{ $capability['estimatedDuration'] }} s</span>
                                    </div>
                                    <p class="text-xs leading-5 text-neutral-400">{{ $capability['description'] }}</p>
                                    @if ($capability['dependencies'])
                                        <p class="text-xs text-neutral-500">
                                            Depende de · {{ implode(' → ', $capability['dependencyNames']) }}
                                        </p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>

                    <section class="grid gap-4 border-t border-neutral-700 py-5 text-xs">
                        <div>
                            <p class="font-semibold uppercase tracking-widest text-neutral-400">Agentes asignados</p>
                            <p class="mt-2 leading-5 text-neutral-300">{{ implode(' · ', $activeMission['agents']) ?: 'Ninguno' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold uppercase tracking-widest text-neutral-400">Conectores asignados</p>
                            <p class="mt-2 leading-5 text-neutral-300">{{ implode(' · ', $activeMission['connectors']) ?: 'Ninguno' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold uppercase tracking-widest text-neutral-400">Proveedores</p>
                            <p class="mt-2 leading-5 text-neutral-300">{{ implode(' · ', $activeMission['providers']) ?: 'Ninguno' }}</p>
                        </div>
                        <div>
                            <p class="font-semibold uppercase tracking-widest text-neutral-400">Duración estimada</p>
                            <p class="mt-2 text-neutral-300">{{ str_replace(' seconds', ' segundos', $activeMission['estimated_time']) }}</p>
                        </div>
                    </section>

                    <section class="border-t border-neutral-700 py-5">
                        <p class="text-xs font-semibold uppercase tracking-widest text-neutral-400">Orden de ejecución</p>
                        <ol class="mt-4 grid gap-2 text-xs text-neutral-300">
                            @foreach ($activeMission['capability_graph'] as $index => $node)
                                <li>{{ $index + 1 }}. {{ $node['name'] }}</li>
                            @endforeach
                        </ol>
                    </section>
                @else
                    <x-nova.empty-state title="No hay ningún grafo de capacidades" description="Crea una misión para inspeccionar las capacidades resueltas." />
                @endif
            </div>

            <div x-show="tab === 'artifacts'" x-cloak class="grid gap-4">
                @forelse ($artifacts as $artifact)
                    <article
                        wire:key="nova-artifact-{{ $artifact['id'] }}"
                        x-transition:enter="transition ease-out duration-500"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                        x-bind:class="pulse === '{{ $artifact['id'] }}' ? 'bg-neutral-800/60' : ''"
                        class="border-t border-neutral-700 py-4"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <span class="truncate text-sm font-semibold">{{ $artifact['name'] }}</span>
                            <span class="text-xs uppercase tracking-widest text-neutral-400">{{ $artifact['type'] }}</span>
                        </div>
                        <p class="mt-2 truncate font-mono text-xs text-neutral-400">{{ $artifact['path'] }}</p>
                    </article>
                @empty
                    <x-nova.empty-state title="Todavía no hay artefactos" description="Los resultados de la misión aparecerán aquí durante la ejecución." />
                @endforelse
            </div>

            <div x-show="tab === 'events'" x-cloak>
                <x-nova.activity-feed :events="$events" title="Eventos de la misión" />
            </div>
        </div>
    </div>
</aside>
