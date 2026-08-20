<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">Alertas de importacion</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        Total activas: {{ $this->boardData['total'] }}.
                        Actualizado {{ $this->boardData['generated_at'] }}.
                    </p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <label class="block">
                        <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Buscar</span>
                        <input
                            type="search"
                            wire:model.live.debounce.300ms="search"
                            placeholder="NIF, usuario o PDF"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        />
                    </label>

                    <label class="block">
                        <span class="mb-1 block text-xs font-medium uppercase tracking-wide text-gray-500">Tipo</span>
                        <select
                            wire:model.live="documentType"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                        >
                            @foreach ($this->documentTypeOptionsData as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            @foreach ($this->boardData['columns'] as $column)
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-4 border-b border-gray-100 pb-3">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-900">
                                {{ $column['label'] }}
                            </h3>
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs font-semibold text-gray-600">
                                {{ $column['count'] }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ $column['description'] }}</p>
                    </div>

                    <div class="space-y-3">
                        @forelse ($column['items'] as $item)
                            <article class="rounded-lg border border-gray-200 bg-gray-50 p-3">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium text-gray-900">
                                            {{ $item['user_name'] ?? $item['nif'] ?? 'Incidencia OCR' }}
                                        </div>
                                        <div class="mt-1 text-xs text-gray-500">
                                            {{ $item['source_name'] }}
                                            @if ($item['page'])
                                                · pág. {{ $item['page'] }}
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-2">
                                        @if ($item['source_url'])
                                            <a
                                                href="{{ $item['source_url'] }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="rounded-md border border-amber-300 px-2 py-1 text-xs font-medium text-amber-700"
                                            >
                                                PDF
                                            </a>
                                        @endif

                                        @if ($item['user_id'] && $item['role'] === 'taxista')
                                            <a
                                                href="{{ \App\Filament\App\Resources\Taxistas\TaxistaResource::getUrl('view', ['record' => $item['user_id']], tenant: \Filament\Facades\Filament::getTenant()) }}"
                                                class="rounded-md border border-gray-300 px-2 py-1 text-xs font-medium text-gray-600"
                                            >
                                                Abrir
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                <p class="mt-3 text-sm text-gray-700">{{ $item['message'] }}</p>

                                <div class="mt-3 space-y-1 text-xs text-gray-500">
                                    <div>Documento: {{ str($item['document_type'])->replace('_', ' ')->title() }}</div>
                                    @if ($item['nif'])
                                        <div>NIF: {{ $item['nif'] }}</div>
                                    @endif
                                    @if ($item['role'])
                                        <div>Rol: {{ $item['role'] }}</div>
                                    @endif
                                    <div>Detectado: {{ \Illuminate\Support\Carbon::parse($item['happened_at'])->format('d/m/Y H:i') }}</div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-lg border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-sm text-gray-400">
                                Sin incidencias.
                            </div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
