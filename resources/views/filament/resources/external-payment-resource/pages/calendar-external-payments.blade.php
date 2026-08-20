<x-filament-panels::page>
    <div class="space-y-4">
        @php
            $calendar = $this->getCalendarData();
            $days = $calendar['days'];
            $rows = $calendar['rows'];
            $dailyTotals = $calendar['dailyTotals'];
            $rangeTotal = $calendar['rangeTotal'];
            $currency = $calendar['currency'];
        @endphp

        <div class="grid items-end gap-4 lg:grid-cols-[1fr_420px]">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="text-sm text-gray-500">Reservas externas pagadas por servicio</div>
                <div class="mt-1 text-xs font-medium uppercase tracking-wide text-gray-400">Suma</div>
                <div class="mt-1 text-2xl font-semibold text-gray-950">
                    {{ number_format((float) $rangeTotal['amount'], 2, ',', '.') }} {{ $currency }}
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    {{ (int) $rangeTotal['count'] }} reservas pagadas
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <div class="mb-3 text-sm font-medium text-gray-700">Rango de fechas</div>
                {{ $this->form }}
            </div>
        </div>

        <div class="overflow-auto rounded-lg border border-gray-200 bg-white">
            <div class="min-w-[980px]">
                <div class="grid" style="display: grid; grid-template-columns: 260px repeat({{ $days->count() }}, minmax(96px, 1fr));">
                    <div class="sticky left-0 z-20 border-b border-r border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-600">
                        Servicio
                    </div>
                    @foreach ($days as $day)
                        <div class="border-b border-gray-200 px-2 py-2 text-center text-[11px] font-medium text-gray-600">
                            <div>{{ $day->format('D') }}</div>
                            <div class="text-gray-400">{{ $day->format('d M') }}</div>
                        </div>
                    @endforeach

                    @forelse ($rows as $row)
                        <div class="sticky left-0 z-10 border-b border-r border-gray-100 bg-white px-3 py-2">
                            <div class="truncate text-sm font-medium text-gray-900">{{ $row['label'] }}</div>
                            <div class="truncate text-xs text-gray-500">{{ $row['key'] }}</div>
                        </div>
                        @foreach ($row['cells'] as $cell)
                            @php
                                $count = (int) ($cell['count'] ?? 0);
                                $amount = (float) ($cell['amount'] ?? 0);
                                $has = $count > 0 && $amount > 0;
                            @endphp
                            <div class="border-b border-gray-100 px-2 py-2">
                                <div @class([
                                    'h-[56px] rounded-md border px-2 py-1 text-center',
                                    'border-gray-200 bg-gray-50' => ! $has,
                                    'border-emerald-200 bg-emerald-50' => $has,
                                ])>
                                    <div class="text-xs font-semibold text-gray-900">{{ $count }}</div>
                                    <div class="text-[11px] text-gray-600">{{ number_format($amount, 2, ',', '.') }} {{ $currency }}</div>
                                </div>
                            </div>
                        @endforeach
                    @empty
                        <div class="col-span-full px-4 py-8 text-sm text-gray-500">
                            No paid external bookings in this range.
                        </div>
                    @endforelse

                    @if (filled($rows))
                        <div class="sticky left-0 z-10 border-r border-gray-200 bg-gray-50 px-3 py-2">
                            <div class="truncate text-sm font-semibold text-gray-950">Total día</div>
                            <div class="truncate text-xs text-gray-500">Suma por fecha</div>
                        </div>
                        @foreach ($dailyTotals as $total)
                            @php
                                $count = (int) ($total['count'] ?? 0);
                                $amount = (float) ($total['amount'] ?? 0);
                                $has = $count > 0 && $amount > 0;
                            @endphp
                            <div class="bg-gray-50 px-2 py-2">
                                <div @class([
                                    'h-[56px] rounded-md border px-2 py-1 text-center',
                                    'border-gray-200 bg-white' => ! $has,
                                    'border-blue-200 bg-blue-50' => $has,
                                ])>
                                    <div class="text-xs font-semibold text-gray-950">{{ $count }}</div>
                                    <div class="text-[11px] font-medium text-gray-700">{{ number_format($amount, 2, ',', '.') }} {{ $currency }}</div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
