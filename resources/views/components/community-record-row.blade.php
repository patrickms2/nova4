@props(['record', 'type', 'tone' => 'red', 'subtitle' => null])

@php
    $title = $record->title ?? $record->concept ?? 'Registro';
    $status = (string) ($record->status ?? 'active');
    $toneClasses = match ($tone) { 'amber' => ['border-l-amber-500', 'bg-amber-500/10 ring-amber-500/20 text-amber-200'], 'blue' => ['border-l-blue-500', 'bg-blue-500/10 ring-blue-500/20 text-blue-200'], default => ['border-l-red-500', 'bg-red-500/10 ring-red-500/20 text-red-200'] };
    $statusClass = match ($status) { 'paid', 'active', 'confirmed', 'resolved', 'completed' => 'border-emerald-400/30 bg-emerald-500/10 text-emerald-200', 'cancelled', 'closed' => 'border-zinc-400/30 bg-zinc-500/10 text-zinc-200', default => 'border-red-400/30 bg-red-500/10 text-red-200' };
@endphp

<button type="button" wire:click="openDetail('{{ $type }}', {{ $record->id }})" {{ $attributes->class('community-portal-row group w-full border-l-2 '.$toneClasses[0].' p-3 text-left sm:p-4') }}>
    <div class="flex items-center gap-3">
        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClasses[1] }} transition group-hover:scale-105">
            @if ($type === 'document')<x-heroicon-o-document-text class="h-5 w-5" />@elseif ($type === 'incident')<x-heroicon-o-exclamation-triangle class="h-5 w-5" />@else<x-heroicon-o-ticket class="h-5 w-5" />@endif
        </span>
        <div class="min-w-0 flex-1"><p class="truncate font-medium text-white/90">{{ $title }}</p><p class="mt-0.5 truncate text-sm text-white/45">{{ $subtitle ?? $record->created_at?->format('d/m/Y H:i') }}</p></div>
        <span class="shrink-0 rounded-full border px-3 py-1 text-[11px] font-medium {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $status)) }}</span>
    </div>
</button>
