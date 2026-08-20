<div x-data="{ phoneNumber: '', status: 'idle', muted: false }" class="space-y-6">
    <!-- Display panel -->
    <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-[#454546] shadow-sm p-6">
        <div class="flex items-center justify-between mb-2 h-4">
            <template x-if="status !== 'idle'">
                <span
                    class="inline-flex items-center gap-1.5 text-xs font-medium"
                    :class="status === 'calling'
                        ? 'text-green-600 dark:text-green-400'
                        : 'text-gray-500 dark:text-gray-400'"
                >
                    <template x-if="status === 'calling'">
                        <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                        </span>
                        <span>Calling…</span>
                    </template>

                    <template x-if="status === 'hangup'">
                        <x-filament::icon icon="heroicon-o-phone-x-mark" class="w-3.5 h-3.5" />
                        <span>Call ended</span>
                    </template>

                    <template x-if="status === 'editing'">
                        <x-filament::icon icon="heroicon-o-pencil" class="w-3.5 h-3.5" />
                        <span>...</span>
                    </template>
                </span>
            </template>

            <template x-if="muted">
                <x-filament::icon icon="heroicon-c-speaker-x-mark" class="w-4 h-4 text-gray-400 dark:text-gray-500" />
            </template>
        </div>

        <div class="text-center">
            <input
                type="text"
                x-model="phoneNumber"
                placeholder="Telefonnummer"
                readonly
                tabindex="-1"
                class="
                    w-full
                    text-2xl
                    font-semibold
                    tracking-widest
                    text-center
                    bg-transparent
                    border-b-2
                    border-gray-300
                    dark:border-[#454546]
                    text-gray-900
                    dark:text-white
                    py-2
                    transition-colors

                    caret-transparent
                    cursor-default
                    outline-none
                    focus:outline-none
                    focus:border-primary-500
                "
            />
        </div>
    </div>

    <!-- Keypad -->
    <div class="grid grid-cols-3 gap-5">
        <template x-for="i in 9">
            <button
                @click="phoneNumber = phoneNumber + i"
                class="
                    aspect-square
                    rounded-full
                    bg-gray-100
                    dark:bg-gray-800
                    text-gray-900
                    dark:text-white
                    text-2xl
                    font-semibold

                    hover:bg-gray-200
                    dark:hover:bg-gray-700

                    focus:outline-none
                    focus-visible:ring-2
                    focus-visible:ring-primary-500/50

                    transition-all
                "
            >
                <span x-text="i"></span>
            </button>
        </template>

        <button class="key" @click="phoneNumber = phoneNumber + '+'">+</button>
        <button class="key" @click="phoneNumber = phoneNumber + '0'">0</button>
        <button class="key" @click="phoneNumber = phoneNumber + '#'">#</button>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-center gap-4">
        <button
            x-show="phoneNumber.length > 0"
            @click="phoneNumber = phoneNumber.slice(0, -1)"
            class="
                p-4 rounded-full
                bg-gray-100 dark:bg-gray-800
                text-gray-700 dark:text-gray-300
                hover:bg-gray-200 dark:hover:bg-gray-700

                focus:outline-none
                focus-visible:ring-2
                focus-visible:ring-primary-500/50

                transition-all
            "
            title="Delete last digit"
        >
            <x-filament::icon icon="heroicon-o-backspace" class="w-6 h-6" />
        </button>

        <button
            @click="phoneNumber = ''; status = 'idle'"
            class="
                p-4 rounded-full
                bg-gray-100 dark:bg-gray-800
                text-gray-700 dark:text-gray-300
                hover:bg-gray-200 dark:hover:bg-gray-700

                focus:outline-none
                focus-visible:ring-2
                focus-visible:ring-primary-500/50

                transition-all
            "
            title="Clear"
        >
            <x-filament::icon icon="heroicon-o-x-circle" class="w-6 h-6" />
        </button>

        <template x-if="status === 'idle' || status === 'editing'">
            <button
                @click="if (phoneNumber) { status = 'calling' }"
                :disabled="!phoneNumber"
                class="p-4 rounded-full transition-all"
                :class="phoneNumber
                    ? 'bg-green-500 hover:bg-green-600 text-white'
                    : 'bg-gray-300 dark:bg-gray-800 text-gray-500 cursor-not-allowed'"
                title="Call"
            >
                <x-filament::icon icon="heroicon-o-phone" class="w-6 h-6" />
            </button>
        </template>

        <template x-if="status !== 'idle' && status !== 'editing'">
            <button
                @click="status = 'hangup'"
                class="
                    p-4 rounded-full
                    bg-red-500 hover:bg-red-600
                    text-white

                    focus:outline-none
                    focus-visible:ring-2
                    focus-visible:ring-red-400/50

                    transition-all
                "
                title="End call"
            >
                <x-filament::icon icon="heroicon-o-phone-x-mark" class="w-6 h-6" />
            </button>
        </template>

        <button
            @click="muted = !muted"
            class="
                p-4 rounded-full
                transition-all

                focus:outline-none
                focus-visible:ring-2
                focus-visible:ring-primary-500/50
            "
            :class="muted
                ? 'bg-primary-500 text-white'
                : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-300'"
            :title="muted ? 'Unmute' : 'Mute'"
        >
            <template x-if="muted">
                <x-filament::icon icon="heroicon-o-speaker-wave" class="w-6 h-6" />
            </template>
            <template x-if="!muted">
                <x-filament::icon icon="heroicon-c-speaker-x-mark" class="w-6 h-6" />
            </template>
        </button>
    </div>
</div>
