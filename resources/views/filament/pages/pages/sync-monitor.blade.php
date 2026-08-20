<x-filament-panels::page>
    <div class="space-y-3">
        @forelse ($this->syncRows as $row)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <div class="flex items-center justify-between gap-3">
                    <div class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ strtoupper($row->source) }} · {{ $row->job_name ?? 'sync' }}
                    </div>
                    <span class="text-xs text-gray-500">{{ $row->processed_at?->format('d/m H:i') ?? '-' }}</span>
                </div>
                <div class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                    Estado: <strong>{{ $row->status }}</strong>
                </div>
                @if ($row->error_message)
                    <div class="mt-2 text-xs text-red-600 dark:text-red-400">{{ $row->error_message }}</div>
                @endif
            </div>
        @empty
            <div
                class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No hay registros de sincronización.
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
