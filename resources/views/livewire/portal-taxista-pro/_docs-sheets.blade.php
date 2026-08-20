<div
    x-show="!docsFolder && showDocsHomeControls"
    x-transition.opacity
    class="fixed inset-0 z-50"
    x-on:keydown.escape.window="showDocsHomeControls = false"
    style="display: none;"
>
    <div
        class="absolute inset-0 bg-black/75"
        x-on:click="showDocsHomeControls = false"
    ></div>

    <div class="absolute inset-0 flex items-center justify-center px-4 sm:px-6">
        <div
            class="w-full max-w-5xl"
            x-on:click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-3 opacity-0"
            x-transition:enter-end="transform translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="transform translate-y-0 opacity-100"
            x-transition:leave-end="transform translate-y-3 opacity-0"
        >
            <div class="glass rounded-3xl border border-white/10 bg-black/50 backdrop-blur p-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-white/80 text-sm font-semibold">Filtrar y ordenar</p>
                    <button
                        type="button"
                        class="glass-hover inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                        x-on:click="showDocsHomeControls = false"
                        aria-label="Cerrar"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4"/>
                    </button>
                </div>

                <div class="mt-3 space-y-3">
                    <div>
                        <p class="text-white/40 text-[11px] uppercase tracking-widest">Segmento</p>
                        <div class="mt-2 inline-flex w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsSegmentAction('all'); showDocsHomeControls = false"
                                x-bind:class="docsSegmentState === 'all' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-squares-2x2 class="h-4 w-4"/>
                                    Carpetas
                                </span>
                            </button>

                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsSegmentAction('recent'); showDocsHomeControls = false"
                                x-bind:class="docsSegmentState === 'recent' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-clock class="h-4 w-4"/>
                                    Recientes
                                </span>
                            </button>

                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsSegmentAction('favorites'); showDocsHomeControls = false"
                                x-bind:class="docsSegmentState === 'favorites' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-star class="h-4 w-4"/>
                                    Favoritos
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="text-[12px] text-white/55">
                        {{ (int) ($documentosStats['total'] ?? 0) }} PDFs
                        <span class="text-white/35">·</span>
                        ★ {{ (int) ($documentosStats['favorites'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    x-show="docsFolder && !selectedDocumentId && showDocsControls"
    x-transition.opacity
    class="fixed inset-0 z-50"
    x-on:keydown.escape.window="showDocsControls = false"
    style="display: none;"
>
    <div
        class="absolute inset-0 bg-black/75"
        x-on:click="showDocsControls = false"
    ></div>

    <div class="absolute inset-0 flex items-center justify-center px-4 sm:px-6">
        <div
            class="w-full max-w-5xl"
            x-on:click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-3 opacity-0"
            x-transition:enter-end="transform translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="transform translate-y-0 opacity-100"
            x-transition:leave-end="transform translate-y-3 opacity-0"
        >
            <div class="glass rounded-3xl border border-white/10 bg-black/50 backdrop-blur p-3">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-white/80 text-sm font-semibold">Filtrar y ordenar</p>
                    <button
                        type="button"
                        class="glass-hover inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                        x-on:click="showDocsControls = false"
                        aria-label="Cerrar"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                </div>

                <div class="mt-3 space-y-3">
                    <div>
                        <p class="text-white/40 text-[11px] uppercase tracking-widest">Segmento</p>
                        <div class="mt-2 inline-flex w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsSegmentAction('all'); showDocsControls = false"
                                x-bind:class="docsSegmentState === 'all' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-squares-2x2 class="h-4 w-4"/>
                                    Todos
                                </span>
                            </button>

                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsSegmentAction('recent'); showDocsControls = false"
                                x-bind:class="docsSegmentState === 'recent' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-clock class="h-4 w-4"/>
                                    Recientes
                                </span>
                            </button>

                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsSegmentAction('favorites'); showDocsControls = false"
                                x-bind:class="docsSegmentState === 'favorites' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-star class="h-4 w-4"/>
                                    Favoritos
                                </span>
                            </button>
                        </div>
                    </div>

                    <div>
                        <p class="text-white/40 text-[11px] uppercase tracking-widest">Orden</p>
                        <div class="mt-2 inline-flex w-full overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsOrderAction('recent'); showDocsControls = false"
                                x-bind:class="docsOrderState === 'recent' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-bars-arrow-down class="h-4 w-4"/>
                                    Fecha
                                </span>
                            </button>

                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsOrderAction('name'); showDocsControls = false"
                                x-bind:class="docsOrderState === 'name' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-bars-3-bottom-left class="h-4 w-4"/>
                                    Nombre
                                </span>
                            </button>

                            <button
                                type="button"
                                class="flex-1 px-3 py-2 text-sm text-white/75 hover:bg-white/5"
                                x-on:click="setDocsOrderAction('reference'); showDocsControls = false"
                                x-bind:class="docsOrderState === 'reference' ? 'bg-white/10 text-white/90 font-semibold' : ''"
                            >
                                <span class="inline-flex items-center justify-center gap-2">
                                    <x-heroicon-o-hashtag class="h-4 w-4"/>
                                    Ref.
                                </span>
                            </button>
                        </div>
                    </div>

                    <div class="text-[12px] text-white/55">
                        {{ (int) ($documentosCarpetaStats['count'] ?? 0) }} PDFs
                        <span class="text-white/35">·</span>
                        ★ {{ (int) ($documentosCarpetaStats['favorites'] ?? 0) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div
    x-show="docsFolder && showDocActions"
    x-transition.opacity
    class="fixed inset-0 z-50"
    x-on:keydown.escape.window="showDocActions = false"
    style="display: none;"
>
    <div
        class="absolute inset-0 bg-black/75"
        x-on:click="showDocActions = false"
    ></div>

    <div class="absolute inset-0 flex items-center justify-center px-4 sm:px-6">
        <div
            class="w-full max-w-5xl"
            x-on:click.stop
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform translate-y-3 opacity-0"
            x-transition:enter-end="transform translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="transform translate-y-0 opacity-100"
            x-transition:leave-end="transform translate-y-3 opacity-0"
        >
            <div class="glass rounded-3xl border border-white/10 bg-black/50 backdrop-blur p-3">
                <div class="flex items-center justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-white/80 text-sm font-semibold">Acciones</p>
                        <p class="mt-1 text-white/45 text-xs truncate" x-text="docActionItem?.nombre ?? ''"></p>
                    </div>
                    <button
                        type="button"
                        class="glass-hover inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                        x-on:click="showDocActions = false"
                        aria-label="Cerrar"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4"/>
                    </button>
                </div>

                <div class="mt-3 overflow-hidden rounded-2xl border border-white/10 bg-white/5">
                    <button
                        type="button"
                        class="w-full glass-hover flex items-center justify-between gap-3 px-4 py-2.5 text-left text-sm text-white/80"
                        x-on:click="showDocActions = false; if (docActionItem?.id) { $wire.openDocumento(docActionItem.id) }"
                    >
                        <span class="inline-flex items-center gap-3">
                            <x-heroicon-o-eye class="h-4 w-4 text-white/70"/>
                            Ver
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 text-white/35"/>
                    </button>

                    <div class="h-px bg-white/10"></div>

                    <button
                        type="button"
                        class="w-full glass-hover flex items-center justify-between gap-3 px-4 py-2.5 text-left text-sm text-white/80"
                        x-on:click="showDocActions = false; if (docActionItem?.id) { $wire.openDocumento(docActionItem.id); $wire.editDocumento() }"
                    >
                        <span class="inline-flex items-center gap-3">
                            <x-heroicon-o-pencil-square class="h-4 w-4 text-white/70"/>
                            Editar
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 text-white/35"/>
                    </button>

                    <div class="h-px bg-white/10"></div>

                    <a
                        class="block w-full glass-hover"
                        x-bind:href="docActionItem?.file_url ?? '#'
                        "
                        x-bind:class="docActionItem?.file_url ? '' : 'opacity-50 pointer-events-none'"
                        target="_blank"
                        x-on:click="showDocActions = false"
                    >
                        <span class="flex items-center justify-between gap-3 px-4 py-2.5 text-left text-sm text-white/80">
                            <span class="inline-flex items-center gap-3">
                                <x-heroicon-o-document-text class="h-4 w-4 text-white/70"/>
                                Archivo
                            </span>
                            <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4 text-white/35"/>
                        </span>
                    </a>

                    <div class="h-px bg-white/10"></div>

                    <a
                        class="block w-full glass-hover"
                        x-bind:href="docActionItem?.file_url ?? '#'
                        "
                        x-bind:class="docActionItem?.file_url ? '' : 'opacity-50 pointer-events-none'"
                        download
                        x-on:click="showDocActions = false"
                    >
                        <span class="flex items-center justify-between gap-3 px-4 py-2.5 text-left text-sm text-white/80">
                            <span class="inline-flex items-center gap-3">
                                <x-heroicon-o-arrow-down-tray class="h-4 w-4 text-white/70"/>
                                Descargar
                            </span>
                            <x-heroicon-o-chevron-right class="h-4 w-4 text-white/35"/>
                        </span>
                    </a>

                    <div class="h-px bg-white/10"></div>

                    <button
                        type="button"
                        class="w-full glass-hover flex items-center justify-between gap-3 px-4 py-2.5 text-left text-sm text-white/80"
                        x-on:click="showDocActions = false; if (docActionItem?.id) { toggleDocumentoFavoritoAction(docActionItem.id, !!docActionItem.favorito) }"
                    >
                        <span class="inline-flex items-center gap-3">
                            <x-heroicon-o-star class="h-4 w-4 text-white/70"/>
                            Favorito
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 text-white/35"/>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
