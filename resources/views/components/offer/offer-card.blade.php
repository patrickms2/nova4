@props([
    'offer',
    'href' => null,
    'ctaLabel' => 'Ver y reservar',
    'delay' => 120,
])

@php
    $slotContent = trim((string) $slot);
@endphp

<article x-data x-init="setTimeout(() => $el.classList.add('is-in'), {{ $delay }})" {{ $attributes->class(['surface-card motion-card flex h-full flex-col gap-5 p-5 sm:p-6']) }}>
    <div class="offer-media">
        @if ($offer->image_url)
            <img src="{{ $offer->image_url }}" alt="{{ $offer->title }}">
            <div aria-hidden="true"></div>
        @endif
    </div>

    <div class="flex flex-wrap gap-2">
        <span class="chip">{{ \App\Models\Offer::CATEGORIES[$offer->category] ?? ucfirst($offer->category) }}</span>
        @if ($offer->location_label)
            <span class="chip">{{ $offer->location_label }}</span>
        @endif
        @if ($offer->price_from)
            <span class="chip">Desde €{{ number_format((float) $offer->price_from, 0) }}</span>
        @endif
        @if ($offer->duration_minutes)
            <span class="chip">{{ $offer->duration_minutes }} min</span>
        @endif
    </div>

    <div class="space-y-2">
        <h3 class="text-[1.85rem] text-slate-950">{{ $offer->title }}</h3>
        <p class="text-[15px] text-slate-600">{{ $offer->excerpt ?: $offer->description }}</p>
    </div>

    @if ($slotContent !== '')
        <div class="space-y-3">
            {{ $slot }}
        </div>
    @endif

    @if ($href)
        <x-ui.button :href="$href" wire:navigate class="mt-auto">
            {{ $ctaLabel }}
        </x-ui.button>
    @endif
</article>
