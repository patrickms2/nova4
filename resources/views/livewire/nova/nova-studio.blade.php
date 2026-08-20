<div class="min-h-screen bg-black text-white">
    @if ($step === 'representations' && $workspacePreview)
        <section
            class="relative flex min-h-screen overflow-hidden bg-[#050505]"
            x-data="{
                shown: 0,
                active: @js($evolvedAreaId ?? ($workspacePreview['navigation'][0]['id'] ?? 'home')),
                total: {{ count($workspacePreview['navigation']) }},
                init() {
                    const reveal = () => {
                        if (this.shown < this.total) {
                            this.shown++
                            window.setTimeout(reveal, 140)
                        }
                    }
                    window.setTimeout(reveal, 350)
                }
            }"
        >
            <aside class="w-[270px] shrink-0 border-r border-neutral-800 bg-[#090909] px-4 py-6">
                <div class="border-b border-neutral-800 px-2 pb-6">
                    <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-orange-500">NOVA Workspace</p>
                    <div class="mt-3 flex items-center gap-3">
                        <span class="text-2xl">{{ $workspacePreview['business_icon'] }}</span>
                        <p class="truncate text-lg font-semibold">{{ $workspacePreview['business_name'] }}</p>
                    </div>
                </div>
                <p class="px-2 pt-6 text-[10px] font-bold uppercase tracking-[0.3em] text-neutral-600">Tu Workspace</p>

                <nav class="mt-3 grid gap-1">
                    @foreach ($workspacePreview['navigation'] as $index => $area)
                        <button
                            type="button"
                            x-cloak
                            x-show="shown > {{ $index }}"
                            x-transition:enter="transition duration-500 ease-out"
                            x-transition:enter-start="translate-y-2 opacity-0"
                            x-transition:enter-end="translate-y-0 opacity-100"
                            @click="active = @js($area['id'])"
                            :class="active === @js($area['id']) ? 'bg-neutral-800 text-white' : 'text-neutral-500 hover:text-neutral-300'"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm transition"
                        >
                            <span class="w-5 text-center">{{ $area['icon'] }}</span>
                            <span>{{ $area['name'] }}</span>
                            @if ($area['id'] === $evolvedAreaId)
                                <span class="ml-auto size-1.5 rounded-full bg-orange-500 shadow-[0_0_12px_rgba(249,115,22,.8)]"></span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </aside>

            <main class="min-w-0 flex-1 px-8 pb-28 pt-8 lg:px-14">
                <div class="mx-auto max-w-5xl">
                    <div class="flex items-center justify-between border-b border-neutral-800 pb-6">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-[0.28em] text-orange-500">Representaciones</p>
                            <p class="mt-2 text-sm text-neutral-500">El mismo modelo operativo en distintos canales.</p>
                        </div>
                        <span class="rounded-full border border-neutral-800 px-3 py-1 text-xs text-neutral-500">En directo</span>
                    </div>

                    @if ($previewNotice)
                        <div
                            class="mt-8 rounded-2xl border border-orange-500/25 bg-orange-500/5 px-5 py-4 text-sm text-orange-200"
                            x-data="{ visible: true }"
                            x-show="visible"
                            x-init="window.setTimeout(() => visible = false, 4200)"
                            x-transition.duration.500ms
                        >
                            {{ $previewNotice }}
                        </div>
                    @endif

                    @foreach ($workspacePreview['navigation'] as $area)
                        <section x-cloak x-show="active === @js($area['id'])" x-transition.opacity.duration.300ms class="py-12">
                            <span class="text-4xl">{{ $area['icon'] }}</span>
                            <h1 class="mt-5 text-4xl font-semibold tracking-tight">{{ $area['name'] }}</h1>
                            <p class="mt-3 max-w-xl text-neutral-500">Todo lo que NOVA puede hacer contigo en esta área.</p>

                            <div class="mt-10 grid gap-3 sm:grid-cols-2">
                                @forelse ($area['tools'] as $tool)
                                    <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                                        <span class="text-orange-500">✦</span>
                                        <p class="mt-3 text-sm font-medium text-neutral-200">{{ $tool }}</p>
                                    </div>
                                @empty
                                    <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6 text-sm text-neutral-500">
                                        Tu punto de partida para hoy.
                                    </div>
                                @endforelse
                            </div>
                        </section>
                    @endforeach
                </div>
            </main>

            <div class="fixed inset-x-0 bottom-0 z-20 flex items-center justify-between border-t border-neutral-800 bg-black/95 px-6 py-4 backdrop-blur">
                <button wire:click="returnToOverview" type="button" class="rounded-xl border border-neutral-700 px-5 py-3 text-sm font-semibold text-neutral-300 transition hover:border-neutral-500 hover:text-white">
                    ← Volver a Studio
                </button>
                <button wire:click="openNova" type="button" class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-orange-400">
                    Abrir Workspace →
                </button>
            </div>
        </section>
    @else
        <main @class([
            'mx-auto min-h-screen px-6 py-10 lg:px-10',
            'max-w-7xl' => in_array($step, ['overview', 'edit'], true),
            'flex max-w-4xl items-center justify-center' => ! in_array($step, ['overview', 'edit'], true),
        ])>
            @if ($step === 'overview' && $workspacePreview)
                <div class="w-full">
                    <header class="flex flex-col gap-6 border-b border-neutral-800 pb-8 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-orange-500">✦ NOVA Studio</p>
                            <p class="mt-8 text-xs text-neutral-600">Workspace</p>
                            <h1 class="mt-2 text-4xl font-semibold tracking-tight">{{ $workspacePreview['business_icon'] }} {{ $workspacePreview['business_name'] }}</h1>
                            <p class="mt-3 flex items-center gap-2 text-sm text-neutral-500">
                                <span class="size-2 rounded-full bg-emerald-500"></span>
                                Preparado para trabajar
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <button wire:click="openRepresentations" type="button" class="rounded-xl bg-orange-500 px-5 py-3 text-sm font-semibold text-black transition hover:bg-orange-400">
                                👁 Representaciones del Workspace
                            </button>
                            <button wire:click="openNova" type="button" class="rounded-xl border border-neutral-700 px-5 py-3 text-sm font-semibold text-neutral-300 transition hover:border-neutral-500 hover:text-white">
                                Abrir Workspace →
                            </button>
                        </div>
                    </header>

                    <div class="mt-10 grid gap-10 xl:grid-cols-[minmax(0,1fr)_280px]">
                        <div class="min-w-0 space-y-14">
                            <section aria-labelledby="current-tools-title">
                                <p class="text-xs text-neutral-600">Tu Workspace</p>
                                <h2 id="current-tools-title" class="mt-2 text-2xl font-semibold">Así trabaja NOVA contigo hoy.</h2>

                                <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach ($workspacePreview['navigation'] as $area)
                                        <article class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                                            <span class="text-xl">{{ $area['icon'] }}</span>
                                            <h3 class="mt-5 font-semibold">{{ $area['name'] }}</h3>
                                            <p class="mt-2 text-sm text-neutral-600">{{ count($area['tools']) }} formas de ayudarte en esta área.</p>
                                        </article>
                                    @endforeach
                                </div>
                            </section>

                            <section aria-labelledby="recommendations-title" class="border-t border-neutral-900 pt-12">
                                <p class="text-xs text-neutral-600">Mejoras recomendadas</p>
                                <h2 id="recommendations-title" class="mt-2 text-2xl font-semibold">Nuevas formas en las que NOVA puede ayudarte.</h2>

                                <div class="mt-6 grid gap-3 md:grid-cols-2">
                                    @forelse ($recommendations as $recommendation)
                                        <article class="flex flex-col rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                                            <span class="text-xl">{{ $recommendation['icon'] }}</span>
                                            <h3 class="mt-5 text-lg font-semibold">{{ $recommendation['name'] }}</h3>
                                            <p class="mt-3 text-sm leading-6 text-neutral-400">{{ $recommendation['reason'] }}</p>
                                            <p class="mt-3 text-sm text-neutral-600">{{ $recommendation['question'] }}</p>
                                            <button wire:click="improveWorkspace('{{ $recommendation['id'] }}')" type="button" class="mt-5 self-start rounded-xl bg-neutral-800 px-4 py-2.5 text-sm font-semibold text-neutral-200 transition hover:bg-neutral-700">
                                                {{ $recommendation['action'] }}
                                            </button>
                                        </article>
                                    @empty
                                        <p class="rounded-2xl border border-neutral-800 p-6 text-sm text-neutral-500">Tu Workspace ya reúne todas las mejoras recomendadas para este momento.</p>
                                    @endforelse
                                </div>
                            </section>

                            <section aria-labelledby="management-title" class="border-t border-neutral-900 pt-12">
                                <p class="text-xs text-neutral-600">Gestión del Workspace</p>
                                <h2 id="management-title" class="mt-2 text-2xl font-semibold">Tu negocio puede seguir evolucionando.</h2>

                                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                    <button wire:click="editWorkspace" type="button" class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5 text-left transition hover:border-neutral-600">
                                        <span class="text-xl">✎</span>
                                        <span class="mt-4 block font-semibold">Editar Workspace</span>
                                        <span class="mt-2 block text-sm text-neutral-600">Revisa el negocio y las formas en las que NOVA te ayuda.</span>
                                    </button>
                                    <button wire:click="startNewWorkspace" type="button" class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5 text-left transition hover:border-neutral-600">
                                        <span class="text-xl">＋</span>
                                        <span class="mt-4 block font-semibold">Nuevo Workspace</span>
                                        <span class="mt-2 block text-sm text-neutral-600">Crea un espacio independiente para otro negocio.</span>
                                    </button>
                                </div>

                                @if (count($workspaces) > 1)
                                    <div class="mt-6 space-y-2">
                                        @foreach ($workspaces as $workspace)
                                            <button wire:click="switchWorkspace('{{ $workspace['id'] }}')" type="button" @class([
                                                'flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-left transition',
                                                'border-orange-500/30 bg-orange-500/5' => $workspace['id'] === $workspacePreview['id'],
                                                'border-neutral-800 hover:border-neutral-600' => $workspace['id'] !== $workspacePreview['id'],
                                            ])>
                                                <span>{{ $workspace['business_icon'] }}</span>
                                                <span class="font-medium">{{ $workspace['business_name'] }}</span>
                                                @if ($workspace['id'] === $workspacePreview['id'])
                                                    <span class="ml-auto text-xs text-orange-400">Activo</span>
                                                @endif
                                            </button>
                                        @endforeach
                                    </div>
                                @endif
                            </section>
                        </div>

                        <div class="hidden xl:block">
                            <div class="sticky top-8">
                                <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-neutral-600">Representaciones</p>
                                <x-nova.workspace-preview :workspace="$workspacePreview" />
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($step === 'edit' && $workspacePreview)
                <div class="w-full">
                    <header class="flex items-end justify-between border-b border-neutral-800 pb-8">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-orange-500">✦ NOVA Studio</p>
                            <h1 class="mt-6 text-3xl font-semibold">Haz que tu Workspace evolucione.</h1>
                            <p class="mt-3 text-neutral-500">Lo que ya funciona está seleccionado. Cambia solo lo que necesites.</p>
                        </div>
                        <button wire:click="returnToOverview" type="button" class="text-sm text-neutral-500 transition hover:text-white">Cancelar</button>
                    </header>

                    <div class="mt-10 grid gap-10 xl:grid-cols-[minmax(0,1fr)_280px]">
                        <div>
                            <label class="block text-sm font-medium text-neutral-300">
                                Nombre del Workspace
                                <input wire:model="workspaceName" type="text" class="mt-3 w-full rounded-xl border border-neutral-800 bg-neutral-950 px-4 py-3 text-white outline-none transition focus:border-orange-500">
                            </label>

                            <h2 class="mt-10 text-lg font-semibold">Tu tipo de negocio</h2>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                @foreach ($businessOptions as $business)
                                    <button wire:click="selectEditedBusiness('{{ $business['id'] }}')" type="button" @class([
                                        'rounded-2xl border p-5 text-left transition',
                                        'border-orange-500 bg-orange-500/5' => $businessType === $business['id'],
                                        'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => $businessType !== $business['id'],
                                    ])>
                                        <span class="text-2xl">{{ $business['icon'] }}</span>
                                        <span class="mt-4 block font-semibold">{{ $business['name'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            @if ($businessType === 'professional')
                                <h2 class="mt-10 text-lg font-semibold">Actividad profesional</h2>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @foreach ($professionalActivities as $id => $activity)
                                        <button wire:click="selectEditedProfessionalActivity('{{ $id }}')" type="button" @class([
                                            'rounded-2xl border p-5 text-left transition',
                                            'border-orange-500 bg-orange-500/5' => $professionalActivity === $id,
                                            'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => $professionalActivity !== $id,
                                        ])>
                                            <span class="text-xl">{{ $activity['icon'] }}</span>
                                            <span class="ml-2 font-semibold">{{ $activity['name'] }}</span>
                                            <span class="mt-3 block text-sm text-neutral-600">{{ $activity['description'] }}</span>
                                        </button>
                                    @endforeach
                                </div>

                                <h2 class="mt-10 text-lg font-semibold">¿Cómo trabajas?</h2>
                                <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                    @foreach ($professionalVariantOptions as $variant)
                                        <button wire:click="selectEditedProfessionalVariant('{{ $variant['id'] }}')" type="button" @class([
                                            'rounded-2xl border p-5 text-left transition',
                                            'border-orange-500 bg-orange-500/5' => in_array($variant['id'], $professionalVariants, true),
                                            'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => ! in_array($variant['id'], $professionalVariants, true),
                                        ])>
                                            <span class="text-xl">{{ $variant['icon'] }}</span>
                                            <span class="ml-2 font-semibold">{{ $variant['name'] }}</span>
                                            <span class="mt-3 block text-sm text-neutral-600">{{ $variant['description'] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endif

                            <h2 class="mt-10 text-lg font-semibold">Formas de ayudar a tu negocio</h2>
                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                @foreach ($improvementOptions as $id => $improvement)
                                    <button wire:click="toggleEditedImprovement('{{ $id }}')" type="button" @class([
                                        'rounded-2xl border p-5 text-left transition',
                                        'border-orange-500/60 bg-orange-500/5' => in_array($id, $extraImprovements, true),
                                        'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => ! in_array($id, $extraImprovements, true),
                                    ])>
                                        <span>{{ $improvement['icon'] }}</span>
                                        <span class="ml-2 font-semibold">{{ $improvement['name'] }}</span>
                                        <span class="mt-3 block text-sm leading-5 text-neutral-600">{{ $improvement['question'] }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <button wire:click="saveWorkspaceEdits" type="button" class="mt-10 rounded-xl bg-orange-500 px-6 py-3 font-semibold text-black transition hover:bg-orange-400">
                                Guardar y ver el resultado →
                            </button>
                        </div>

                        <div class="hidden xl:block">
                            <div class="sticky top-8">
                                <p class="mb-3 text-[10px] font-bold uppercase tracking-[0.28em] text-neutral-600">Así quedará</p>
                                <x-nova.workspace-preview :workspace="$workspacePreview" />
                            </div>
                        </div>
                    </div>
                </div>
            @elseif ($step === 'welcome')
                <section class="w-full max-w-2xl text-center">
                    <p class="text-[11px] font-bold uppercase tracking-[0.32em] text-orange-500">✦ NOVA Studio</p>
                    <h1 class="mt-8 text-4xl font-semibold tracking-tight sm:text-5xl">Vamos a crear el Workspace perfecto para tu negocio</h1>
                    <p class="mx-auto mt-5 max-w-lg text-lg leading-8 text-neutral-500">Cuéntanos cómo trabajas. NOVA aprenderá de ti y preparará tu espacio.</p>
                    <button wire:click="start" type="button" class="mt-10 rounded-xl bg-orange-500 px-7 py-3.5 font-semibold text-black">Empezar →</button>
                </section>
            @elseif ($step === 'activity')
                <section class="w-full">
                    <p class="text-sm text-orange-500">Primero, tu negocio.</p>
                    <h1 class="mt-3 text-3xl font-semibold">¿Qué haces habitualmente?</h1>
                    <p class="mt-3 text-neutral-500">Elige al menos dos opciones.</p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        @foreach ($activityOptions as $activity)
                            <button wire:click="toggleActivity('{{ $activity['id'] }}')" type="button" @class([
                                'rounded-2xl border p-5 text-left transition',
                                'border-orange-500 bg-orange-500/5' => in_array($activity['id'], $activities, true),
                                'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => ! in_array($activity['id'], $activities, true),
                            ])>
                                <span class="text-2xl">{{ $activity['icon'] }}</span>
                                <span class="mt-4 block font-semibold">{{ $activity['name'] }}</span>
                                <span class="mt-2 block text-sm text-neutral-600">{{ $activity['description'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @elseif ($step === 'business')
                <section class="w-full">
                    <p class="text-sm text-orange-500">Ahora te conozco un poco mejor.</p>
                    <h1 class="mt-3 text-3xl font-semibold">¿Qué tipo de negocio quieres preparar?</h1>
                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        @foreach ($businessOptions as $business)
                            <button wire:click="selectBusiness('{{ $business['id'] }}')" type="button" class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5 text-left transition hover:border-orange-500">
                                <span class="text-2xl">{{ $business['icon'] }}</span>
                                <span class="mt-4 block font-semibold">{{ $business['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @elseif ($step === 'professional-activity')
                <section class="w-full">
                    <p class="text-sm text-orange-500">Profesionales</p>
                    <h1 class="mt-3 text-3xl font-semibold">¿Tu actividad se centra en vender o en prestar servicios?</h1>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        @foreach ($professionalActivities as $id => $activity)
                            <button wire:click="selectProfessionalActivity('{{ $id }}')" type="button" class="rounded-2xl border border-neutral-800 bg-neutral-950 p-6 text-left transition hover:border-orange-500">
                                <span class="text-2xl">{{ $activity['icon'] }}</span>
                                <span class="mt-5 block text-lg font-semibold">{{ $activity['name'] }}</span>
                                <span class="mt-2 block text-sm text-neutral-600">{{ $activity['description'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @elseif ($step === 'professional-variant')
                <section class="w-full">
                    <p class="text-sm text-orange-500">Profesionales · {{ $professionalActivities[$professionalActivity]['name'] ?? '' }}</p>
                    <h1 class="mt-3 text-3xl font-semibold">¿Qué quieres organizar con NOVA?</h1>
                    <p class="mt-3 text-neutral-500">Puedes elegir varias opciones para un mismo Workspace.</p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($professionalVariantOptions as $variant)
                            <button wire:click="selectProfessionalVariant('{{ $variant['id'] }}')" type="button" @class([
                                'rounded-2xl border p-5 text-left transition',
                                'border-orange-500 bg-orange-500/5' => in_array($variant['id'], $professionalVariants, true),
                                'border-neutral-800 bg-neutral-950 hover:border-orange-500' => ! in_array($variant['id'], $professionalVariants, true),
                            ])>
                                <span class="text-2xl">{{ $variant['icon'] }}</span>
                                <span class="mt-4 block font-semibold">{{ $variant['name'] }}</span>
                                <span class="mt-2 block text-sm leading-5 text-neutral-600">{{ $variant['description'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <button
                        wire:click="confirmProfessionalVariants"
                        type="button"
                        @disabled($professionalVariants === [])
                        class="mt-8 rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black transition disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Continuar →
                    </button>
                </section>
            @elseif ($step === 'objectives')
                <section class="w-full">
                    <p class="text-sm text-orange-500">Tus objetivos</p>
                    <h1 class="mt-3 text-3xl font-semibold">¿Qué quieres conseguir con NOVA?</h1>
                    <p class="mt-3 text-neutral-500">Elige las formas en las que NOVA puede impulsar tu negocio.</p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        @foreach ($objectiveOptions as $id => $objective)
                            <button wire:click="toggleObjective('{{ $id }}')" type="button" @class([
                                'rounded-2xl border p-5 text-left transition',
                                'border-orange-500 bg-orange-500/5' => in_array($id, $objectives, true),
                                'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => ! in_array($id, $objectives, true),
                            ])>
                                <span class="text-2xl">{{ $objective['icon'] }}</span>
                                <span class="mt-4 block font-semibold">{{ $objective['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <button
                        wire:click="confirmObjectives"
                        type="button"
                        @disabled($objectives === [])
                        class="mt-8 rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black transition disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Continuar →
                    </button>
                </section>
            @elseif ($step === 'tools')
                <section class="w-full">
                    <p class="text-sm text-orange-500">Tus herramientas</p>
                    <h1 class="mt-3 text-3xl font-semibold">¿Qué herramientas usa tu negocio hoy?</h1>
                    <p class="mt-3 text-neutral-500">Selecciona las fuentes de datos que quieres conectar.</p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($toolOptions as $id => $tool)
                            <button wire:click="toggleTool('{{ $id }}')" type="button" @class([
                                'rounded-2xl border p-5 text-left transition',
                                'border-orange-500 bg-orange-500/5' => in_array($id, $tools, true),
                                'border-neutral-800 bg-neutral-950 hover:border-neutral-600' => ! in_array($id, $tools, true),
                            ])>
                                <span class="text-2xl">{{ $tool['icon'] }}</span>
                                <span class="mt-4 block font-semibold">{{ $tool['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <button wire:click="confirmTools" type="button" class="mt-8 rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black transition">
                        Continuar →
                    </button>
                </section>
            @elseif ($step === 'thinking')
                <section wire:poll.450ms="advanceSequence" class="w-full max-w-xl text-center">
                    <span class="inline-flex size-12 animate-pulse items-center justify-center rounded-full border border-orange-500/30 text-orange-500">✦</span>
                    <h1 class="mt-7 text-3xl font-semibold">Estoy entendiendo tu negocio</h1>
                    <p class="mt-4 text-neutral-500">NOVA está aprendiendo cómo puede ayudarte mejor.</p>
                </section>
            @elseif ($step === 'discovery')
                <section wire:poll.450ms="advanceSequence" class="w-full max-w-xl text-center">
                    <span class="inline-flex size-12 animate-pulse items-center justify-center rounded-full border border-orange-500/30 text-orange-500">✦</span>
                    <h1 class="mt-7 text-3xl font-semibold">Estoy descubriendo tu negocio</h1>
                    <p class="mt-4 text-neutral-500">Ejecutando una misión del Planner sobre tu web.</p>

                    @if ($discoveryMission)
                        <div class="mt-8 text-left">
                            <div class="flex items-center justify-between text-xs text-neutral-500">
                                <span>{{ $discoveryMission['status'] }}</span>
                                <span class="font-semibold text-white">{{ (int) $discoveryMission['progress'] }}%</span>
                            </div>
                            <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-neutral-900">
                                <div class="h-full rounded-full bg-orange-500 transition-all duration-700" style="width: {{ max(3, $discoveryMission['progress']) }}%"></div>
                            </div>

                            <div class="mt-6 space-y-2 text-sm">
                                @foreach (array_slice($discoveryMission['events'] ?? [], -3) as $event)
                                    <div class="rounded-xl border border-neutral-800 bg-neutral-950 px-4 py-3 text-left">
                                        <p class="font-medium text-neutral-200">{{ $event['title'] }}</p>
                                        <p class="mt-1 text-xs text-neutral-500">{{ $event['description'] }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @elseif ($step === 'proposal' && $workspacePreview)
                <section class="w-full">
                    <p class="text-sm text-orange-500">He preparado una primera propuesta.</p>
                    <h1 class="mt-3 text-3xl font-semibold">Así puede empezar tu Workspace.</h1>
                    <div class="mt-8 grid gap-3 sm:grid-cols-3">
                        @foreach ($workspacePreview['navigation'] as $area)
                            <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                                <span>{{ $area['icon'] }}</span>
                                <p class="mt-4 font-semibold">{{ $area['name'] }}</p>
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-8 flex gap-3">
                        <button wire:click="acceptProposal" class="rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black">Me gusta →</button>
                        <button wire:click="requestMore" class="rounded-xl border border-neutral-700 px-5 py-3 text-neutral-300">Quiero añadir algo</button>
                    </div>
                </section>
            @elseif ($step === 'improvements')
                <section class="w-full">
                    <h1 class="text-3xl font-semibold">¿Cómo más quieres que NOVA te ayude?</h1>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2">
                        @foreach ($improvementOptions as $id => $improvement)
                            <button wire:click="toggleImprovement('{{ $id }}')" type="button" @class([
                                'rounded-2xl border p-5 text-left',
                                'border-orange-500 bg-orange-500/5' => in_array($id, $extraImprovements, true),
                                'border-neutral-800 bg-neutral-950' => ! in_array($id, $extraImprovements, true),
                            ])>
                                <span>{{ $improvement['icon'] }} {{ $improvement['name'] }}</span>
                            </button>
                        @endforeach
                    </div>
                    <button wire:click="confirmImprovements" class="mt-8 rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black">Continuar →</button>
                </section>
            @elseif ($step === 'website')
                <section class="w-full max-w-xl text-center">
                    <h1 class="text-3xl font-semibold">¿Tu negocio ya tiene una web?</h1>
                    <p class="mt-4 text-neutral-500">Puedo aprender de lo que ya has creado.</p>
                    <div class="mt-8 flex justify-center gap-3">
                        <button wire:click="chooseWebsite(true)" class="rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black">Sí, tengo web</button>
                        <button wire:click="chooseWebsite(false)" class="rounded-xl border border-neutral-700 px-5 py-3">Todavía no</button>
                    </div>
                </section>
            @elseif ($step === 'url')
                <section class="w-full max-w-xl">
                    <h1 class="text-3xl font-semibold">Comparte la dirección de tu web.</h1>
                    <input wire:model="websiteUrl" type="url" value="https://novagestion.eu" placeholder="https://minegocio.com" class="mt-8 w-full rounded-xl border border-neutral-800 bg-neutral-950 px-4 py-4 outline-none focus:border-orange-500">
                    @error('websiteUrl') <p class="mt-2 text-sm text-red-400">{{ $message }}</p> @enderror
                    <button wire:click="discoverWebsite" class="mt-5 rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black">Descubrir mi negocio →</button>
                </section>
            @elseif ($step === 'import')
                <section class="w-full max-w-xl text-center">
                    <h1 class="text-3xl font-semibold">Ya conozco mejor tu negocio.</h1>
                    <p class="mt-4 text-neutral-500">Puedo conservar lo que ya tienes o empezar contigo desde cero.</p>
                    <div class="mt-8 flex justify-center gap-3">
                        <button wire:click="chooseImport('import')" class="rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black">Conservar lo que ya tengo</button>
                        <button wire:click="chooseImport('fresh')" class="rounded-xl border border-neutral-700 px-5 py-3">Empezar de nuevo</button>
                    </div>
                </section>
            @elseif ($step === 'confirmation' && $workspacePreview)
                <section class="w-full">
                    <p class="text-sm text-orange-500">Todo encaja.</p>
                    <h1 class="mt-3 text-3xl font-semibold">Confirmo cómo entiendo tu negocio.</h1>

                    <div class="mt-8 grid gap-6 lg:grid-cols-2">
                        <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Objetivos</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($objectiveOptions as $id => $objective)
                                    @if (in_array($id, $objectives, true))
                                        <span class="rounded-full border border-orange-500/30 bg-orange-500/10 px-3 py-1 text-sm text-orange-200">{{ $objective['name'] }}</span>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Fuentes de datos</p>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($toolOptions as $id => $tool)
                                    @if (in_array($id, $tools, true))
                                        <span class="rounded-full border border-neutral-700 bg-neutral-900 px-3 py-1 text-sm text-neutral-300">{{ $tool['name'] }}</span>
                                    @endif
                                @endforeach
                                @if ($tools === [])
                                    <span class="text-sm text-neutral-500">Ninguna por ahora.</span>
                                @endif
                            </div>
                        </div>
                        <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Modelo operativo</p>
                            <ul class="mt-3 space-y-2 text-sm text-neutral-300">
                                <li><span class="text-orange-500">Entidades:</span> {{ count($workspacePreview['operational_model']['entities'] ?? []) }}</li>
                                <li><span class="text-orange-500">Procesos:</span> {{ count($workspacePreview['operational_model']['processes'] ?? []) }}</li>
                                <li><span class="text-orange-500">Automaciones:</span> {{ count($workspacePreview['operational_model']['automations'] ?? []) }}</li>
                                <li><span class="text-orange-500">Integraciones:</span> {{ count($workspacePreview['operational_model']['integrations'] ?? []) }}</li>
                            </ul>
                        </div>
                        <div class="rounded-2xl border border-neutral-800 bg-neutral-950 p-5">
                            <p class="text-xs font-bold uppercase tracking-wider text-neutral-600">Representaciones coordinadas</p>
                            <div class="mt-4 grid gap-4">
                                <div class="rounded-xl border border-neutral-800 bg-neutral-950 p-4">
                                    <p class="text-sm font-semibold text-white">🖥 {{ $workspacePreview['representations']['admin']['title'] ?? 'Admin' }}</p>
                                    <p class="mt-1 text-xs text-neutral-500">{{ $workspacePreview['representations']['admin']['description'] ?? 'Panel de control' }}</p>
                                    <p class="mt-2 text-xs text-orange-400">{{ count($workspacePreview['representations']['admin']['sidebar'] ?? []) }} áreas disponibles</p>
                                </div>
                                <div class="rounded-xl border border-neutral-800 bg-neutral-950 p-4">
                                    <p class="text-sm font-semibold text-white">🌐 {{ $workspacePreview['representations']['web']['title'] ?? 'Web' }}</p>
                                    <p class="mt-1 text-xs text-neutral-500">{{ $workspacePreview['representations']['web']['description'] ?? 'Web pública' }}</p>
                                    <p class="mt-2 text-xs text-orange-400">{{ count($workspacePreview['representations']['web']['sections'] ?? []) }} secciones</p>
                                </div>
                                <div class="rounded-xl border border-neutral-800 bg-neutral-950 p-4">
                                    <p class="text-sm font-semibold text-white">🤖 {{ $workspacePreview['representations']['copilot']['title'] ?? 'Copilot' }}</p>
                                    <p class="mt-1 text-xs text-neutral-500">{{ $workspacePreview['representations']['copilot']['description'] ?? 'Asistente conversacional' }}</p>
                                    <p class="mt-2 text-xs text-orange-400">{{ count($workspacePreview['representations']['copilot']['channels'] ?? []) }} canales</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex gap-3">
                        <button wire:click="createWorkspace" class="rounded-xl bg-orange-500 px-5 py-3 font-semibold text-black">Generar Workspace →</button>
                        <button wire:click="editWorkspace" class="rounded-xl border border-neutral-700 px-5 py-3 text-neutral-300">Revisar configuración</button>
                    </div>
                </section>
            @elseif ($step === 'building')
                <section wire:poll.450ms="advanceSequence" class="w-full max-w-xl text-center">
                    <span class="inline-flex size-12 animate-pulse items-center justify-center rounded-full border border-orange-500/30 text-orange-500">✦</span>
                    <h1 class="mt-7 text-3xl font-semibold">Actualizando tu Workspace...</h1>
                    <div class="mx-auto mt-8 max-w-sm space-y-3 text-left text-sm text-neutral-500">
                        @foreach (['Organizando tu espacio', 'Preparando formas de ayudarte', 'Adaptando NOVA a tu negocio', 'Todo listo'] as $index => $line)
                            <p @class(['transition', 'text-neutral-200' => $sequence > $index])>✓ {{ $line }}</p>
                        @endforeach
                    </div>
                </section>
            @elseif ($step === 'evolving' && $activeImprovement)
                <section wire:poll.500ms="advanceSequence" class="w-full max-w-xl text-center">
                    <span class="text-4xl">{{ $activeImprovement['icon'] }}</span>
                    <h1 class="mt-7 text-3xl font-semibold">Estoy mejorando {{ $activeArea['name'] ?? 'tu Workspace' }}</h1>
                    <div class="mx-auto mt-8 max-w-sm space-y-3 text-left text-sm">
                        @foreach ($activeImprovement['steps'] as $index => $line)
                            <p @class(['text-neutral-200' => $sequence > $index, 'text-neutral-700' => $sequence <= $index])>✓ {{ $line }}</p>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>
    @endif
</div>
