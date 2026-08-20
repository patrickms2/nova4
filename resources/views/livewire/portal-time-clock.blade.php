<div
    x-data="{ open: $wire.entangle('showDropdown'), now: '{{ now()->format('H:i:s') }}' }"
    x-init="setInterval(() => { now = new Date().toLocaleTimeString('es-ES', { hour12: false }) }, 1000)"
    @open-spotlight.window="open = false"
    @toggle-time-clock.window="open = !open"
    @open-modal.window="open = false"
    @close-modal.window="open = false"
    class="relative"
>
    {{-- Topbar button --}}
    @php
        $att = $this->todayAttendance;
        $isIn = $att['checked_in'];
    @endphp

    <button
        type="button"
        @click="open = !open"
        class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm transition
               {{ $isIn
                   ? 'text-emerald-400 hover:bg-emerald-500/10'
                   : 'text-zinc-400 hover:bg-white/5' }}"
        title="{{ $isIn ? 'Fichado — Entrada: ' . $att['start'] : 'Sin fichar' }}"
    >
        {{-- Dot indicator --}}
        <span class="relative flex h-2.5 w-2.5">
            @if($isIn)
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-500"></span>
            @else
                <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-zinc-500"></span>
            @endif
        </span>

        {{-- Clock icon --}}
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
        </svg>

        {{-- Live time (desktop only) --}}
        <span class="hidden sm:inline font-mono text-xs tabular-nums" x-text="now"></span>
    </button>

    {{-- Dropdown panel --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        @click.outside="open = false"
        class="absolute right-0 top-full z-[70] mt-3 w-72 rounded-xl border border-white/10 bg-zinc-900/95 p-4 shadow-2xl backdrop-blur-xl"
        style="display: none;"
    >
        {{-- Header --}}
        <div class="mb-3 text-center">
            <p class="text-3xl font-bold text-white tabular-nums font-mono" x-text="now"></p>
            <p class="mt-1 text-xs text-zinc-400">{{ now()->translatedFormat('l, j F Y') }}</p>
        </div>

        <div class="h-px bg-white/10 my-3"></div>

        {{-- Status card --}}
        @if($isIn)
            <div class="rounded-lg bg-emerald-500/10 border border-emerald-500/20 p-3 text-center space-y-1">
                <div class="flex items-center justify-center gap-2">
                    <svg class="h-4 w-4 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                    </svg>
                    <span class="text-sm font-semibold text-emerald-400">Fichado</span>
                </div>
                <p class="text-xs text-emerald-300/70">Entrada a las {{ $att['start'] }}</p>
            </div>

            <button
                wire:click="checkOut"
                wire:loading.attr="disabled"
                class="mt-3 w-full rounded-lg bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-red-500 disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <svg class="h-4 w-4" wire:loading.class="hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15m-3 0-3-3m0 0 3-3m-3 3H15"/>
                </svg>
                <svg class="h-4 w-4 animate-spin hidden" wire:loading.class.remove="hidden" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Registrar Salida
            </button>
        @elseif($att['end'])
            {{-- Already completed today --}}
            <div class="rounded-lg bg-blue-500/10 border border-blue-500/20 p-3 text-center space-y-1">
                <span class="text-sm font-semibold text-blue-400">Jornada completa</span>
                <p class="text-xs text-blue-300/70">{{ $att['start'] }} — {{ $att['end'] }}</p>
            </div>
        @else
            {{-- Not checked in --}}
            <div class="rounded-lg bg-zinc-800 border border-white/5 p-3 text-center">
                <span class="text-sm text-zinc-400">Sin fichar hoy</span>
            </div>

            <button
                wire:click="checkIn"
                wire:loading.attr="disabled"
                class="mt-3 w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-500 disabled:opacity-50 flex items-center justify-center gap-2"
            >
                <svg class="h-4 w-4" wire:loading.class="hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                </svg>
                <svg class="h-4 w-4 animate-spin hidden" wire:loading.class.remove="hidden" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Registrar Entrada
            </button>
        @endif

        {{-- Month stats --}}
        @php $stats = $this->monthStats; @endphp
        <div class="mt-3 grid grid-cols-3 gap-2 text-center">
            <div class="rounded-lg bg-white/5 p-2">
                <p class="text-lg font-bold text-emerald-400">{{ $stats['present'] }}</p>
                <p class="text-[10px] text-zinc-500">Presente</p>
            </div>
            <div class="rounded-lg bg-white/5 p-2">
                <p class="text-lg font-bold text-amber-400">{{ $stats['late'] }}</p>
                <p class="text-[10px] text-zinc-500">Tarde</p>
            </div>
            <div class="rounded-lg bg-white/5 p-2">
                <p class="text-lg font-bold text-red-400">{{ $stats['absent'] }}</p>
                <p class="text-[10px] text-zinc-500">Ausente</p>
            </div>
        </div>

        <button
            type="button"
            class="mt-3 w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2 text-sm font-semibold text-white/80 transition hover:bg-white/10"
            x-on:click="$dispatch('open-spotlight'); open = false"
        >
            Spotlight
        </button>
                            <div class="tl-s2 p-2 mb-4 mt-4 flex flex-wrap justify-center gap-2">
                        <div class="flex items-center gap-2">
                   
                    <!-- Clear Cache Button -->

                </div>
                    </div>
    </div>
      <script>
        function clearCache() {
            fetch('/portal/clear-cache-login', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            })
                .then(response => response.json())
                .then(data => {
                    // Show notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                    notification.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            ${data.message || 'Cache limpiada correctamente'}
        `;
                    document.body.appendChild(notification);

                    // Remove notification after 3 seconds
                    setTimeout(() => {
                        notification.remove();
                    }, 3000);

                    // Clear browser cache
                    if ('caches' in window) {
                        caches.keys().then(function (names) {
                            names.forEach(function (name) {
                                caches.delete(name);
                            });
                        });
                    }
                })
                .catch(error => {
                    console.error('Error clearing cache:', error);
                    // Show error notification
                    const notification = document.createElement('div');
                    notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                    notification.innerHTML = `
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Error al limpiar la cache
        `;
                    document.body.appendChild(notification);

                    setTimeout(() => {
                        notification.remove();
                    }, 3000);
                });
        }

        // Theme Toggle Function
        function toggleTheme() {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';

            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);

            // Update button icon
            const themeButton = document.querySelector('[onclick="toggleTheme()"] svg path');
            if (newTheme === 'dark') {
                themeButton.setAttribute('d', 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z');
            } else {
                themeButton.setAttribute('d', 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z');
            }
        }

        // Initialize theme on load
        document.addEventListener('DOMContentLoaded', function () {
            const savedTheme = 'light';//localStorage.getItem('theme') || 'dark';
            document.documentElement.setAttribute('data-theme', savedTheme);

            // Update icon based on saved theme
            if (savedTheme === 'dark') {
                const themeButton = document.querySelector('[onclick="toggleTheme()"] svg path');
                if (themeButton) {
                    themeButton.setAttribute('d', 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z');
                }
            }
        });
    </script>
    <?php
// Theme Toggle Function
    function toggleTheme() {
        $currentTheme = $_COOKIE['theme'] ?? 'dark';
        $newTheme = $currentTheme === 'dark' ? 'light' : 'dark';
        setcookie('theme', $newTheme, time() + (10 * 365 * 24 * 60 * 60)); // Set cookie for 10 years

        // Update button icon
        ?>
    <script>
        const themeButton = document.querySelector('[onclick="toggleTheme()"] svg path');
        if ('<?php echo $newTheme; ?>' === 'dark') {
            themeButton.setAttribute('d', 'M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z');
        } else {
            themeButton.setAttribute('d', 'M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z');
        }
    </script>
        <?php
    }

// Clear Cache Function
    function clearCache() {
        $csrfToken = $_POST['csrf_token'] ?? '';
        $url = '/portal/clear-cache-login';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'X-CSRF-TOKEN: ' . $csrfToken,
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        ?>
    <script>
        // Show notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
        notification.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                ${data.message || 'Cache limpiada correctamente'}
            `;
        document.body.appendChild(notification);

        // Remove notification after 3 seconds
        setTimeout(() => {
            notification.remove();
        }, 3000);

        // Clear browser cache
        if ('caches' in window) {
            caches.keys().then(function (names) {
                names.forEach(function (name) {
                    caches.delete(name);
                });
            });
        }
    </script>
        <?php
    } else {
        ?>
    <script>
        console.error('Error clearing cache: <?php echo $error; ?>');
        // Show error notification
        const notification = document.createElement('div');
        notification.className = 'fixed top-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
        notification.innerHTML = `
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                Error al limpiar la cache
            `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    </script>
        <?php
    }
    }

// Initialize theme on load
    ?>
    </script>
</div>
