<div class="grid grid-cols-3 gap-6">
    <div class="bg-gray-50 dark:bg-gray-900/50 rounded-lg p-6">
        <div class="flex items-center justify-between mb-2 h-2">
            @if ($status !== 'idle')
                <span class="inline-flex items-center gap-1.5 text-xs {{ $status === 'calling' ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">
                    @if ($status === 'calling')
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span>Calling...</span>
                    @elseif ($status === 'hangup')
                        <x-filament::icon icon="heroicon-o-phone-x-mark" class="w-3 h-3" />
                        <span>Call ended</span>
                    @else
                        <x-filament::icon icon="heroicon-o-pencil" class="w-3 h-3" />
                        <span>...</span>
                    @endif
                </span>
            @endif
            @if ($muted)
                <x-filament::icon icon="heroicon-c-speaker-x-mark" class="w-4 h-4 text-gray-400" />
            @endif
        </div>
        <div class="text-center">
            <input
                type="text"
                wire:model="phoneNumber"
                placeholder="Telefonnummer"
                class="w-full text-2xl text-center bg-transparent border-b-2 border-gray-300 dark:border-gray-600 focus:border-primary-500 focus:outline-none py-2 dark:text-white transition-colors caret-transparent"
                readonly
            />
        </div>
    </div>

    <div class="grid grid-cols-3 gap-6">
        @for ($i = 1; $i <= 9; $i++)
            <button
                wire:click="append('{{ $i }}')"
                class="aspect-square rounded-full bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-white text-2xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
            >
                {{ $i }}
            </button>
        @endfor

        <button
            wire:click="append('+')"
            class="aspect-square rounded-full bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-white text-2xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
        >
            +
        </button>

        <button
            wire:click="append('0')"
            class="aspect-square rounded-full bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-white text-2xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
        >
            0
        </button>

        <button
            wire:click="append('#')"
            class="aspect-square rounded-full bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-white text-2xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
        >
            #
        </button>
    </div>

    <div class="flex items-center justify-center gap-4">
        @if (!empty($phoneNumber))
            <button
                wire:click="backspace"
                class="p-4 aspect-square rounded-full bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-white text-2xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                title="Delete last digit"
            >
                <x-filament::icon icon="heroicon-o-backspace" class="w-6 h-6" />
            </button>
        @endif

        <button
            wire:click="clear"
            class="aspect-square rounded-full bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-white text-2xl font-semibold hover:bg-gray-300 dark:hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
            title="Clear"
        >
            <x-filament::icon icon="heroicon-o-x-circle" class="w-6 h-6" />
        </button>

        @if ($status === 'idle' || $status === 'editing')
            <button
                wire:click="startCall"
                {{ $phoneNumber ? '' : 'disabled' }}
                class="p-4 rounded-full {{ $phoneNumber ? 'bg-green-500 hover:bg-green-600 text-white' : 'bg-gray-300 dark:bg-gray-800 text-gray-500 cursor-not-allowed' }} focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                title="Call"
            >
                <x-filament::icon icon="heroicon-o-phone" class="w-6 h-6" />
            </button>
        @else
            <button
                wire:click="endCall"
                class="p-4 rounded-full bg-red-500 hover:bg-red-600 text-white focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
                title="End call"
            >
                <x-filament::icon icon="heroicon-o-phone-x-mark" class="w-6 h-6" />
            </button>
        @endif

        <button
            wire:click="toggleMute"
            class="p-4 rounded-full {{ $muted ? 'bg-primary-500 text-white' : 'bg-gray-300 dark:bg-gray-800 text-gray-900 dark:text-gray-300' }} hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all"
            title="{{ $muted ? 'Unmute' : 'Mute' }}"
        >
            @if ($muted)
                <x-filament::icon icon="heroicon-o-speaker-wave" class="w-6 h-6" />
            @else
                <x-filament::icon icon="heroicon-c-speaker-x-mark" class="w-6 h-6" />
            @endif
        </button>
    </div>
</div>
