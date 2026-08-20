<x-filament-panels::page>
    <div class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Resumen mensual</h3>
            <p class="mt-1 text-xs text-gray-500">Vista agregada por día (reservas, asistentes e ingresos).</p>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->calendarData as $day)
                <a
                    href="{{ \App\Filament\Pages\DailyAgenda::getUrl(['date' => $day['date']]) }}"
                    class="block rounded-xl border border-gray-200 bg-white p-4 transition hover:border-primary-400 dark:border-gray-800 dark:bg-gray-900"
                >
                    <div
                        class="text-xs text-gray-500">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d/m/Y') }}</div>
                    <div class="mt-2 text-lg font-semibold text-gray-900 dark:text-white">{{ $day['reservations'] }}
                        reservas
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-300">{{ $day['attendees'] }} asistentes</div>
                    <div class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                        € {{ number_format($day['revenue'], 2, ',', '.') }}</div>
                </a>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
