{{-- DOCUMENTOS --}}
<div>
    @if ((int) ($documentosStats['total'] ?? 0) === 0)
        <x-portal.card padding="p-6">
            <p class="text-white/60">Sin documentos registrados.</p>
            <p class="mt-2 text-white/40 text-sm">Usa Spotlight (⌘K) para subir uno.</p>
        </x-portal.card>
    @else
        <div class="space-y-6">
            <div class="relative overflow-visible">
                <div
                    class="stack-panel"
                    x-show="!docsFolder"
                    x-transition:enter="transition duration-300 ease-out"
                    x-transition:enter-start="transform translate-x-0 opacity-100"
                    x-transition:enter-end="transform translate-x-0 opacity-100"
                    x-transition:leave="transition duration-300 ease-in"
                    x-transition:leave-start="transform translate-x-0 opacity-100"
                    x-transition:leave-end="transform -translate-x-full opacity-0"
                >
                    <div class="space-y-4">
                        <div class="sticky top-0 z-10 -mx-2 px-4 pt-1 pb-2">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div
                                        class="tl-breadcrumb inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                                        <a
                                            href="{{ route('mobile-portal') }}"
                                            class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-white/85 hover:text-white"
                                        >
                                            <x-heroicon-o-chevron-left class="h-4 w-4"/>
                                            Documentos
                                        </a>
                                    </div>
                                </div>

                                <div class="flex tl-segment-group items-center gap-1.5 shrink-0">
                                    <button
                                        type="button"
                                        class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                        x-on:click="openSpotlightAction()"
                                        aria-label="Spotlight"
                                    >
                                        <x-heroicon-o-magnifying-glass class="h-4 w-4"/>
                                    </button>

                                    <button
                                        type="button"
                                        class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                        x-bind:class="showDocumentosTopFilters ? 'ring-1 ring-amber-400/40 text-amber-200' : ''"
                                        x-bind:aria-label="showDocumentosTopFilters ? 'Ocultar opciones' : 'Mostrar opciones'"
                                        x-on:click="showDocumentosTopFilters = !showDocumentosTopFilters"
                                    >
                                        <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                                    </button>

                                    <button
                                        type="button"
                                        class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full bg-rose-500/20 text-rose-100 ring-1 ring-rose-500/30"
                                        x-on:click="runCreateAction('createDocumento')"
                                        x-bind:disabled="pendingAction === 'create:createDocumento'"
                                        aria-label="Nuevo documento"
                                    >
                                        <x-heroicon-o-plus class="h-4 w-4"/>
                                    </button>
                                </div>
                            </div>

                            <div class="mt-2 space-y-2" x-show="showDocumentosTopFilters" x-transition.opacity
                                 style="display: none;">
                                <div class="tl-segment-group flex gap-2 overflow-x-auto pb-1">
                                    <button
                                        type="button"
                                        x-on:click="setDocsViewAction('all')"
                                        x-bind:class="docsViewState === 'all' ? 'tl-segment-active ring-1 ring-white/15 bg-white/10' : 'tl-pill-zinc'"
                                        class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                    >
                                        Todos
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="setDocsViewAction('folders')"
                                        x-bind:class="docsViewState === 'folders' ? 'tl-segment-active ring-1 ring-white/15 bg-white/10' : 'tl-pill-zinc'"
                                        class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                    >
                                        Carpetas
                                    </button>
                                    <button
                                        type="button"
                                        x-on:click="setDocsViewAction('recent')"
                                        x-bind:class="docsViewState === 'recent' ? 'tl-segment-active ring-1 ring-blue-400/30 bg-blue-500/10 text-blue-200' : 'tl-pill-zinc'"
                                        class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                    >
                                        Recientes
                                    </button>
                                                                            <button
                                            type="button"
                                            x-on:click="setDocsViewAction('favorites')"
                                            x-bind:class="docsViewState === 'favorites' ? 'tl-segment-active ring-1 ring-amber-400/30 bg-amber-500/10 text-amber-200' : 'tl-pill-zinc'"
                                            class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                        >
                                            Favoritos
                                        </button>
                                </div>

                                <div class="text-[12px] text-white/60">
                                    {{ (int) ($documentosStats['total'] ?? 0) }} PDFs
                                    <span class="text-white/35">·</span>
                                    ★ {{ (int) ($documentosStats['favorites'] ?? 0) }}
                                    <span class="text-white/35">·</span>

                                </div>


                            </div>
                        </div>
                    </div>


                    @if ($docsView === 'all')
                        <div class="space-y-3">
                            <p class="text-white/40 text-[11px] uppercase tracking-widest">Todos</p>
                            @forelse (($documentosTodos ?? []) as $doc)
                            
                                <x-portal.row
                                    wire:key="doc-all-{{ (int) ($doc['id'] ?? 0) }}"
                                    class="tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                    data-portal-no-feedback
                                    :href="$doc['url_view'] ?? ($doc['url'] ?? '#')"
                                    :title="$doc['nombre'] ?? 'Documento'"
                                    :subtitle="$doc['fecha'] ?? null"
                                    :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                    role="button"
                                    tabindex="0"
                                >
                                    <x-slot:icon>
                                        <x-heroicon-o-document-text class="h-5 w-5 text-amber-200"/>
                                    </x-slot:icon>
                                    <x-slot:right>
                                        <div class="flex items-center gap-1.5">
                                            @if (($doc['estado'] ?? 'activo') === 'archivado')
                                                <x-portal.badge color="zinc">Archivado</x-portal.badge>
                                            @endif
                                            <button
                                                type="button"
                                                class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                                x-on:click.stop.prevent="toggleDocumentoFavoritoAction({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})"
                                                aria-label="Favorito"
                                            >
                                                <template x-if="isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                    <x-heroicon-s-star class="h-4 w-4 text-amber-300"/>
                                                </template>
                                                <template x-if="!isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                    <x-heroicon-o-star class="h-4 w-4"/>
                                                </template>
                                            </button>
                                        </div>
                                    </x-slot:right>
                                </x-portal.row>
                            @empty
                                <x-portal.card padding="p-6">
                                    <p class="text-white/60">Sin documentos registrados.</p>
                                </x-portal.card>
                            @endforelse
                        </div>
                    @elseif ($docsView === 'folders')
                        <div class="space-y-3">
                            <p class="text-white/40 text-[11px] uppercase tracking-widest">Carpetas</p>
                            @foreach (($documentosStats['types'] ?? []) as $folder)
                                @php
                                    $folderType = (string) ($folder['type'] ?? 'OTROS');
                                    $folderCount = (int) ($folder['count'] ?? 0);
                                @endphp
                                <x-portal.row
                                    wire:key="doc-folder-{{ strtolower($folderType) }}"
                                    class="min-w-[320px] tl-folder tl-reveal"
                                    data-portal-no-feedback
                                    title="{{ strtoupper($folderType) }}"
                                    subtitle="Documentos PDF"
                                    :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                    wire:click="openDocsFolder('{{ $folderType }}')"
                                    role="button"
                                    tabindex="0"
                                >
                                    <x-slot:icon>
                                        <x-heroicon-o-folder class="h-5 w-5 text-amber-200"/>
                                    </x-slot:icon>
                                    <x-slot:right>
                                        <x-portal.badge color="amber">{{ $folderCount }}</x-portal.badge>
                                    </x-slot:right>
                                </x-portal.row>
                            @endforeach
                        </div>
                    @elseif ($docsView === 'recent')
                        <div class="space-y-3">
                            <p class="text-white/40 text-[11px] uppercase tracking-widest">Recientes</p>
                            @forelse (($documentosRecientes ?? []) as $doc)
                                <x-portal.row
                                    wire:key="doc-recent-{{ (int) ($doc['id'] ?? 0) }}"
                                    class="tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                    data-portal-no-feedback
                                    :href="$doc['url_view'] ?? ($doc['url'] ?? '#')"
                                    :title="$doc['nombre'] ?? 'Documento'"
                                    :subtitle="$doc['fecha'] ?? null"
                                    :iconBg="'bg-blue-500/10 ring-1 ring-blue-500/20'"
                                    role="button"
                                    tabindex="0"
                                >
                                    <x-slot:icon>
                                        <x-heroicon-o-document-text class="h-5 w-5 text-blue-200"/>
                                    </x-slot:icon>
                                    <x-slot:right>
                                        <button
                                            type="button"
                                            class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                            x-on:click.stop.prevent="toggleDocumentoFavoritoAction({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})"
                                            aria-label="Favorito"
                                        >
                                            <template x-if="isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                <x-heroicon-s-star class="h-4 w-4 text-amber-300"/>
                                            </template>
                                            <template x-if="!isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                <x-heroicon-o-star class="h-4 w-4"/>
                                            </template>
                                        </button>
                                    </x-slot:right>
                                </x-portal.row>
                            @empty
                                <x-portal.card padding="p-6">
                                    <p class="text-white/60">Sin documentos recientes.</p>
                                </x-portal.card>
                            @endforelse
                        </div>
                    @elseif ($docsView === 'favorites')
                        <div class="space-y-3">
                            <p class="text-white/40 text-[11px] uppercase tracking-widest">Favoritos</p>
                            @forelse (($documentosFavoritos ?? []) as $doc)
                                <x-portal.row
                                    wire:key="doc-favorites-{{ (int) ($doc['id'] ?? 0) }}"
                                    class="tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                    data-portal-no-feedback
                                    :href="$doc['url_view'] ?? ($doc['url'] ?? '#')"
                                    :title="$doc['nombre'] ?? 'Documento'"
                                    :subtitle="$doc['fecha'] ?? null"
                                    :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                    role="button"
                                    tabindex="0"
                                >
                                    <x-slot:icon>
                                        <x-heroicon-o-document-text class="h-5 w-5 text-amber-200"/>
                                    </x-slot:icon>
                                    <x-slot:right>
                                        <x-portal.badge color="amber">★</x-portal.badge>
                                    </x-slot:right>
                                </x-portal.row>
                            @empty
                                <x-portal.card padding="p-6">
                                    <p class="text-white/60">Sin documentos favoritos.</p>
                                </x-portal.card>
                            @endforelse
                        </div>
                    @else
                        <div class="space-y-6">
                            @if (count($documentosFavoritos ?? []) > 0)
                                <div class="space-y-3">
                                    <p class="text-white/40 text-[11px] uppercase tracking-widest">Favoritos</p>
                                    <div class="flex gap-3 overflow-x-auto pb-1">
                                        @foreach ($documentosFavoritos as $doc)
                                            <x-portal.row
                                                wire:key="doc-favorite-{{ (int) ($doc['id'] ?? 0) }}"
                                                class="min-w-[320px] tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2 tl-doc-featured tl-interactive tl-pop"
                                                data-portal-no-feedback
                                                :href="$doc['url_view'] ?? ($doc['url'] ?? '#')"
                                                :title="$doc['nombre'] ?? 'Documento'"
                                                :subtitle="$doc['fecha'] ?? null"
                                                :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                                role="button"
                                                tabindex="0"
                                            >
                                                <x-slot:icon>
                                                    <x-heroicon-o-document-text class="h-5 w-5 text-amber-200"/>
                                                </x-slot:icon>
                                                <x-slot:right>
                                                    <x-portal.badge color="amber">★</x-portal.badge>
                                                </x-slot:right>
                                            </x-portal.row>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="space-y-3">
                                <p class="text-white/40 text-[11px] uppercase tracking-widest">Recientes</p>
                                @forelse (($documentosRecientes ?? []) as $doc)
                                    <x-portal.row
                                        wire:key="doc-home-recent-{{ (int) ($doc['id'] ?? 0) }}"
                                        class="tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                        data-portal-no-feedback
                                        :href="$doc['url_view'] ?? ($doc['url'] ?? '#')"
                                        :title="$doc['nombre'] ?? 'Documento'"
                                        :subtitle="$doc['fecha'] ?? null"
                                        :iconBg="'bg-blue-500/10 ring-1 ring-blue-500/20'"
                                        role="button"
                                        tabindex="0"
                                    >
                                        <x-slot:icon>
                                            <x-heroicon-o-document-text class="h-5 w-5 text-blue-200"/>
                                        </x-slot:icon>
                                        <x-slot:right>
                                            <button
                                                type="button"
                                                class="
                                                tl-glass-dynamic tl-cursor-react tl-s3 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2 "
                                                x-on:click.stop.prevent="toggleDocumentoFavoritoAction({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})"
                                                aria-label="Favorito"
                                            >
                                                <template x-if="isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                    <x-heroicon-s-star class="h-4 w-4 text-amber-300"/>
                                                </template>
                                                <template x-if="!isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                    <x-heroicon-o-star class="h-4 w-4"/>
                                                </template>
                                            </button>
                                        </x-slot:right>
                                    </x-portal.row>
                                @empty
                                    <x-portal.card padding="p-6">
                                        <p class="text-white/60">Sin documentos recientes.</p>
                                    </x-portal.card>
                                @endforelse
                            </div>

                            <div class="space-y-3">
                                <p class="text-white/40 text-[11px] uppercase tracking-widest">Carpetas</p>
                                @forelse (($documentosStats['types'] ?? []) as $folder)
                                    @php
                                        $folderType = (string) ($folder['type'] ?? 'OTROS');
                                        $folderCount = (int) ($folder['count'] ?? 0);
                                    @endphp
                                    <x-portal.row
                                        wire:key="doc-home-folder-{{ strtolower($folderType) }}"
                                        class="tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                        data-portal-no-feedback
                                        title="{{ strtoupper($folderType) }}"
                                        subtitle="Documentos PDF"
                                        :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                        wire:click="openDocsFolder('{{ $folderType }}')"
                                        role="button"
                                        tabindex="0"
                                    >
                                        <x-slot:icon>
                                            <x-heroicon-o-folder class="h-5 w-5 text-amber-200"/>
                                        </x-slot:icon>
                                        <x-slot:right>
                                            <x-portal.badge color="amber">{{ $folderCount }}</x-portal.badge>
                                        </x-slot:right>
                                    </x-portal.row>
                                @empty
                                    <x-portal.card padding="p-6">
                                        <p class="text-white/60">Sin carpetas disponibles.</p>
                                    </x-portal.card>
                                @endforelse
                            </div>
                        </div>
                    @endif
                </div>

                <div
                    class="stack-panel"
                    x-show="docsFolder"
                    x-transition:enter="transition duration-300 ease-out"
                    x-transition:enter-start="transform translate-x-full opacity-0"
                    x-transition:enter-end="transform translate-x-0 opacity-100"
                    x-transition:leave="transition duration-300 ease-in"
                    x-transition:leave-start="transform translate-x-0 opacity-100"
                    x-transition:leave-end="transform translate-x-full opacity-0"
                >
                    <div class="space-y-4">
                        @if (! $selectedDocumento)
                            <div class="sticky top-0 z-10 -mx-2 px-2 pt-1 pb-2">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div
                                            class="tl-segment-group inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                                            <button
                                                type="button"
                                                class="tl-segment tl-interactive    text-xs font-semibold uppercase tracking-widest text-white/85 hover:text-white"
                                                wire:click="closeDocsFolder"
                                            >
                                                Documentos
                                            </button>
                                            <span class="text-white/30">›</span>
                                            <span
                                                class="text-xs font-semibold uppercase tracking-widest text-white/85">{{ strtoupper((string) $docsFolder) }}</span>
                                        </div>
                                    </div>
                                    <div class="flex tl-segment-group items-center gap-1.5 shrink-0">
                                        <button
                                            type="button"
                                            class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                            x-bind:class="showDocumentosTopFilters ? 'ring-1 ring-amber-400/40 text-amber-200' : ''"
                                            x-bind:aria-label="showDocumentosTopFilters ? 'Ocultar opciones' : 'Mostrar opciones'"
                                            x-on:click="showDocumentosTopFilters = !showDocumentosTopFilters"
                                        >
                                            <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                                        </button>

                                        <button
                                            type="button"
                                            class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full bg-rose-500/20 text-rose-100 ring-1 ring-rose-500/30"
                                            x-on:click="runCreateAction('createDocumento')"
                                            x-bind:disabled="pendingAction === 'create:createDocumento'"
                                            aria-label="Nuevo documento"
                                        >
                                            <x-heroicon-o-plus class="h-4 w-4"/>
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-2 space-y-2" x-show="showDocumentosTopFilters" x-transition.opacity
                                     style="display: none;">
                                    <div class="flex gap-2 overflow-x-auto pb-1">
                                        <button
                                            type="button"
                                            x-on:click="setDocsSegmentAction('all')"
                                            x-bind:class="docsSegmentState === 'all' ? 'tl-segment-active ring-1 ring-white/15 bg-white/10' : 'tl-pill-zinc'"
                                            class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                        >
                                            Todos
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="setDocsSegmentAction('recent')"
                                            x-bind:class="docsSegmentState === 'recent' ? 'tl-segment-active ring-1 ring-blue-400/30 bg-blue-500/10 text-blue-200' : 'tl-pill-zinc'"
                                            class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                        >
                                            Recientes
                                        </button>
                                        <button
                                            type="button"
                                            x-on:click="setDocsSegmentAction('favorites')"
                                            x-bind:class="docsSegmentState === 'favorites' ? 'tl-segment-active ring-1 ring-amber-400/30 bg-amber-500/10 text-amber-200' : 'tl-pill-zinc'"
                                            class="tl-pill tl-segment px-4 py-1.5 text-xs font-medium"
                                        >
                                            Favoritos
                                        </button>
                                    </div>

                                    <div class="text-[12px] text-white/60">
                                        {{ (int) ($documentosCarpetaStats['count'] ?? 0) }} PDFs
                                        <span class="text-white/35">·</span>
                                        ★ {{ (int) ($documentosCarpetaStats['favorites'] ?? 0) }}
                                        <span class="text-white/35">·</span>
                                        <span
                                            class="text-white/45">{{ $docsSegment === 'favorites' ? 'Favoritos' : ($docsSegment === 'recent' ? 'Recientes' : 'Todos') }}</span>
                                        <span class="text-white/35">·</span>
                                        <span
                                            class="text-white/45">{{ $docsOrder === 'name' ? 'Nombre' : ($docsOrder === 'reference' ? 'Referencia' : 'Fecha') }}</span>
                                    </div>

                                    @php
                                        $shouldShowSegmentChip = in_array($docsSegment, ['favorites', 'recent'], true);
                                        $shouldShowOrderChip = $docsOrder !== 'recent';
                                    @endphp

                                </div>
                            </div>
                        @endif

                        @if ($selectedDocumento)
                            <div class="mt-6 flex justify-center">
                                <div class="w-full max-w-3xl">
                                    <div class="sticky top-0 z-10 -mx-2 px-2 pt-1 pb-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <div class="min-w-0">
                                                <div
                                                    class="tl-breadcrumb inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                                                    <button
                                                        type="button"
                                                        class="text-xs font-semibold uppercase tracking-widest text-white/85 hover:text-white"
                                                        wire:click="closeDocumento"
                                                    >
                                                        Documentos
                                                    </button>
                                                    <span class="text-white/30">›</span>
                                                    <span
                                                        class="text-xs font-semibold uppercase tracking-widest text-white/85">{{ strtoupper((string) $docsFolder) }}</span>
                                                </div>
                                            </div>

                                            <div class="tl-segment-group flex items-center gap-1.5 shrink-0">
                                                <button
                                                    type="button"
                                                    class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                                    wire:click="editDocumento"
                                                    aria-label="Editar"
                                                >
                                                    <x-heroicon-o-pencil-square class="h-4 w-4"/>
                                                </button>

                                                <a
                                                    class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                                    href="{{ $selectedDocumento['file_url'] ?? ($selectedDocumento['url_view'] ?? '#') }}"
                                                    target="_blank"
                                                    aria-label="Ver fichero"
                                                >
                                                    <x-heroicon-o-eye class="h-4 w-4"/>
                                                </a>

                                                @if (! empty($selectedDocumento['file_url']))
                                                    <a
                                                        class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                                        href="{{ $selectedDocumento['file_url'] }}"
                                                        download
                                                        aria-label="Descargar"
                                                    >
                                                        <x-heroicon-o-arrow-down-tray class="h-4 w-4"/>
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                    </div>

                                    <div x-show="documentMode === 'view'" x-transition.opacity>
                                        <x-portal.card padding="p-6">
                                            <div class="flex flex-col gap-4">
                                                <div class="min-w-0">
                                                    <p class="text-xl font-semibold text-white/90 truncate">{{ $selectedDocumento['nombre'] ?? 'Documento' }}</p>
                                                    <p class="mt-1 text-sm text-white/45">{{ $selectedDocumento['fecha'] ?? '—' }}</p>
                                                </div>

                                                <div class="tl-inner-surface rounded-3xl p-5 sm:p-6 tl-interactive">
                                                    <p class="text-white/70 text-sm font-semibold">Resumen del
                                                        documento</p>

                                                    <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                                        <div>
                                                            <dt class="text-white/40 text-[11px] uppercase tracking-widest">
                                                                Titulo
                                                            </dt>
                                                            <dd class="mt-1 text-white/85 font-semibold break-words">{{ $selectedDocumento['nombre'] ?? '—' }}</dd>
                                                        </div>

                                                        <div>
                                                            <dt class="text-white/40 text-[11px] uppercase tracking-widest">
                                                                Tipo
                                                            </dt>
                                                            <dd class="mt-1 text-white/85 font-semibold">{{ $selectedDocumento['tipo'] ?? '—' }}</dd>
                                                        </div>

                                                        <div>
                                                            <dt class="text-white/40 text-[11px] uppercase tracking-widest">
                                                                Referencia
                                                            </dt>
                                                            <dd class="mt-1 text-white/80 font-medium break-words">{{ $selectedDocumento['referencia'] ?? '—' }}</dd>
                                                        </div>

                                                        <div>
                                                            <dt class="text-white/40 text-[11px] uppercase tracking-widest">
                                                                Favorito
                                                            </dt>
                                                            <dd class="mt-1 text-white/85 font-semibold">
                                                                @if (($selectedDocumento['favorito'] ?? false) === true)
                                                                    ★
                                                                @else
                                                                    —
                                                                @endif
                                                            </dd>
                                                        </div>

                                                        <div class="sm:col-span-2">
                                                            <dt class="text-white/40 text-[11px] uppercase tracking-widest">
                                                                Nombre fichero
                                                            </dt>
                                                            <dd class="mt-1 text-white/80 font-medium break-all">{{ $selectedDocumento['archivo'] ?? '—' }}</dd>
                                                        </div>

                                                        <div class="sm:col-span-2">
                                                            <dt class="text-white/40 text-[11px] uppercase tracking-widest">
                                                                Notas
                                                            </dt>
                                                            <dd class="mt-1 text-white/70 text-sm whitespace-pre-line break-words">{{ $selectedDocumento['notas'] ?? '—' }}</dd>
                                                        </div>
                                                    </dl>
                                                </div>
                                            </div>
                                        </x-portal.card>
                                    </div>

                                    <div x-show="documentMode === 'edit'" x-transition.opacity>
                                        <x-portal.card padding="p-6">
                                            <form wire:submit.prevent="saveDocumento" class="space-y-4">
                                                {{ $this->form }}

                                                <div class="flex items-center justify-end gap-2 pt-2">
                                                    <x-portal.button variant="ghost" type="button"
                                                                     wire:click="cancelEditDocumento">
                                                        Cancelar
                                                    </x-portal.button>

                                                    <x-portal.button variant="primary" type="submit">
                                                        Guardar
                                                    </x-portal.button>
                                                </div>
                                            </form>
                                        </x-portal.card>
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if (! $selectedDocumento)
                            @if (count($documentosCarpeta ?? []) === 0)
                                <div class="mt-4">
                                    <x-portal.card padding="p-6">
                                        <p class="text-white/60">Sin documentos en esta carpeta.</p>
                                        <p class="mt-2 text-white/40 text-sm">Usa Spotlight (⌘K) para subir uno.</p>
                                    </x-portal.card>
                                </div>
                            @else
                                <div class="mt-4 space-y-3" x-show="!selectedDocumentId" x-transition.opacity>
                                    @foreach ($documentosCarpeta as $doc)
                                        <x-portal.row
                                            class="tl-doc tl-reveal"
                                            data-portal-no-feedback
                                            :href="$doc['url_view'] ?? ($doc['url'] ?? '#')"
                                            :title="$doc['nombre'] ?? 'Documento'"
                                            :subtitle="(($doc['fecha'] ?? null) && ($doc['referencia'] ?? null)) ? (($doc['fecha'] ?? '') . ' · Ref: ' . ($doc['referencia'] ?? '')) : ($doc['fecha'] ?? null)"
                                            :iconBg="'bg-amber-500/10 ring-1 ring-amber-500/20'"
                                            role="button"
                                            tabindex="0"
                                        >
                                            <x-slot:icon>
                                                <x-heroicon-o-document-text class="h-5 w-5 text-amber-200"/>
                                            </x-slot:icon>
                                            <x-slot:right>
                                                <div class="flex items-center gap-1.5" x-data="{ open: false }"
                                                     x-on:keydown.escape.window="open = false">
                                                    <button
                                                        type="button"
                                                        class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                                        x-on:click.stop.prevent="toggleDocumentoFavoritoAction({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})"
                                                        aria-label="Favorito"
                                                    >
                                                        <template x-if="isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                            <x-heroicon-s-star class="h-4 w-4 text-amber-300"/>
                                                        </template>
                                                        <template x-if="!isDocumentoFavorito({{ (int) ($doc['id'] ?? 0) }}, {{ ($doc['favorito'] ?? false) ? 'true' : 'false' }})">
                                                            <x-heroicon-o-star class="h-4 w-4"/>
                                                        </template>
                                                    </button>

                                                    <button
                                                        type="button"
                                                        class="tl-s3  tl-interactive  inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                                        x-on:click.stop.prevent="docActionItem = @js($doc); showDocActions = true"
                                                        aria-label="Mas acciones"
                                                    >
                                                        <x-heroicon-o-ellipsis-vertical class="h-4 w-4"/>
                                                    </button>
                                                </div>
                                            </x-slot:right>
                                        </x-portal.row>
                                    @endforeach
                                </div>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
@endif
