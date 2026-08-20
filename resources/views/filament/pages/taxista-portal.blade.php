<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $profileUrl = $this->taxistaProfileUrl();
        $selectedDocumentType = $this->selectedDocumentType;
        $selectedDocumentFolder = $selectedDocumentType ? $this->selectedDocumentFolder() : null;
        $selectedDocuments = $selectedDocumentType ? $this->selectedDocuments() : collect();
        $favoriteDocuments = (! $selectedDocumentType && $this->showFavoriteDocuments) ? $this->favoriteDocuments() : collect();
        $documentFolders = ! $selectedDocumentType ? $this->documentFolders() : collect();
    @endphp

    @if (! $this->taxistaId)
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-sm text-gray-600 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-300">
            No hay taxistas disponibles para mostrar en el portal.
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-5">
            <div class="rounded-xl border border-cyan-200 bg-cyan-50 p-4 dark:border-cyan-900/40 dark:bg-cyan-950/20">
                <p class="text-xs font-medium uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Taxis</p>
                <p class="mt-1 text-3xl font-bold text-cyan-900 dark:text-cyan-100">{{ $stats['taxis'] }}</p>
            </div>

            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-900/40 dark:bg-blue-950/20">
                <p class="text-xs font-medium uppercase tracking-wide text-blue-700 dark:text-blue-300">Citas</p>
                <p class="mt-1 text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['appointments'] }}</p>
            </div>

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/40 dark:bg-amber-950/20">
                <p class="text-xs font-medium uppercase tracking-wide text-amber-700 dark:text-amber-300">Documentos</p>
                <p class="mt-1 text-3xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['documents'] }}</p>
            </div>

            <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-900/40 dark:bg-rose-950/20">
                <p class="text-xs font-medium uppercase tracking-wide text-rose-700 dark:text-rose-300">Tickets Abiertos</p>
                <p class="mt-1 text-3xl font-bold text-rose-900 dark:text-rose-100">{{ $stats['tickets'] }}</p>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900/40 dark:bg-emerald-950/20">
                <p class="text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Próxima Cita</p>
                <p class="mt-1 text-sm font-semibold text-emerald-900 dark:text-emerald-100">
                    {{ $stats['nextAppointment'] ?: 'Sin citas próximas' }}
                </p>
                @if ($profileUrl)
                    <a href="{{ $profileUrl }}" class="mt-2 inline-flex text-xs font-medium text-emerald-700 hover:underline dark:text-emerald-300">
                        Abrir ficha del taxista
                    </a>
                @endif
            </div>
        </div>

        <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Documentos</h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        Navega por carpetas de tipo. El resumen se mantiene separado del explorador.
                    </p>
                </div>

                <div class="inline-flex rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
                    <button
                        type="button"
                        wire:click="openFavoriteDocuments"
                        class="rounded-md px-2.5 py-1 text-xs font-medium transition {{ $this->showFavoriteDocuments ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    >
                        Favoritos
                    </button>
                    <button
                        type="button"
                        wire:click="closeFavoriteDocuments"
                        class="rounded-md px-2.5 py-1 text-xs font-medium transition {{ ! $this->showFavoriteDocuments ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-gray-100' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}"
                    >
                        Carpetas
                    </button>
                </div>
            </div>

            @if ($selectedDocumentType)
                <div class="mt-4 inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <button type="button" wire:click="backToDocumentFolders" class="font-medium text-primary-600 hover:underline dark:text-primary-400">
                        Documentos
                    </button>
                    <span>›</span>
                    <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $selectedDocumentFolder['label'] ?? 'Tipo' }}</span>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    @forelse ($selectedDocuments as $document)
                        <article class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                            <div class="flex items-start justify-between gap-2">
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $document->title }}</p>
                                <button
                                    type="button"
                                    wire:click="toggleFavorite({{ $document->id }})"
                                    class="text-sm leading-none {{ $document->is_favorite ? 'text-amber-500' : 'text-gray-400 hover:text-amber-500' }}"
                                    title="Marcar favorito"
                                >
                                    {{ $document->is_favorite ? '★' : '☆' }}
                                </button>
                            </div>

                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                {{ $document->department?->name ?: 'Sin departamento' }} · {{ $document->uploaded_at?->format('d/m/Y H:i') ?: $document->created_at?->format('d/m/Y H:i') }}
                            </p>

                            <div class="mt-3">
                                <label class="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">Mover a</label>
                                <select
                                    wire:change="updateDocumentType({{ $document->id }}, $event.target.value)"
                                    class="block w-full rounded-md border-gray-300 text-xs focus:border-primary-500 focus:ring-primary-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                >
                                    @foreach ([
                                        'nomina' => 'Nominas',
                                        'impuesto' => 'Impuestos',
                                        'certificado' => 'Certificados',
                                        'seguro' => 'Seguros',
                                        'otros' => 'Otros',
                                    ] as $optionValue => $optionLabel)
                                        <option value="{{ $optionValue }}" @selected($document->document_type === $optionValue)>
                                            {{ $optionLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </article>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-200 p-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            Esta carpeta no tiene documentos.
                        </p>
                    @endforelse
                </div>
            @else
                @if ($this->showFavoriteDocuments && $favoriteDocuments->isNotEmpty())
                    <div class="mt-4">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            Favoritos recientes
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($favoriteDocuments as $document)
                                <article class="rounded-xl border border-amber-200 bg-amber-50/70 p-3 dark:border-amber-800/40 dark:bg-amber-950/20">
                                    <div class="flex items-start justify-between gap-2">
                                        <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $document->title }}</p>
                                        <button
                                            type="button"
                                            wire:click="toggleFavorite({{ $document->id }})"
                                            class="text-sm leading-none text-amber-500"
                                            title="Quitar favorito"
                                        >
                                            ★
                                        </button>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        {{ $document->department?->name ?: 'Sin departamento' }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($documentFolders as $folder)
                        <button
                            type="button"
                            wire:click="openDocumentFolder('{{ $folder['type'] }}')"
                            class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600 dark:hover:bg-gray-800"
                        >
                            <span>
                                <span class="block text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $folder['label'] }}</span>
                                <span class="block text-xs text-gray-500 dark:text-gray-400">Tipo de documento</span>
                            </span>
                            <span class="rounded-full border border-gray-300 bg-gray-50 px-2 py-0.5 text-xs font-semibold text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                                {{ $folder['count'] }}
                            </span>
                        </button>
                    @empty
                        <p class="rounded-lg border border-dashed border-gray-200 p-3 text-xs text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            No hay carpetas con documentos todavía.
                        </p>
                    @endforelse
                </div>
            @endif
        </section>
    @endif
</x-filament-panels::page>
