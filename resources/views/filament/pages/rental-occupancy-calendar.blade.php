<x-filament-panels::page class="rental-occupancy-calendar">
    <x-filament::card>
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold capitalize text-gray-900 dark:text-white">
                {{ $this->calendar['monthName'] }}
            </h2>
            <div class="flex items-center gap-2">
                <x-filament::button size="sm" wire:click="previousMonth" color="gray">
                    <x-filament::icon name="heroicon-o-chevron-left" class="w-5 h-5" />
                </x-filament::button>
                <x-filament::button size="sm" wire:click="today" color="gray">
                    Hoy
                </x-filament::button>
                <x-filament::button size="sm" wire:click="nextMonth" color="gray">
                    <x-filament::icon name="heroicon-o-chevron-right" class="w-5 h-5" />
                </x-filament::button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-2 text-center text-sm font-semibold text-gray-500 dark:text-gray-400">
            <div>Lun</div>
            <div>Mar</div>
            <div>Mié</div>
            <div>Jue</div>
            <div>Vie</div>
            <div>Sáb</div>
            <div>Dom</div>
        </div>

        <div class="grid grid-cols-7 gap-2 mt-2">
            @foreach ($this->calendar['weeks'] as $week)
                @foreach ($week as $day)
                    <div class="min-h-[120px] p-2 border rounded-xl flex flex-col {{ $day['isCurrentMonth'] ? 'bg-white dark:bg-gray-900 border-gray-200 dark:border-gray-800' : 'bg-gray-50 dark:bg-gray-800/50 border-gray-100 dark:border-gray-700' }}">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-sm {{ $day['isToday'] ? 'font-bold text-primary-600 dark:text-primary-400' : 'text-gray-500 dark:text-gray-400' }}">
                                {{ $day['date']->format('j') }}
                            </span>
                            @if ($day['isToday'])
                                <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                            @endif
                        </div>

                        <div class="flex-1 space-y-1 overflow-y-auto">
                            @foreach ($day['reservations'] as $reservation)
                                <a href="{{ \App\Filament\App\Rentals\Resources\RentalReservationResource::getUrl('view', ['record' => $reservation['id']]) }}"
                                   class="block text-[10px] leading-tight truncate rounded px-1.5 py-0.5 {{ $reservation['isStart'] ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-100' : ($reservation['isEnd'] ? 'bg-rose-100 text-rose-800 dark:bg-rose-900 dark:text-rose-100' : 'bg-amber-100 text-amber-800 dark:bg-amber-900 dark:text-amber-100') }}"
                                   title="{{ $reservation['guest'] }} ({{ $reservation['check_in'] }} - {{ $reservation['check_out'] }})">
                                    {{ $reservation['guest'] ?? '—' }}
                                    @if ($reservation['isStart'])
                                        <span class="opacity-70">→</span>
                                    @elseif ($reservation['isEnd'])
                                        <span class="opacity-70">←</span>
                                    @endif
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endforeach
        </div>
    </x-filament::card>

    <x-filament::card class="mt-4">
        <h3 class="text-base font-semibold mb-2">Reservas del mes</h3>
        <div class="space-y-2">
            @php
                $monthReservations = collect($this->calendar['weeks'])
                    ->flatten(1)
                    ->pluck('reservations')
                    ->flatten(1)
                    ->unique('id')
                    ->sortBy('check_in');
            @endphp

            @forelse ($monthReservations as $reservation)
                <a href="{{ \App\Filament\App\Rentals\Resources\RentalReservationResource::getUrl('view', ['record' => $reservation['id']]) }}"
                   class="flex items-center justify-between p-3 -mx-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <div>
                        <div class="font-medium">{{ $reservation['guest'] ?? '—' }}</div>
                        <div class="text-sm text-gray-500 dark:text-gray-400">{{ $reservation['property'] ?? '—' }} · {{ $reservation['check_in'] }} - {{ $reservation['check_out'] }}</div>
                    </div>
                    <div class="text-xs text-gray-500 uppercase">{{ $reservation['channel'] }}</div>
                </a>
            @empty
                <div class="text-sm text-gray-500 dark:text-gray-400">No hay reservas confirmadas para este mes.</div>
            @endforelse
        </div>
    </x-filament::card>

    <div class="space-y-4">
        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs text-gray-500">Fecha</div>
                <div
                    class="font-semibold">{{ \Illuminate\Support\Carbon::parse($this->selectedDate)->format('d/m/Y') }}</div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs text-gray-500">Servicio</div>
                <div class="font-semibold"></div>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
                <div class="text-xs text-gray-500">Idioma</div>
                <div class="font-semibold"></div>
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
