<x-filament-panels::page>
    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Pagos desajustados</h3>
            <div class="mt-3 space-y-2">
                @forelse ($this->mismatchedPayments as $reservation)
                    <div
                        class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-900/40 dark:bg-amber-900/20">
                        {{ $reservation->booking_starts_at?->format('d/m H:i') }} · {{ $reservation->service_name }}
                        · {{ $reservation->customer_name }}
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Sin desajustes.</div>
                @endforelse
            </div>
        </section>

        <section class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Reservas huérfanas</h3>
            <div class="mt-3 space-y-2">
                @forelse ($this->orphanBookings as $reservation)
                    <div
                        class="rounded-lg border border-rose-200 bg-rose-50 p-3 text-sm dark:border-rose-900/40 dark:bg-rose-900/20">
                        {{ $reservation->booking_starts_at?->format('d/m H:i') }} · LP
                        #{{ $reservation->latepoint_booking_id }} · {{ $reservation->customer_name }}
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Sin reservas huérfanas.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament-panels::page>
