<div
    x-data
    x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
    class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 px-6 py-8 space-y-8"
    wire:key="booking-page-wrapper"
    x-cloak
>
    <div class="space-y-4" wire:key="booking-page-header">
        <h1
            x-data x-init="setTimeout(() => $el.classList.add('is-in'), 100)"
            class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 text-4xl font-black tracking-tight text-slate-900 leading-[0.9]"
        >
            Fricción cero.
        </h1>
        <p
            x-data x-init="setTimeout(() => $el.classList.add('is-in'), 200)"
            class="opacity-0 translate-y-4 transition-all duration-[var(--motion-soft)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 text-slate-500 font-bold text-lg leading-tight"
        >
            Cerrar en < 20 segundos
        </p>
    </div>

    <form wire:submit="submit" class="space-y-6" wire:key="booking-form">
        <div class="space-y-4">
            <div
                class="bg-white rounded-[40px] border border-slate-100 p-8 shadow-[0_12px_32px_rgba(0,0,0,0.04)] space-y-6">

                <label class="block group">
                    <span
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1 mb-2 block transition-colors group-focus-within:text-slate-900">Tu nombre</span>
                    <input type="text" wire:model.defer="customerName" placeholder="Nombre completo"
                           class="w-full bg-slate-50 border-2 border-transparent rounded-[24px] py-5 px-6 text-lg font-bold text-slate-900 placeholder:text-slate-300 focus:bg-white focus:border-slate-900 focus:ring-0 transition-all outline-none">
                    @error('customerName') <p
                        class="mt-2 text-xs text-rose-500 font-bold ml-1">{{ $message }}</p> @enderror
                </label>

                <div class="grid grid-cols-1 gap-6">
                    <label class="block group">
                        <span
                            class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1 mb-2 block transition-colors group-focus-within:text-slate-900">Email</span>
                        <input type="email" wire:model.defer="customerEmail" placeholder="tu@email.com"
                               class="w-full bg-slate-50 border-2 border-transparent rounded-[24px] py-5 px-6 text-lg font-bold text-slate-900 placeholder:text-slate-300 focus:bg-white focus:border-slate-900 focus:ring-0 transition-all outline-none">
                    </label>

                    <label class="block group">
                        <span
                            class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1 mb-2 block transition-colors group-focus-within:text-slate-900">Teléfono</span>
                        <input type="tel" wire:model.defer="customerPhone" placeholder="+34 600 000 000"
                               class="w-full bg-slate-50 border-2 border-transparent rounded-[24px] py-5 px-6 text-lg font-bold text-slate-900 placeholder:text-slate-300 focus:bg-white focus:border-slate-900 focus:ring-0 transition-all outline-none">
                    </label>
                </div>

                <label class="block group">
                    <span
                        class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] ml-1 mb-2 block transition-colors group-focus-within:text-slate-900">Personas</span>
                    <div class="relative">
                        <select wire:model.defer="partySize"
                                class="w-full bg-slate-50 border-2 border-transparent rounded-[24px] py-5 px-6 text-lg font-bold text-slate-900 focus:bg-white focus:border-slate-900 focus:ring-0 transition-all appearance-none outline-none">
                            @for($i=1; $i<=10; $i++)
                                <option value="{{ $i }}">{{ $i }} {{ $i === 1 ? 'persona' : 'personas' }}</option>
                            @endfor
                        </select>
                        <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                            ↓
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <div class="space-y-4 pt-2" x-data x-init="setTimeout(() => $el.classList.add('is-in'), 400)"
             wire:key="booking-cta">
            <button type="submit"
                    class="opacity-0 translate-y-4 transition-all duration-[var(--motion-base)] ease-[var(--ease-springy)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 w-full bg-slate-950 hover:bg-slate-900 text-white font-black py-6 rounded-[28px] shadow-[0_20px_40px_rgba(0,0,0,0.2)] transition-all hover:scale-[1.01] active:scale-[0.98] text-xl">
                Confirmar reserva · {{ number_format($offer->price_from, 0) }}€
            </button>
            <div
                x-data x-init="setTimeout(() => $el.classList.add('is-in'), 550)"
                class="opacity-0 transition-opacity duration-[var(--motion-soft)] [&.is-in]:opacity-100 text-center"
            >
                <p class="text-slate-500 font-bold text-sm">Pago en local · sin compromiso</p>
                <p class="mt-2 text-[10px] font-bold text-slate-300 uppercase tracking-widest">
                    Garantía de calidad TAXILANZ
                </p>
            </div>
        </div>
    </form>
</div>
