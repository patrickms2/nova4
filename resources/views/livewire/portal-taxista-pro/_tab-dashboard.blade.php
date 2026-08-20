{{-- DASHBOARD --}}
<div>
    <div class="space-y-6">
        <x-portal.card padding="p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <p class="text-white/60 text-sm">Taxilanz</p>
                    <p class="mt-1 text-xl font-semibold text-white/90 truncate">{{ $taxista?->name ?? 'Taxista' }}</p>
                    <p class="mt-1 text-white/40 text-sm">Spotlight (⌘K) para buscar y crear.</p>
                </div>
            </div>
        </x-portal.card>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          
                    <x-portal.card padding="p-6" class="tl-s2 tl-s2-hover  tl-interactive" role="button" tabindex="0">
                            <form action="https://admin.taxilanz.com/portal/logout" method="post"><input type="hidden" name="_token" value="890uyE5GBWXH4hHe7Sm26pQLP6mgn3C7frG86fNR" autocomplete="off">
        <button class="tl-s1" type="submit">
            <svg class="fi-icon fi-size-md" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" data-slot="icon">
  <path fill-rule="evenodd" d="M3 4.25A2.25 2.25 0 0 1 5.25 2h5.5A2.25 2.25 0 0 1 13 4.25v2a.75.75 0 0 1-1.5 0v-2a.75.75 0 0 0-.75-.75h-5.5a.75.75 0 0 0-.75.75v11.5c0 .414.336.75.75.75h5.5a.75.75 0 0 0 .75-.75v-2a.75.75 0 0 1 1.5 0v2A2.25 2.25 0 0 1 10.75 18h-5.5A2.25 2.25 0 0 1 3 15.75V4.25Z" clip-rule="evenodd"></path>
  <path fill-rule="evenodd" d="M19 10a.75.75 0 0 0-.75-.75H8.704l1.048-.943a.75.75 0 1 0-1.004-1.114l-2.5 2.25a.75.75 0 0 0 0 1.114l2.5 2.25a.75.75 0 1 0 1.004-1.114l-1.048-.943h9.546A.75.75 0 0 0 19 10Z" clip-rule="evenodd"></path>
</svg>            
            <span class="fi-dropdown-list-item-label">
                Salir            </span>

                    </button>

        </form> 

                <p class="text-white/60 text-sm">Salir</p>
                <p class="mt-2 text-3xl font-semibold text-white/90">{{ $stats['documentos'] ?? 0 }}</p>
            </x-portal.card>
            <x-portal.card padding="p-6" class="tl-s2 tl-s2-hover  tl-interactive" role="button" tabindex="0"
                           x-on:click="switchTabAction('documentos')">
                <p class="text-white/60 text-sm">Documentos</p>
                <p class="mt-2 text-3xl font-semibold text-white/90">{{ $stats['documentos'] ?? 0 }}</p>
            </x-portal.card>

            <x-portal.card padding="p-6" class="tl-s2 tl-s2-hover  tl-interactive" role="button" tabindex="0"
                           x-on:click="switchTabAction('citas')">
                <p class="text-white/60 text-sm">Citas</p>
                <p class="mt-2 text-3xl font-semibold text-white/90">{{ $stats['citas'] ?? 0 }}</p>
            </x-portal.card>

            <x-portal.card padding="p-6" class="tl-s2 tl-s2-hover  tl-interactive" role="button" tabindex="0"
                           x-on:click="switchTabAction('tickets')">
                <p class="text-white/60 text-sm">Tickets</p>
                <p class="mt-2 text-3xl font-semibold text-white/90">{{ $stats['tickets'] ?? 0 }}</p>
            </x-portal.card>

            <x-portal.card padding="p-6" class="tl-s2 tl-s2-hover  tl-interactive" role="button" tabindex="0"
                           x-on:click="openAnnouncementsAction()">
                <p class="text-white/60 text-sm">Avisos</p>
                <p class="mt-2 text-3xl font-semibold text-white/90">{{ $stats['anuncios'] ?? 0 }}</p>
            </x-portal.card>
        </div>

        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-white/40 text-[11px] uppercase tracking-widest">Documentos recientes</p>
                <x-portal.button variant="ghost" x-on:click="openSpotlightAction()">Spotlight</x-portal.button>
            </div>

            @if (count($documentosFavoritos ?? []) > 0)
                <div class="space-y-3">
                    <p class="text-white/40 text-[11px] uppercase tracking-widest">Favoritos</p>
                    <div class="flex gap-3 overflow-x-auto pb-1">
                        @foreach ($documentosFavoritos as $doc)
                            <x-portal.row
                                class="min-w-[320px]"
                                data-portal-no-feedback
                                :title="$doc['nombre'] ?? 'Documento'"
                                :subtitle="$doc['fecha'] ?? null"
                                :href="$doc['url_view'] ?? ($doc['url'] ?? null)"
                                :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                            >
                                <x-slot:icon>
                                    <x-heroicon-o-document-text class="h-5 w-5 text-amber-200"/>
                                </x-slot:icon>
                                <x-slot:right>
                                    <div class="flex items-center gap-2">
                                        <x-portal.badge color="amber">★</x-portal.badge>
                                        <x-portal.badge color="amber">PDF</x-portal.badge>
                                    </div>
                                </x-slot:right>
                            </x-portal.row>
                        @endforeach
                    </div>
                </div>
            @endif

            @forelse ($documentosRecientes as $doc)
                <x-portal.row
                    data-portal-no-feedback
                    :title="$doc['nombre'] ?? 'Documento'"
                    :subtitle="$doc['fecha'] ?? null"
                    :href="$doc['url_view'] ?? ($doc['url'] ?? null)"
                    :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                >
                    <x-slot:icon>
                        <x-heroicon-o-document-text class="h-5 w-5 text-amber-200"/>
                    </x-slot:icon>
                    <x-slot:right>
                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="glass px-2 py-2"
                                x-on:click.stop.prevent="toggleDocumentoFavoritoAction({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})"
                                aria-label="Marcar como favorito"
                            >
                                <template x-if="isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                    <x-heroicon-s-star class="h-4 w-4 text-amber-300"/>
                                </template>
                                <template x-if="!isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                    <x-heroicon-o-star class="h-4 w-4 text-white/50"/>
                                </template>
                            </button>

                            <x-portal.badge color="amber">PDF</x-portal.badge>
                        </div>
                    </x-slot:right>
                </x-portal.row>
            @empty
                <x-portal.card padding="p-6">
                    <p class="text-white/60">Aún no hay documentos.</p>
                    <p class="mt-2 text-white/40 text-sm">Usa Spotlight (⌘K) para subir el primero.</p>
                </x-portal.card>
            @endforelse
        </div>

        <x-portal.card padding="p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-white/60 text-sm">Estado</p>
                        <p class="mt-1 text-white/40 text-sm">Visible para Operaciones en tiempo real.</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="glass px-3 py-2"
                            x-on:click="toggleOnlineAction()"
                            x-bind:disabled="pendingOnline"
                            x-bind:aria-label="isOnline ? 'Online' : 'Offline'"
                            x-bind:title="isOnline ? 'Online' : 'Offline'"
                        >
                            <span class="inline-flex items-center gap-2">
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :class="isOnline ? 'bg-emerald-400' : 'bg-rose-400'"
                                ></span>
                            </span>
                        </button>

                        <span
                            x-show="trackingActive"
                            x-transition
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-1 text-[10px] font-semibold text-emerald-400"
                            title="GPS activo — enviando ubicación a Traccar"
                        >
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            GPS
                        </span>
                    </div>
                </div>

                <div class="flex gap-3">
                    <x-portal.button variant="primary" x-on:click="shareLocation()" x-bind:disabled="pendingShareLocation">
                        <span x-text="pendingShareLocation ? 'Compartiendo…' : 'Compartir ubicacion'"></span>
                    </x-portal.button>
                    <x-portal.button variant="ghost" as="a"
                                     href="{{ \App\Filament\Portal\Pages\TaxistaChats::getUrl(panel: 'portal') }}">
                        Chat
                    </x-portal.button>
                </div>
            </div>
        </x-portal.card>
    </div>
</div>
