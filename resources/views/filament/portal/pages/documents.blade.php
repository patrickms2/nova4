<x-filament-panels::page>
    <div class="portal-documents-page">
        <div class="flex items-center justify-end mb-2">
            <x-employee-help-popup page="portal-documents" />
        </div>
        <div
            x-data="{
                selectedTipoId: $wire.entangle('selectedTipoId'),
                direction: 'forward',
                init() {
                    this.$watch('selectedTipoId', (value, oldValue) => {
                        if (oldValue === undefined || oldValue === value) {
                            return;
                        }

                        this.direction = value === null ? 'back' : 'forward';
                    });
                },
            }"
            x-bind:data-direction="direction"
            class="portal-documents-switch"
        >
            <section
                x-cloak
                x-show="selectedTipoId === null"
                x-transition:enter="portal-switch-enter"
                x-transition:enter-start="portal-switch-enter-start"
                x-transition:enter-end="portal-switch-enter-end"
                x-transition:leave="portal-switch-leave"
                x-transition:leave-start="portal-switch-leave-start"
                x-transition:leave-end="portal-switch-leave-end"
                class="portal-view-surface portal-view-surface--folders"
            >
                <div class="portal-documents-summary" aria-label="Resumen de documentos">
                    <article class="portal-documents-summary__item">
                        <p class="portal-documents-summary__label">Documentos</p>
                        <p class="portal-documents-summary__value">{{ $this->documentsSummary['total'] }}</p>
                    </article>
                    <article class="portal-documents-summary__item">
                        <p class="portal-documents-summary__label">Carpetas</p>
                        <p class="portal-documents-summary__value">{{ $this->documentsSummary['folders'] }}</p>
                    </article>
                    <article class="portal-documents-summary__item">
                        <p class="portal-documents-summary__label">Favoritos</p>
                        <p class="portal-documents-summary__value">{{ $this->documentsSummary['favorites'] }}</p>
                    </article>
                </div>

                <div class="portal-documents-browser-head">
                    <div class="portal-documents-browser-copy">
                        <p class="portal-documents-browser-eyebrow">Explorador</p>
                        <h3 class="portal-documents-browser-title">Carpetas por tipo</h3>
                    </div>

                    <div class="portal-documents-mode-switch" role="tablist" aria-label="Modo de documentos">
                        <button
                            type="button"
                            class="portal-mode-btn {{ $this->showFavoritesRail ? 'is-active' : '' }}"
                            wire:click="openFavoritesRail"
                            aria-pressed="{{ $this->showFavoritesRail ? 'true' : 'false' }}"
                            title="Favoritos"
                        >
                            <x-filament::icon icon="heroicon-o-star" class="h-5 w-5" />
                        </button>
                        <button
                            type="button"
                            class="portal-mode-btn {{ !$this->showFavoritesRail ? 'is-active' : '' }}"
                            wire:click="closeFavoritesRail"
                            aria-pressed="{{ !$this->showFavoritesRail ? 'true' : 'false' }}"
                            title="Carpetas"
                        >
                            <x-filament::icon icon="heroicon-o-folder" class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                @if ($this->favoriteDocuments->isNotEmpty())
                    <section class="portal-documents-favorites" aria-label="Documentos favoritos">
                        <div class="portal-documents-favorites__head">
                            <p class="portal-documents-favorites__title">
                                Favoritos recientes
                                <span class="portal-documents-favorites__count">{{ $this->favoriteDocuments->count() }}</span>
                            </p>
                            <button
                                type="button"
                                class="portal-documents-favorites__toggle"
                                wire:click="{{ $this->showFavoritesRail ? 'closeFavoritesRail' : 'openFavoritesRail' }}"
                            >
                                {{ $this->showFavoritesRail ? 'Ocultar' : 'Mostrar' }}
                            </button>
                        </div>

                        @if ($this->showFavoritesRail)
                        <div class="portal-favorites-rail" aria-label="Favoritos recientes">
                            @foreach ($this->favoriteDocuments as $doc)
                                <button
                                    type="button"
                                    class="portal-favorite-doc-card"
                                    wire:key="portal-favorite-doc-{{ $doc['id'] }}"
                                    wire:click="openDocument({{ $doc['id'] }})"
                                    x-on:dblclick="$wire.openDocument({{ $doc['id'] }})"
                                >
                                    <p class="portal-favorite-doc-card__title">{{ $doc['title'] }}</p>
                                    <p class="portal-favorite-doc-card__meta">{{ $doc['date'] }}</p>
                                    <p class="portal-favorite-doc-card__footer">
                                        <span class="portal-row-tile__badge portal-badge-info">{{ $doc['type'] }}</span>
                                        <span class="portal-row-tile__badge portal-badge-gray">{{ $doc['reference'] }}</span>
                                    </p>
                                </button>
                            @endforeach
                        </div>
                        @endif
                    </section>
                @endif

                <div class="portal-folders-grid">
                    @forelse ($this->folderTypes as $index => $folder)
                        <button
                            type="button"
                            class="portal-folder-card"
                            style="--folder-stagger: {{ $index }};"
                            wire:key="portal-folder-{{ $folder['id'] }}"
                            wire:click="openFolder({{ $folder['id'] }})"
                        >
                            <div class="portal-folder-card__icon">
                                <x-filament::icon
                                    icon="heroicon-o-folder"
                                    class="h-6 w-6"
                                />
                            </div>
                            <div class="portal-folder-card__content">
                                <p class="portal-folder-card__title">{{ $folder['name'] }}</p>
                                <p class="portal-folder-card__meta">Tipo de documento</p>
                            </div>
                            <span class="portal-folder-card__count">{{ $folder['count'] }}</span>
                            <div class="portal-folder-card__chevron">
                                <x-filament::icon icon="heroicon-o-chevron-right" class="h-5 w-5" />
                            </div>
                        </button>
                    @empty
                        <div class="portal-folder-empty">
                            No hay carpetas con documentos todavía.
                        </div>
                    @endforelse
                </div>
            </section>

            <section
                x-cloak
                x-show="selectedTipoId !== null"
                x-transition:enter="portal-switch-enter"
                x-transition:enter-start="portal-switch-enter-start"
                x-transition:enter-end="portal-switch-enter-end"
                x-transition:leave="portal-switch-leave"
                x-transition:leave-start="portal-switch-leave-start"
                x-transition:leave-end="portal-switch-leave-end"
                class="portal-view-surface portal-view-surface--documents"
            >
                @if ($this->selectedFolder)
                    <div class="portal-folder-breadcrumb-chip" aria-label="Ruta actual de documentos">
                        <x-filament::icon icon="heroicon-o-folder" class="h-4 w-4" />
                        <span>Documentos</span>
                        <x-filament::icon icon="heroicon-o-chevron-right" class="h-3.5 w-3.5" />
                        <strong>{{ $this->selectedFolder['name'] }}</strong>
                    </div>
                @endif
                {{ $this->table }}
            </section>
        </div>
    </div>
</x-filament-panels::page>
