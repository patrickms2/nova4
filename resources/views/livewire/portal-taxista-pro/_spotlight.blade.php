<div
    x-show="showSpotlight"
    x-transition.opacity
    class="fixed inset-0 z-50"
>
    <div class="absolute inset-0 bg-black/75" @click="$wire.closeSpotlight()"></div>

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
                        x-init="$watch('showSpotlight', value => value && $nextTick(() => $refs.spotlightInput?.focus()))"
                    />
                    <x-portal.button
                        variant="ghost"
                        x-on:click="$wire.closeSpotlight()"
                        data-portal-pending-label="Cerrar buscador"
                    >
                        ESC
                    </x-portal.button>
                </div>

                <div class="mt-4 space-y-3">
                    @if (strlen($spotlight ?? '') < 2)
                        <p class="text-[11px] uppercase tracking-widest text-white/40">Acciones rápidas</p>

                        <x-portal.row
                            title="Nueva cita"
                            subtitle="Solicitar cita"
                            :iconBg="'bg-blue-500/10 ring-1 ring-blue-500/20'"
                            wire:click="runQuickAction('createCita')"
                            role="button"
                            tabindex="0"
                            data-portal-pending-label="Nueva cita"
                        >
                            <x-slot:icon>
                                <x-heroicon-o-calendar-days class="h-5 w-5 text-blue-200" />
                            </x-slot:icon>
                            <x-slot:right>
                                <x-portal.badge color="blue">+ Crear</x-portal.badge>
                            </x-slot:right>
                        </x-portal.row>

                        <x-portal.row
                            title="Nuevo ticket"
                            subtitle="Incidencia o duda"
                            :iconBg="'bg-red-500/10 ring-1 ring-red-500/20'"
                            wire:click="runQuickAction('createTicket')"
                            role="button"
                            tabindex="0"
                            data-portal-pending-label="Nuevo ticket"
                        >
                            <x-slot:icon>
                                <x-heroicon-o-exclamation-circle class="h-5 w-5 text-red-200" />
                            </x-slot:icon>
                            <x-slot:right>
                                <x-portal.badge color="red">+ Crear</x-portal.badge>
                            </x-slot:right>
                        </x-portal.row>

                        <x-portal.row
                            title="Nuevo documento"
                            subtitle="Subir documentación"
                            :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                            wire:click="runQuickAction('createDocumento')"
                            role="button"
                            tabindex="0"
                            data-portal-pending-label="Nuevo documento"
                        >
                            <x-slot:icon>
                                <x-heroicon-o-document-text class="h-5 w-5 text-amber-200" />
                            </x-slot:icon>
                            <x-slot:right>
                                <x-portal.badge color="amber">+ Subir</x-portal.badge>
                            </x-slot:right>
                        </x-portal.row>

                        <x-portal.row
                            title="Incidencia"
                            subtitle="Soporte urgente con captura"
                            :iconBg="'bg-red-500/10 ring-1 ring-red-500/20'"
                            wire:click="runQuickAction('createIncidencia')"
                            role="button"
                            tabindex="0"
                            data-portal-pending-label="Nueva incidencia"
                        >
                            <x-slot:icon>
                                <x-heroicon-o-exclamation-circle class="h-5 w-5 text-red-200" />
                            </x-slot:icon>
                            <x-slot:right>
                                <x-portal.badge color="red">+ Crear</x-portal.badge>
                            </x-slot:right>
                        </x-portal.row>
                    @elseif (count($spotlightResults) > 0)
                        <p class="text-[11px] uppercase tracking-widest text-white/40">Resultados</p>

                        @foreach ($spotlightResults as $result)
                            <x-portal.row
                                :title="$result['label']"
                                :subtitle="$result['sub'] ?? null"
                                :href="$result['url'] ?? null"
                                class="spotlight-result"
                            >
                                <x-slot:icon>
                                    @if ($result['type'] === 'cita')
                                        <x-heroicon-o-calendar-days class="h-5 w-5 text-blue-200" />
                                    @elseif ($result['type'] === 'documento')
                                        <x-heroicon-o-document-text class="h-5 w-5 text-amber-200" />
                                    @elseif (in_array($result['type'], ['ticket', 'gasto'], true))
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

    <x-filament-actions::modals />
</div>
