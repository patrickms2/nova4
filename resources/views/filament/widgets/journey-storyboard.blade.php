<x-filament-widgets::widget>
    <x-filament::section>
        <div class="rounded-3xl border border-primary-100 bg-gradient-to-br from-white via-primary-50/40 to-amber-50/60 p-6 shadow-sm">
            <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
                <div class="space-y-3">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-primary-600">Executive snapshot</p>
                    <div>
                        <h2 class="text-3xl font-semibold tracking-tight text-gray-950">Booking atribuible, sin ruido.</h2>
                        <p class="mt-2 max-w-2xl text-sm text-gray-600">Un KPI principal arriba. El funnel debajo. Lo demás, bajo demanda.</p>
                    </div>
                </div>

                <div class="flex flex-col gap-3 sm:flex-row sm:items-stretch">
                    <div class="min-w-[220px] rounded-2xl border border-white/80 bg-white/90 p-5 shadow-sm">
                        <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-gray-500">KPI principal</p>
                        <p class="mt-2 text-4xl font-semibold tracking-tight text-gray-950">€{{ number_format($commission, 0) }}</p>
                        <p class="mt-2 text-sm text-gray-600">Comisión atribuible</p>
                    </div>
                    <a href="{{ route('home') }}" class="inline-flex items-center justify-center rounded-2xl bg-primary-600 px-5 py-4 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500">
                        Ver front demo
                    </a>
                </div>
            </div>

            <div class="mt-6 grid gap-3 md:grid-cols-4">
                <article class="rounded-2xl border border-white/80 bg-white/85 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Taxi requests</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">{{ number_format($rides) }}</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/85 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">CTR view → click</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">{{ $clickThrough }}%</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/85 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Bookings</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">{{ number_format($bookingsCount) }}</p>
                </article>
                <article class="rounded-2xl border border-white/80 bg-white/85 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">GMV demo</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">€{{ number_format($gmv, 0) }}</p>
                </article>
            </div>

            <div class="mt-6 grid gap-3 xl:grid-cols-4">
                <article class="rounded-2xl border border-gray-200 bg-gray-950 p-4 text-white shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-white/60">1 · Taxi</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight">{{ number_format($rides) }}</p>
                    <p class="mt-2 text-sm text-white/70">entradas al funnel</p>
                </article>
                <article class="rounded-2xl border border-amber-200 bg-amber-50/80 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-amber-800">2 · Contexto</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">{{ number_format($views) }}</p>
                    <p class="mt-2 text-sm text-gray-600">views en espera útil</p>
                </article>
                <article class="rounded-2xl border border-teal-200 bg-teal-50/80 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-teal-800">3 · Oferta</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">{{ number_format($clicks) }}</p>
                    <p class="mt-2 text-sm text-gray-600">clics a detalle</p>
                </article>
                <article class="rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-emerald-800">4 · Booking</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight text-gray-950">{{ $rideToBooking }}%</p>
                    <p class="mt-2 text-sm text-gray-600">ride → booking</p>
                </article>
            </div>

            <details class="mt-6 rounded-2xl border border-gray-200 bg-white/80 p-4">
                <summary class="cursor-pointer list-none text-sm font-semibold text-gray-900">ℹ️ Ver contexto ejecutivo</summary>
                <div class="mt-4 grid gap-3 md:grid-cols-3">
                    <div class="rounded-2xl bg-gray-50 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Zona con más señal</p>
                        <p class="mt-2 text-lg font-semibold text-gray-950">{{ $topZone }}</p>
                    </div>
                    <div class="rounded-2xl bg-gray-50 p-4 md:col-span-2">
                        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-gray-500">Última reserva</p>
                        <p class="mt-2 text-lg font-semibold text-gray-950">{{ $latestBooking?->offer?->title ?: 'Todavía no hay bookings' }}</p>
                        <p class="mt-2 text-sm text-gray-600">La explicación profunda queda oculta por defecto para mantener el dashboard limpio.</p>
                    </div>
                </div>
            </details>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
