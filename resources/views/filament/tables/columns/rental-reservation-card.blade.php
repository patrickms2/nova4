<div class="p-4 bg-white border border-gray-200 rounded-2xl shadow-sm dark:border-gray-800 dark:bg-gray-900">
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-800">
                @if ($record->guest?->avatar_url)
                    <img src="{{ $record->guest->avatar_url }}" alt="" class="object-cover w-full h-full">
                @else
                    <x-filament::icon icon="heroicon-o-user" class="w-5 h-5 m-2.5 text-gray-400" />
                @endif
            </div>
            <div>
                <div class="font-semibold leading-tight">{{ $record->guest?->fullName() ?? 'Huésped' }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $record->rentalProperty?->name ?? '—' }}</div>
            </div>
        </div>
        <span @class([
            'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300' => $record->status === 'confirmed',
            'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' => $record->status === 'pending',
            'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300' => $record->status === 'cancelled',
        ])>
            {{ ucfirst($record->status) }}
        </span>
    </div>

    <div class="mt-4 text-sm text-gray-600 dark:text-gray-300">
        <div class="flex items-center justify-between">
            <span class="text-gray-500 dark:text-gray-400">Entrada</span>
            <span class="font-medium">{{ $record->check_in?->format('d M Y') }}</span>
        </div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-gray-500 dark:text-gray-400">Salida</span>
            <span class="font-medium">{{ $record->check_out?->format('d M Y') }}</span>
        </div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-gray-500 dark:text-gray-400">Noches</span>
            <span class="font-medium">{{ $record->nights() }}</span>
        </div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-gray-500 dark:text-gray-400">Huéspedes</span>
            <span class="font-medium">{{ $record->adults + $record->children }}</span>
        </div>
    </div>

    <div class="mt-4">
        <div class="flex items-center justify-between">
            <span class="text-sm text-gray-500 dark:text-gray-400">Facturado</span>
            <span class="font-semibold">€{{ number_format($record->amount, 2, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-sm text-gray-500 dark:text-gray-400">Comisiones</span>
            <span class="font-medium">€{{ number_format($record->channel_commission + $record->management_commission, 2, ',', '.') }}</span>
        </div>
        <div class="flex items-center justify-between mt-1">
            <span class="text-sm text-gray-500 dark:text-gray-400">Neto</span>
            <span class="font-semibold text-emerald-600 dark:text-emerald-400">€{{ number_format($record->payout - $record->cleaning_fee, 2, ',', '.') }}</span>
        </div>
    </div>

    <div class="mt-4">
        <span @class([
            'inline-flex items-center px-2 py-1 text-xs font-medium rounded-full',
            'bg-rose-100 text-rose-700 dark:bg-rose-900 dark:text-rose-300' => $record->channel === 'airbnb',
            'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' => $record->channel === 'booking',
            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300' => ! in_array($record->channel, ['airbnb', 'booking']),
        ])>
            {{ ucfirst($record->channel) }}
        </span>
    </div>
</div>
