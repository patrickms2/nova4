<div
    class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#fff7ed,_transparent_30%),radial-gradient(circle_at_top_right,_#ecfeff,_transparent_35%),#f4f7fb]">
    <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 py-6">

        {{-- PASO 1: TAXI CONFIRMADO --}}
        @if (! $showSuggestions)
            <section
                x-data
                x-init="requestAnimationFrame(() => $el.classList.add('is-in'))"
                class="opacity-0 translate-y-6 scale-[0.985] blur-[2px] transition-all duration-[var(--motion-slow)] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 [&.is-in]:scale-100 [&.is-in]:blur-0 rounded-[32px] bg-gradient-to-br from-blue-600 via-blue-500 to-cyan-400 p-5 text-white shadow-[0_24px_60px_rgba(37,99,235,0.30)]"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-white/70">
                            Taxi confirmado
                        </div>
                        <h1 class="mt-2 text-4xl font-black leading-none">
                            Llega en {{ $ride->eta_minutes }} min
                        </h1>
                        <p class="mt-3 text-sm font-medium leading-relaxed text-white/85">
                            {{ $ride->pickup_label }} → {{ $ride->destination_label }}
                        </p>
                    </div>

                    <div class="rounded-2xl bg-white/15 px-4 py-3 text-3xl backdrop-blur-md shadow-sm">
                        🚕
                    </div>
                </div>
            </section>
        @endif

        {{-- PASO 2: MICRO-INTENCIÓN --}}
        @if (! $showSuggestions)
            <section
                x-show="!$wire.showSuggestions"
                x-transition:enter="transition duration-[var(--motion-soft)] ease-[var(--ease-out-soft)]"
                x-transition:enter-start="opacity-0 translate-y-6"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition duration-[var(--motion-fast)] ease-[var(--ease-in-soft)]"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-4"
                class="mt-6 rounded-[32px] border border-white/40 bg-white/72 p-6 backdrop-blur-xl shadow-[0_12px_40px_rgba(15,23,42,0.08)]"
            >
                <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                    Mientras llega el taxi...
                </div>

                <h2 class="mt-2 text-2xl font-black text-slate-950">
                    ¿Qué te interesa ahora?
                </h2>

                <p class="mt-2 text-sm leading-6 text-slate-500">
                    Te mostramos opciones pensadas para tu llegada a {{ $ride->destination_label }}.
                </p>

                <div class="mt-6 grid grid-cols-2 gap-3">
                    @foreach($interestOptions as $key => $label)
                        <button
                            wire:click="selectInterest('{{ $key }}')"
                            class="rounded-2xl border px-4 py-4 text-sm font-bold transition duration-[var(--motion-base)] ease-[var(--ease-springy)]
                            {{ $interestType === $key
                                ? 'border-blue-200 bg-blue-50 text-blue-700 shadow-sm scale-[1.03]'
                                : 'border-white/50 bg-white/70 text-slate-700 backdrop-blur-md hover:bg-white hover:scale-[1.02] active:scale-[0.96]' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                @error('interestType')
                <p class="mt-4 text-xs font-semibold text-rose-500">{{ $message }}</p>
                @enderror
            </section>
        @endif

        {{-- PASO 3: PROPUESTAS --}}
        @if ($showSuggestions)
            <section
                x-show="$wire.showSuggestions"
                x-transition:enter="transition duration-[var(--motion-soft)] ease-[var(--ease-out-soft)]"
                x-transition:enter-start="opacity-0 translate-y-8"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mt-8 space-y-6"
            >
                <div class="flex items-center justify-between px-1">
                    <button
                        wire:click="backToInterestSelection"
                        class="inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.24em] text-slate-400 transition hover:text-slate-600"
                    >
                        <span aria-hidden="true">←</span>
                        <span>Volver</span>
                    </button>

                    <div class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                        Seleccionado para ti
                    </div>
                </div>

                <div class="px-1">
                    @if($interestType)
                        <div
                            class="mt-2 inline-flex items-center gap-2 rounded-full bg-emerald-50 px-3 py-2 text-[11px] font-bold uppercase tracking-[0.2em] text-emerald-700">
                            <span>Perfil</span>
                            <span
                                class="text-emerald-900">{{ $interestOptions[$interestType] ?? ucfirst($interestType) }}</span>
                        </div>
                    @endif

                    <h3 class="mt-3 text-3xl font-black leading-tight text-slate-950">
                        Perfectas para tu llegada a {{ $ride->destination_label }}
                    </h3>
                </div>

                <div class="space-y-4">
                    @foreach($ride->recommendations as $recommendation)
                        <div
                            x-data
                            x-init="setTimeout(() => $el.classList.add('is-in'), 120 + ({{ $loop->index }} * 90))"
                            class="opacity-0 translate-y-5 scale-[0.99] transition-all duration-[480ms] ease-[var(--ease-out-soft)] [&.is-in]:opacity-100 [&.is-in]:translate-y-0 [&.is-in]:scale-100 rounded-[28px] border border-white/40 bg-white/78 p-4 backdrop-blur-xl shadow-[0_12px_40px_rgba(15,23,42,0.06)]"
                        >
                            <div class="flex gap-4">
                                <div class="relative h-24 w-24 flex-shrink-0">
                                    <img
                                        src="{{ $recommendation->offer->image_url }}"
                                        alt="{{ $recommendation->offer->title }}"
                                        class="h-full w-full rounded-2xl object-cover shadow-sm"
                                    >

                                    <div
                                        class="absolute -right-2 -top-2 flex h-7 w-7 items-center justify-center rounded-full bg-slate-950 text-[10px] font-black text-white shadow-lg">
                                        #{{ $recommendation->position }}
                                    </div>
                                </div>

                                <div class="min-w-0 flex-1 py-1">
                                    <h4 class="text-lg font-black leading-tight text-slate-950">
                                        {{ $recommendation->offer->title }}
                                    </h4>

                                    <p class="mt-1 line-clamp-2 text-xs font-medium leading-relaxed text-slate-500">
                                        {{ $recommendation->offer->excerpt }}
                                    </p>

                                    <div
                                        class="mt-3 flex flex-wrap gap-2 text-[10px] font-bold uppercase tracking-wider text-slate-600">
                                        <span class="rounded-lg bg-slate-100/80 px-2 py-1">
                                            €{{ number_format($recommendation->offer->price_from, 0) }}
                                        </span>

                                        <span class="rounded-lg bg-slate-100/80 px-2 py-1">
                                            {{ $recommendation->offer->duration_minutes }} min
                                        </span>

                                        @php
                                            $reasonText = match($recommendation->primary_reason) {
                                                'matched_gastronomy_interest' => 'Sabores que definen Lanzarote',
                                                'matched_leisure_interest' => 'Planes para disfrutar tu llegada',
                                                'matched_shopping_interest' => 'Productos con historia local',
                                                'matched_information_interest' => 'Rincones que marcan la diferencia',
                                                'strong_local_value' => 'Muy nuestro · Auténtico',
                                                'top_near_destination' => 'Muy cerca de tu destino',
                                                'fits_available_time' => 'Encaja con tu momento',
                                                default => 'Optimizado para tu llegada'
                                            };
                                        @endphp

                                        <span class="rounded-lg bg-emerald-50 px-2 py-1 text-emerald-700">
                                            🔥 {{ $reasonText }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <a
                                href="{{ route('offers.show', ['offer' => $recommendation->offer->slug, 'ride_id' => $ride->id, 'recommendation_id' => $recommendation->id]) }}"
                                class="mt-4 block w-full rounded-2xl bg-slate-950 px-5 py-4 text-center text-sm font-bold text-white shadow-[0_12px_30px_rgba(15,23,42,0.20)] transition duration-300 hover:scale-[1.01] active:scale-[0.985]"
                            >
                                Ver detalles y reservar
                            </a>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</div>
