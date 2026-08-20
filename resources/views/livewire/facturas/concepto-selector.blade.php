<div
    x-data="{
        open: @entangle('showResults').defer,
        focus() { $refs.searchInput.focus(); },
    }"
    class="space-y-2"
>
    <div class="relative">
        <div class="flex items-center gap-2 bg-white border border-slate-200 rounded-2xl px-3 py-1.5 shadow-sm focus-within:ring-2 focus-within:ring-teal-400 transition">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-teal-100 text-teal-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none"
                     viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-width="1.8" stroke-linecap="round"
                          stroke-linejoin="round"
                          d="M12 6v12m6-6H6"/>
                </svg>
            </span>

            <input
                x-ref="searchInput"
                type="text"
                wire:model.debounce.250ms="search"
                @focus="open = true"
                placeholder="Concepto..."
                class="flex-1 border-0 bg-transparent text-xs focus:outline-none focus:ring-0 placeholder-slate-400"
            />

            @if($selectedConcepto)
                <span class="text-[10px] px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-100">
                    {{ $selectedConcepto->id }}
                </span>
            @endif
        </div>

        <div
            x-show="open"
            x-cloak
            @click.away="open = false"
            class="absolute z-30 mt-1 w-full rounded-2xl border border-slate-200 bg-white shadow-lg shadow-teal-100/40 overflow-hidden"
        >
            @if(strlen($search) < 2)
                <div class="px-3 py-2 text-[11px] text-slate-400">
                    Escribe al menos 2 letras…
                </div>
            @else
                @if($this->resultados->isEmpty())
                    <div class="px-3 py-2 text-[11px] text-slate-400">
                        Sin coincidencias para <strong>{{ $search }}</strong>
                    </div>
                @else
                    <div class="max-h-64 overflow-y-auto divide-y divide-slate-50">
                        @foreach($this->resultados as $c)
                            <button
                                type="button"
                                wire:click="selectConcepto({{ $c->id }})"
                                class="w-full text-left px-3 py-2 hover:bg-teal-50 transition flex gap-2"
                            >
                                <div>
                                    <div class="h-7 w-7 rounded-full bg-gradient-to-br from-teal-400 to-emerald-500 flex items-center justify-center text-[10px] font-semibold text-white shadow-sm">
                                        {{ mb_substr($c->concepto, 0, 2) }}
                                    </div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold text-slate-800 truncate">
                                            {{ $c->concepto }}
                                        </p>
                                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-50 text-slate-500 border border-slate-100">
                                            #{{ $c->id }}
                                        </span>
                                    </div>

                                    <div class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[10px] text-slate-500">
                                        @if($c->grupo)
                                            <span>🏷 {{ $c->grupo }}</span>
                                        @endif
                                        @if($c->unidad)
                                            <span>📦 {{ $c->unidad }}</span>
                                        @endif
                                        <span class="font-semibold text-teal-700">
                                            💶 {{ number_format($c->precio, 2) }} €
                                        </span>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if($selectedConcepto)
        <div class="rounded-xl border border-teal-100 bg-teal-50/60 px-3 py-2 text-[11px] text-teal-900 flex items-start gap-2">
            <div>
                <div class="h-6 w-6 rounded-full bg-teal-500 flex items-center justify-center text-[10px] font-bold text-white shadow">
                    {{ mb_substr($selectedConcepto->concepto,0,2) }}
                </div>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-semibold truncate">
                    {{ $selectedConcepto->concepto }}
                </p>
                <div class="mt-0.5 flex flex-wrap gap-x-3 gap-y-0.5">
                    @if($selectedConcepto->grupo)
                        <span>🏷 {{ $selectedConcepto->grupo }}</span>
                    @endif
                    @if($selectedConcepto->unidad)
                        <span>📦 {{ $selectedConcepto->unidad }}</span>
                    @endif
                    <span>💶 {{ number_format($selectedConcepto->precio, 2) }} €</span>
                </div>
            </div>
        </div>
    @endif
</div>
