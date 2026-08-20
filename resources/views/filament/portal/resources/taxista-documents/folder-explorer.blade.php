@php
    $folderCards = $folders ?? [];
    $documentMetrics = $metrics ?? ['documents' => 0, 'folders' => 0, 'favorites' => 0];
    $currentFolder = $selectedDocumentType ?? null;
    $currentFolderLabel = $selectedDocumentLabel ?? null;
@endphp

@if (! $currentFolder)
    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="grid gap-3 md:grid-cols-3">
            <article class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Documentos</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $documentMetrics['documents'] }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Carpetas</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $documentMetrics['folders'] }}</p>
            </article>
            <article class="rounded-xl border border-gray-200 px-4 py-3 dark:border-gray-700">
                <p class="text-xs uppercase tracking-wide text-gray-500 dark:text-gray-400">Favoritos</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-gray-100">{{ $documentMetrics['favorites'] }}</p>
            </article>
        </div>

        <div class="mt-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Explorador</p>
            <p class="mt-1 text-2xl font-semibold text-gray-900 dark:text-gray-100">Carpetas por tipo</p>
        </div>

        <div class="mt-3 grid gap-3">
            @forelse ($folderCards as $folder)
                <button
                    type="button"
                    wire:click="openDocumentFolder('{{ $folder['type'] }}')"
                    class="flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 text-left transition hover:border-gray-300 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-gray-600 dark:hover:bg-gray-800"
                >
                    <span>
                        <span class="block text-2xl font-semibold uppercase leading-tight text-gray-900 dark:text-gray-100">
                            {{ $folder['label'] }}
                        </span>
                        <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">Tipo de documento</span>
                    </span>
                    <span class="inline-flex h-9 min-w-9 items-center justify-center rounded-full border border-gray-300 bg-gray-50 px-2 text-sm font-semibold text-gray-700 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200">
                        {{ $folder['count'] }}
                    </span>
                </button>
            @empty
                <div class="rounded-xl border border-dashed border-gray-300 px-4 py-6 text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No hay carpetas con documentos todavía.
                </div>
            @endforelse
        </div>
    </section>
@else
    <section class="rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
        <div class="inline-flex items-center gap-2 rounded-full border border-gray-300 bg-gray-50 px-3 py-1.5 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
            <button
                type="button"
                wire:click="backToDocumentFolders"
                class="font-medium text-primary-600 transition hover:underline dark:text-primary-400"
            >
                Documentos
            </button>
            <span>›</span>
            <span class="font-semibold text-gray-900 dark:text-gray-100">{{ strtoupper($currentFolderLabel ?? '') }}</span>
        </div>
    </section>
@endif
