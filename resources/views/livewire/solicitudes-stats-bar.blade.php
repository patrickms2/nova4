<div wire:poll.15s="loadStats">
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {{-- TOTAL --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Total</div>
                @if($loading)
                    <x-filament::loading-indicator class="h-4 w-4 text-blue-500" />
                @else
                    <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse" title="En vivo"></div>
                @endif
            </div>
            <div class="mt-2 flex items-baseline gap-3">
                <div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ number_format($totalHoy) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">HOY</div>
                </div>
                <div class="text-gray-200 dark:text-gray-700">/</div>
                <div>
                    <div class="text-2xl font-black text-gray-700 dark:text-gray-200">{{ number_format($totalMes) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">MES</div>
                </div>
            </div>
        </div>

        {{-- TIAS --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">Tías</div>
            <div class="mt-2 flex items-baseline gap-3">
                <div>
                    <div class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ number_format($stats['Tías']['hoy'] ?? 0) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">HOY</div>
                </div>
                <div class="text-gray-200 dark:text-gray-700">/</div>
                <div>
                    <div class="text-2xl font-black text-gray-700 dark:text-gray-200">{{ number_format($stats['Tías']['mes'] ?? 0) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">MES</div>
                </div>
            </div>
        </div>

        {{-- YAIZA --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-semibold uppercase tracking-wider text-emerald-600 dark:text-emerald-400">Yaiza</div>
            <div class="mt-2 flex items-baseline gap-3">
                <div>
                    <div class="text-2xl font-black text-emerald-600 dark:text-emerald-400">{{ number_format($stats['Yaiza']['hoy'] ?? 0) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">HOY</div>
                </div>
                <div class="text-gray-200 dark:text-gray-700">/</div>
                <div>
                    <div class="text-2xl font-black text-gray-700 dark:text-gray-200">{{ number_format($stats['Yaiza']['mes'] ?? 0) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">MES</div>
                </div>
            </div>
        </div>

        {{-- TEGUISE --}}
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-xs font-semibold uppercase tracking-wider text-amber-600 dark:text-amber-400">Teguise</div>
            <div class="mt-2 flex items-baseline gap-3">
                <div>
                    <div class="text-2xl font-black text-amber-600 dark:text-amber-400">{{ number_format($stats['Teguise']['hoy'] ?? 0) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">HOY</div>
                </div>
                <div class="text-gray-200 dark:text-gray-700">/</div>
                <div>
                    <div class="text-2xl font-black text-gray-700 dark:text-gray-200">{{ number_format($stats['Teguise']['mes'] ?? 0) }}</div>
                    <div class="text-[10px] font-medium text-gray-400">MES</div>
                </div>
            </div>
        </div>
    </div>
</div>
