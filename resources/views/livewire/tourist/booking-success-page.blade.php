<div
    x-data
    x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
    class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 px-6 py-8 space-y-8 text-center"
    wire:key="booking-success-wrapper-{{ $booking->id ?? 'x' }}"
    x-cloak
>
    <div class="space-y-6">
        <div
            x-data x-init="setTimeout(() => $el.classList.add('is-in'), 100)"
            class="opacity-0 scale-75 transition-all duration-[var(--motion-soft)] ease-[var(--ease-springy)] [&.is-in]:opacity-100 [&.is-in]:scale-100 mx-auto flex h-24 w-24 items-center justify-center rounded-[40px] bg-emerald-500 text-5xl shadow-[0_20px_40px_rgba(16,185,129,0.3)]"
        >
            <span class="text-white">✔</span>
        </div>
        <div class="space-y-2">
            <h1
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 200)"
                class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 text-4xl font-black tracking-tight text-slate-950 leading-[0.9]"
            >
                Reserva confirmada.
            </h1>
            <p
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 300)"
                class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 text-slate-500 font-bold text-lg"
            >
                Cierre de ciclo emocional
            </p>
        </div>
    </div>

    <div
        x-data x-init="setTimeout(() => $el.classList.add('is-in'), 400)"
        class="opacity-0 translate-y-6 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 bg-white rounded-[48px] border border-slate-100 p-10 shadow-[0_12px_48px_rgba(0,0,0,0.06)] space-y-10"
    >
        <div class="space-y-3">
            <div class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Importe final</div>
            <div class="flex items-center justify-center gap-2">
                <span class="text-6xl font-black tracking-tighter text-slate-950">{{ number_format($booking->amount ?? 0, 0) }}€</span>
                <span
                    class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-black uppercase tracking-widest">Confirmado</span>
            </div>
        </div>

        <div class="space-y-4 pt-8 border-t border-slate-100">
            <p class="text-[15px] font-bold text-slate-900 leading-tight">
                Presenta esta pantalla al llegar a tu destino para disfrutar de la experiencia.
            </p>
            <div
                class="inline-block px-6 py-3 bg-slate-50 rounded-2xl font-mono text-sm font-black text-slate-950 border border-slate-100 uppercase">
                Ref: TX-{{ strtoupper(substr($booking->uuid ?? 'XXXX', 0, 4)) }}
            </div>
        </div>
    </div>

    <div class="space-y-6 pt-4" x-data x-init="setTimeout(() => $el.classList.add('is-in'), 600)">
        <div
            x-data x-init="setTimeout(() => $el.classList.add('is-in'), 700)"
            class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 bg-blue-50/50 p-6 rounded-[32px] border border-blue-100/50"
        >
            <p class="text-[13px] font-bold text-blue-700 leading-relaxed">
                ✨ Tu taxi te ha ayudado a descubrir esta experiencia increíble.
            </p>
        </div>

        <a href="{{ route('home') }}"
           class="opacity-0 translate-y-4 transition-all duration-[var(--motion-base)] ease-[var(--ease-springy)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 block w-full bg-slate-950 hover:bg-slate-900 text-white font-black py-6 rounded-[28px] shadow-[0_20px_40px_rgba(0,0,0,0.15)] transition-all hover:scale-[1.01] active:scale-[0.98] text-xl">
            Volver al inicio
        </a>

        <p class="text-[10px] font-bold text-slate-300 uppercase tracking-widest">
            Experiencia premium Lanzarote
        </p>
    </div>
</div>
