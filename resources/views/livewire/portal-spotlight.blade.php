<div
    x-data="{
        showSpotlight: @entangle('showSpotlight'),
        spotlight: @entangle('spotlight'),
        selectedIndex: 0,
        quickItemsCount: 7,
        dispatchUtility(eventName) {
            this.showSpotlight = false
            $wire.closeSpotlight()

            window.setTimeout(() => {
                window.dispatchEvent(new CustomEvent(eventName))
            }, 120)
        },
        dispatchQuickAction(actionName) {
            this.showSpotlight = false
            $wire.closeSpotlight()

            window.setTimeout(() => {
                if (document.querySelector('[data-portal-quick-actions-host]')) {
                    window.dispatchEvent(new CustomEvent('portal-run-quick-action', {
                        detail: { actionName },
                    }))

                    return
                }

                $wire.runQuickAction(actionName)
            }, 120)
        },
    }"
    x-init="
        $watch('showSpotlight', value => {
            if (! value) {
                selectedIndex = 0
                return
            }

            selectedIndex = 0
            $nextTick(() => $refs.spotlightInput?.focus())
        })

        $watch('spotlight', () => {
            selectedIndex = 0
        })
    "
    @open-spotlight.window="showSpotlight = true; $wire.openSpotlight()"
    @keydown.meta.k.window.prevent="showSpotlight = true; $wire.openSpotlight()"
    @keydown.ctrl.k.window.prevent="showSpotlight = true; $wire.openSpotlight()"
    @keydown.escape.window="showSpotlight = false; $wire.closeSpotlight()"
    @keydown.arrow-down.window.prevent="
        if (! showSpotlight) return
        const results = $root.querySelectorAll('[data-spotlight-result]').length
        const total = ((spotlight ?? '').trim().length >= 2) ? results : quickItemsCount + results
        if (total > 0) {
            selectedIndex = (selectedIndex + 1) % total
        }
    "
    @keydown.arrow-up.window.prevent="
        if (! showSpotlight) return
        const results = $root.querySelectorAll('[data-spotlight-result]').length
        const total = ((spotlight ?? '').trim().length >= 2) ? results : quickItemsCount + results
        if (total > 0) {
            selectedIndex = selectedIndex === 0 ? total - 1 : selectedIndex - 1
        }
    "
    @keydown.enter.window.prevent="
        if (! showSpotlight) return
        const results = $root.querySelectorAll('[data-spotlight-result]').length
        const searching = ((spotlight ?? '').trim().length >= 2)
        const total = searching ? results : quickItemsCount + results
        if (total < 1) return

        const activeInput = document.activeElement
        if (activeInput && activeInput.tagName === 'INPUT' && activeInput !== $refs.spotlightInput) return

        if (! searching) {
            if (selectedIndex === 0) { dispatchQuickAction('createCita'); return }
            if (selectedIndex === 1) { dispatchQuickAction('createTicket'); return }
            if (selectedIndex === 2) { dispatchQuickAction('createDocumento'); return }
            if (selectedIndex === 3) { dispatchUtility('open-portal-help-guide'); return }
            if (selectedIndex === 4) { dispatchUtility('toggle-time-clock'); return }
            if (selectedIndex === 5) { dispatchQuickAction('createIncidencia'); return }
            if (selectedIndex === 6) { dispatchUtility('open-announcements'); return }
        }

        const selectedResult = $root.querySelector('[data-spotlight-item=\'' + selectedIndex + '\']')
        if (selectedResult) {
            selectedResult.click()
        }
    "
>
    <div
        x-show="showSpotlight"
        x-transition.opacity
        class="fixed inset-0 z-50"
    >
        <div class="absolute inset-0 bg-black/75" @click="showSpotlight = false; $wire.closeSpotlight()"></div>

        <div class="absolute inset-0 flex items-center justify-center px-4 sm:px-6">
            <div
                class="w-full max-w-5xl"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="transform translate-y-3 opacity-0"
                x-transition:enter-end="transform translate-y-0 opacity-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="transform translate-y-0 opacity-100"
                x-transition:leave-end="transform translate-y-3 opacity-0"
            >
                <div class="glass rounded-3xl border border-white/10 bg-black/50 p-4 backdrop-blur">
                    <div class="flex items-center gap-3">
                        <input
                            type="text"
                            wire:model.live.debounce.300ms="spotlight"
                            placeholder="Buscar citas, documentos, tickets…"
                            class="input-glass"
                            x-ref="spotlightInput"
                        />
                        <x-portal.button
                            variant="ghost"
                            x-on:click="showSpotlight = false; $wire.closeSpotlight()"
                            data-portal-pending-label="Cerrar buscador"
                        >
                            ESC
                        </x-portal.button>
                    </div>

                    <div class="mt-4 space-y-3">
                        @if (strlen($spotlight ?? '') < 2)
                            <p class="text-[11px] uppercase tracking-widest text-white/40">Acciones rápidas</p>

                            <x-portal.row
                                title="CITA"
                                subtitle="Solicitar cita"
                                :iconBg="'bg-blue-500/10 ring-1 ring-blue-500/20'"
                                x-on:click="dispatchQuickAction('createCita')"
                                role="button"
                                tabindex="0"
                                data-portal-pending-label="CITA"
                                data-spotlight-item="0"
                                x-bind:class="{ 'ring-2 ring-blue-500 bg-white/5': selectedIndex === 0 }"
                            >
                                <x-slot:icon>
                                    <x-heroicon-o-calendar-days class="h-6 w-6 text-blue-200" />
                                </x-slot:icon>
                                <x-slot:right>
                                    <x-portal.badge color="blue">+ Crear</x-portal.badge>
                                </x-slot:right>
                            </x-portal.row>
                            <x-portal.row
                                title="TICKET"
                                subtitle="Incidencia o duda"
                                :iconBg="'bg-red-500/10 ring-1 ring-red-500/20'"
                                x-on:click="dispatchQuickAction('createTicket')"
                                role="button"
                                tabindex="0"
                                data-portal-pending-label="TICKET"
                                data-spotlight-item="1"
                                x-bind:class="{ 'ring-2 ring-red-500 bg-white/5': selectedIndex === 1 }"
                            >
                                <x-slot:icon>
                                    <x-heroicon-o-exclamation-circle class="h-6 w-6 text-red-200" />
                                </x-slot:icon>
                                <x-slot:right>
                                    <x-portal.badge color="red">+ Crear</x-portal.badge>
                                </x-slot:right>
                            </x-portal.row>
                            <x-portal.row
                                title="DOCUMENTO"
                                subtitle="Subir documentación"
                                :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                x-on:click="dispatchQuickAction('createDocumento')"
                                role="button"
                                tabindex="0"
                                data-portal-pending-label="DOCUMENTO"
                                data-spotlight-item="2"
                                x-bind:class="{ 'ring-2 ring-amber-500 bg-white/5': selectedIndex === 2 }"
                            >
                                <x-slot:icon>
                                    <x-heroicon-o-document-text class="h-6 w-6 text-amber-200" />
                                </x-slot:icon>
                                <x-slot:right>
                                    <x-portal.badge color="amber">+ Subir</x-portal.badge>
                                </x-slot:right>
                            </x-portal.row>
                            <div class="portal-spotlight__actions-grid">
                                <button
                                    type="button"
                                    class="portal-spotlight__utility portal-spotlight__utility--indigo"
                                    x-on:click="dispatchUtility('open-portal-help-guide')"
                                    data-spotlight-item="3"
                                    x-bind:class="{ 'ring-2 ring-indigo-400/70 bg-white/5': selectedIndex === 3 }"
                                >
                                    <span class="portal-spotlight__utility-copy">
                                        <span class="portal-spotlight__utility-label">AYUDA</span>
                                        <span class="portal-spotlight__utility-subtitle">Búsqueda rápida</span>
                                    </span>
                                    <span class="portal-spotlight__utility-icon">
                                        <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-4 w-4 text-indigo-200" />
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="portal-spotlight__utility portal-spotlight__utility--rose"
                                    x-on:click="dispatchUtility('toggle-time-clock')"
                                    data-spotlight-item="4"
                                    x-bind:class="{ 'ring-2 ring-rose-400/70 bg-white/5': selectedIndex === 4 }"
                                >
                                    <span class="portal-spotlight__utility-copy">
                                        <span class="portal-spotlight__utility-label">REGISTRO</span>
                                        <span class="portal-spotlight__utility-subtitle">Entrada y salida</span>
                                    </span>
                                    <span class="portal-spotlight__utility-icon">
                                        <x-filament::icon icon="heroicon-o-clock" class="h-4 w-4 text-rose-200" />
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="portal-spotlight__utility portal-spotlight__utility--amber"
                                    x-on:click="dispatchQuickAction('createIncidencia')"
                                    data-portal-pending-label="INCIDENCIA"
                                    data-spotlight-item="5"
                                    x-bind:class="{ 'ring-2 ring-amber-400/70 bg-white/5': selectedIndex === 5 }"
                                >
                                    <span class="portal-spotlight__utility-copy">
                                        <span class="portal-spotlight__utility-label">INCIDENCIA</span>
                                        <span class="portal-spotlight__utility-subtitle">Captura pantalla</span>
                                    </span>
                                    <span class="portal-spotlight__utility-icon">
                                        <x-filament::icon icon="heroicon-o-exclamation-circle" class="h-4 w-4 text-amber-200" />
                                    </span>
                                </button>

                                <button
                                    type="button"
                                    class="portal-spotlight__utility portal-spotlight__utility--emerald"
                                    x-on:click="dispatchUtility('open-announcements')"
                                    data-spotlight-item="6"
                                    x-bind:class="{ 'ring-2 ring-emerald-400/70 bg-white/5': selectedIndex === 6 }"
                                >
                                    <span class="portal-spotlight__utility-copy">
                                        <span class="portal-spotlight__utility-label">AVISOS</span>
                                        <span class="portal-spotlight__utility-subtitle">Ver notificaciones</span>
                                    </span>
                                    <span class="portal-spotlight__utility-icon">
                                        <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-4 w-4 text-emerald-200" />
                                    </span>
                                </button>
                            </div>

                        @elseif (count($spotlightResults) > 0)
                            <p class="text-[11px] uppercase tracking-widest text-white/40">Resultados</p>

                            @foreach ($spotlightResults as $index => $result)
                                <x-portal.row
                                    :title="$result['label']"
                                    :subtitle="$result['sub']"
                                    :href="$result['url']"
                                    class="spotlight-result"
                                    data-spotlight-result
                                    x-bind:data-spotlight-item="((spotlight ?? '').trim().length >= 2) ? {{ $index }} : quickItemsCount + {{ $index }}"
                                    x-bind:class="{ 'ring-2 ring-white/30 bg-white/5': selectedIndex === ((((spotlight ?? '').trim().length >= 2) ? {{ $index }} : quickItemsCount + {{ $index }})) }"
                                >
                                    <x-slot:icon>
                                        @if ($result['type'] === 'cita')
                                            <x-heroicon-o-calendar-days class="h-5 w-5 text-blue-200" />
                                        @elseif ($result['type'] === 'documento')
                                            <x-heroicon-o-document-text class="h-5 w-5 text-amber-200" />
                                        @elseif ($result['type'] === 'ticket' || $result['type'] === 'gasto')
                                            <x-heroicon-o-exclamation-circle class="h-5 w-5 text-red-200" />
                                        @elseif ($result['type'] === 'taxi')
                                            <x-heroicon-o-truck class="h-5 w-5 text-indigo-200" />
                                        @endif
                                    </x-slot:icon>
                                </x-portal.row>
                            @endforeach
                        @else
                            <div class="pb-8 pt-8 text-center">
                                <p class="text-white/40">
                                    No se han encontrado resultados para
                                    "<span class="text-white/60">{{ $spotlight }}</span>"
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-filament-actions::modals />
</div>
