<x-filament-panels::page>
    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs text-gray-500">Fecha</div>
                <div
                    class="font-semibold">{{ \Illuminate\Support\Carbon::parse($this->selectedDate)->format('d/m/Y') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs text-gray-500">Servicio</div>
                <div class="font-semibold">{{ $this->service ?: 'Todos' }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs text-gray-500">Idioma</div>
                <div class="font-semibold">{{ $this->language ?: 'Todos' }}</div>
            </div>
        </div>

        <div class="space-y-2">
            @forelse ($this->agendaRows as $reservation)
                <div
                    class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-gray-900">
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ $reservation->booking_starts_at?->format('H:i') }} — {{ $reservation->service_name }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            {{ $reservation->customer_name }} · {{ $reservation->attendees }} pax
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                            {{ strtoupper($reservation->booking_status) }}
                        </span>
                        <span
                            class="rounded-full bg-emerald-50 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                            {{ strtoupper($reservation->payment_status) }}
                        </span>
                    </div>
                </div>
            @empty
                <div
                    class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No hay reservas para este día.
                </div>
            @endforelse
        </div>
    </div>
</x-filament-panels::page>
