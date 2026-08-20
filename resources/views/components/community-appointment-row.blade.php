@props(['appointment'])

@php
    $status = (string) ($appointment->status ?? 'scheduled');
    $statusLabel = match ($status) { 'scheduled' => 'Pendiente', 'confirmed' => 'Confirmada', 'completed' => 'Finalizada', 'cancelled' => 'Cancelada', default => ucfirst($status) };
    $statusClass = match ($status) { 'confirmed' => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200', 'cancelled' => 'border-red-400/30 bg-red-500/10 text-red-200', 'completed' => 'border-blue-400/30 bg-blue-500/10 text-blue-200', default => 'border-amber-400/30 bg-amber-500/10 text-amber-200' };
@endphp

<button type="button" wire:click="openDetail('appointment', {{ $appointment->id }})" {{ $attributes->class('community-portal-row group w-full border-l-2 border-l-blue-500 p-3 text-left sm:p-4') }}>
    <div class="flex min-w-0 items-center gap-3 sm:gap-4">
        <div class="w-[58px] shrink-0 rounded-xl border border-white/10 bg-white/5 p-2 text-center transition group-hover:bg-white/[0.075]">
            <x-heroicon-o-calendar-days class="mx-auto h-3.5 w-3.5 text-blue-300" />
            <p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-white/55">{{ $appointment->starts_at?->translatedFormat('M') }}</p>
            <p class="text-2xl font-semibold leading-none text-white/95">{{ $appointment->starts_at?->format('d') }}</p>
        </div>
        <div class="w-[122px] shrink-0 rounded-xl border border-white/10 bg-white/5 px-2 py-2 text-center transition group-hover:bg-white/[0.075]">
            <span class="inline-flex max-w-full items-center gap-1 rounded-md border border-blue-400/25 bg-blue-500/10 px-2 py-1 text-[10px] font-semibold text-blue-200"><x-heroicon-o-building-office-2 class="h-3 w-3 shrink-0" /><span class="truncate">{{ $appointment->community?->name ?? 'Comunidad' }}</span></span>
            <div class="mt-2 inline-flex items-center gap-1.5 text-blue-100"><x-heroicon-o-clock class="h-4 w-4" /><span class="text-sm font-semibold tabular-nums">{{ $appointment->starts_at?->format('H:i') }}</span></div>
        </div>
        <div class="min-w-0 flex-1"><p class="truncate font-medium text-white/90">{{ $appointment->title }}</p></div>
        <div class="flex min-h-[76px] w-11 shrink-0 items-center justify-center"><span class="-rotate-90 whitespace-nowrap rounded-full border px-3 py-1 text-[11px] font-medium {{ $statusClass }}">{{ $statusLabel }}</span></div>
    </div>
</button>
