<x-filament-panels::page class="rental-contract-simulator">
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-filament::card class="lg:col-span-1">
            <h2 class="text-base font-semibold mb-4">Entradas</h2>

            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Total cobrado (€)</label>
                    <input type="number" step="0.01" wire:model.live="totalAmount" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Comisión canal (€)</label>
                    <input type="number" step="0.01" wire:model.live="channelCommission" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Adultos</label>
                        <input type="number" wire:model.live="adults" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Niños</label>
                        <input type="number" wire:model.live="children" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Noches</label>
                    <input type="number" wire:model.live="nights" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <hr class="border-gray-200 dark:border-gray-700">

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Limpieza por estancia (€)</label>
                    <input type="number" step="0.01" wire:model.live="cleaning" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Lavandería por huésped (€)</label>
                    <input type="number" step="0.01" wire:model.live="laundryPerGuest" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Welcome pack (€)</label>
                        <input type="number" step="0.01" wire:model.live="welcomePack" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Damage waiver (€)</label>
                        <input type="number" step="0.01" wire:model.live="damageWaiver" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">% comisión gestor</label>
                    <input type="number" step="0.01" wire:model.live="managerCommissionRate" class="mt-1 block w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 sm:text-sm">
                </div>
            </div>
        </x-filament::card>

        <x-filament::card class="lg:col-span-2">
            <h2 class="text-base font-semibold mb-4">Resultados</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Alojamiento</div>
                    <div class="text-2xl font-semibold tracking-tight">€{{ number_format($this->results['accommodation'], 2, ',', '.') }}</div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Tasas a huésped</div>
                    <div class="text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">€{{ number_format($this->results['services'], 2, ',', '.') }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Limpieza, lavandería, welcome pack, damage waiver</div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Base comisionable</div>
                    <div class="text-2xl font-semibold tracking-tight">€{{ number_format($this->results['commissionableBase'], 2, ',', '.') }}</div>
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">Alojamiento - comisión canal</div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Comisión gestor ({{ $managerCommissionRate }}%)</div>
                    <div class="text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">€{{ number_format($this->results['managerCommission'], 2, ',', '.') }}</div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Neto propietario</div>
                    <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">€{{ number_format($this->results['netOwner'], 2, ',', '.') }}</div>
                </div>

                <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Comisiones totales</div>
                    <div class="text-2xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">€{{ number_format($this->results['totalCommissions'], 2, ',', '.') }}</div>
                </div>
            </div>

            <div class="mt-6 p-4 rounded-xl bg-primary-50 dark:bg-primary-900/20">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-primary-900 dark:text-primary-100">% del cobro total que se queda el propietario</span>
                    <span class="text-2xl font-bold text-primary-700 dark:text-primary-300">
                        {{ $this->totalAmount > 0 ? number_format(($this->results['netOwner'] / $this->totalAmount) * 100, 1, ',', '.') : '0' }}%
                    </span>
                </div>
                <div class="w-full h-2 mt-3 bg-primary-200 dark:bg-primary-800 rounded-full overflow-hidden">
                    <div class="h-full bg-primary-600 rounded-full" style="width: {{ $this->totalAmount > 0 ? min(100, max(0, ($this->results['netOwner'] / $this->totalAmount) * 100)) : 0 }}%"></div>
                </div>
            </div>
        </x-filament::card>
    </div>
</x-filament-panels::page>
