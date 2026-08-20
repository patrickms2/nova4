<div
    x-data
    x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
    class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 pb-12"
    wire:key="offer-detail-wrapper-{{ $offer->id ?? 'x' }}"
    x-cloak
>
    {{-- HERO IMAGE CON PARALLAX SUAVE --}}
    <div
        class="relative h-[40vh] w-full overflow-hidden bg-slate-100"
        x-data="{ y: 0 }"
        @scroll.window="y = window.scrollY * 0.15"
    >
        <img
            src="{{ $offer->image_url ? (Str::startsWith($offer->image_url, 'http') ? $offer->image_url : asset('storage/' . $offer->image_url)) : 'https://images.unsplash.com/photo-1510414842594-a61c69b5ae57?auto=format&fit=crop&q=80' }}"
            alt="{{ $offer->title }}"
            :style="`transform: translateY(${y}px) scale(1.1)`"
            class="h-full w-full object-cover will-change-transform"
        >

        <a href="javascript:history.back()"
           class="absolute top-6 left-6 w-12 h-12 rounded-full bg-white/90 backdrop-blur-xl border border-white/20 flex items-center justify-center text-slate-900 text-xl shadow-lg z-20 transition duration-[var(--motion-fast)] ease-[var(--ease-springy)] hover:scale-105 active:scale-90">
            ←
        </a>
    </div>

    <div
        class="px-6 py-8 space-y-8 bg-white -mt-10 rounded-t-[40px] relative z-10 shadow-[0_-20px_40px_rgba(0,0,0,0.05)]">
        {{-- TITULO Y FRASE CLAVE --}}
        <div class="space-y-3">
            <h1
                x-data
                x-init="setTimeout(() => $el.classList.add('is-in'), 100)"
                class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 text-4xl font-black leading-[1] text-slate-950 tracking-tight"
            >
                {{ $offer->title }}
            </h1>
            <p
                x-data
                x-init="setTimeout(() => $el.classList.add('is-in'), 200)"
                class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 text-xl font-bold text-emerald-600 leading-tight"
            >
                {{ $offer->excerpt ?: 'Una experiencia única en Lanzarote que no te puedes perder.' }}
            </p>
        </div>

        {{-- CONTEXTO DE CONVERSIÓN --}}
        <div class="flex flex-col gap-3">
            <div
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 300)"
                class="opacity-0 translate-y-3 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 flex items-center gap-3 text-slate-900 bg-slate-50 p-4 rounded-3xl border border-slate-100/50"
            >
                <span class="text-xl">📍</span>
                <span class="font-bold text-[15px]">
                    @if($distanceKm !== null && $distanceMinutes !== null)
                        {{ $offer->location_label ?: 'Ubicación confirmada' }} · {{ number_format($distanceKm, 1) }} km · {{ $distanceMinutes }} min desde tu destino
                    @else
                        {{ $offer->location_label ?: 'Ubicación en Lanzarote' }}
                    @endif
                </span>
            </div>
            <div
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 400)"
                class="opacity-0 translate-y-3 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 flex items-center gap-3 text-slate-900 bg-slate-50 p-4 rounded-3xl border border-slate-100/50"
            >
                <span class="text-xl">⚡</span>
                <span class="font-bold text-[15px]">Disponible ahora</span>
            </div>
            <div
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 500)"
                class="opacity-0 translate-y-3 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 flex items-center gap-3 text-slate-900 bg-slate-50 p-4 rounded-3xl border border-slate-100/50"
            >
                <span class="text-xl">🔘</span>
                <span class="font-bold text-[15px]">Reserva rápida en 2 clics</span>
            </div>
        </div>

        {{-- DETAILS GRID --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-[32px] bg-slate-950 p-6 text-white shadow-xl">
                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/50">Precio</div>
                <div class="mt-1 text-3xl font-black">
                    {{ number_format($offer->price_from ?? 0, 0) }}€
                </div>
            </div>

            <div class="rounded-[32px] bg-slate-50 p-6 border border-slate-100">
                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">Duración</div>
                <div class="mt-1 text-3xl font-black text-slate-950">
                    {{ $offer->duration_minutes ?? 60 }}'
                </div>
            </div>
        </div>

        {{-- DESCRIPTION CORTA --}}
        <div class="space-y-4">
            <h2 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Sobre la experiencia</h2>
            <p class="text-[16px] leading-relaxed text-slate-600 font-medium">
                {{ $offer->description ?: 'Una propuesta pensada para quien quiere descubrir algo auténtico, cercano y fácil de disfrutar al llegar.' }}
            </p>
        </div>

        {{-- CTA FIJO O SEMI-FIJO --}}
        <div class="pt-4">
            <button wire:click="goToBooking"
                    x-data
                    x-init="setTimeout(() => $el.classList.add('is-in'), 650)"
                    class="opacity-0 translate-y-4 transition-all duration-[var(--motion-base)] ease-[var(--ease-springy)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 w-full bg-slate-950 hover:bg-slate-900 text-white font-black py-6 rounded-[28px] shadow-[0_20px_40px_rgba(0,0,0,0.2)] hover:scale-[1.01] active:scale-[0.98] text-xl flex items-center justify-center gap-3">
                <span>Reservar ahora</span>
                <span class="opacity-30">·</span>
                <span>{{ number_format($offer->price_from ?? 0, 0) }}€</span>
            </button>
            <p
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 800)"
                class="opacity-1 transition-opacity duration-[var(--motion-soft)] [&.is-in]:opacity-100 mt-4 text-center text-[11px] font-bold text-slate-400 uppercase tracking-widest flex items-center justify-center gap-2"
            >
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                Confirmación inmediata · Pago en local
            </p>
        </div>
    </div>
</div>
