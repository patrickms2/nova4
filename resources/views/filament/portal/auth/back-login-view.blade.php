<meta name="csrf-token" content="{{ csrf_token() }}">
<div
    class="min-h-screen text-zinc-100 antialiased">
    <!-- Fondo con patrón sutil -->

    <div class="absolute inset-0 opacity-90">
        <video autoplay muted loop id="bg-video">
            <source
                src="{{ asset('video/large-vecteezy_volcanic-landscape-near-timanfaya-lanzarote-canary-islands_15811098_large.mp4') }}"
                type="video/mp4">
        </video>
    </div>

    <!-- Contenido principal -->
    <div class="w-full max-w-md relative z-10">

        <!-- Botones de Acceso Rápido - Más prominentes -->

        <!-- Formulario de login -->
        <div id="login"
             class="relative z-10 w-full max-w-3xl rounded-3xl border border-white/10 dark:bg-white/10 bg-white/90 text-white p-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-8">
            @if(env('SWITH_PANELS', false))
                <div class="mb-4">
                    <a href="{{ route('login') }}"
                       class="inline-flex items-center gap-2 text-sm text-white/80 hover:text-white transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Volver al selector de paneles
                    </a>
                </div>
            @endif

            <div class="flex justify-between items-center mb-6">
                <div class="flex items-center gap-2">
                    <a href="/login"><img src="{{ asset('img/logo.png') }}" alt="Taxilanz" class="h-12 object-contain"></a>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Theme Toggle Button -->
                    <button
                        type="button"
                        onclick="toggleTheme()"
                        class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                        title="Cambiar tema"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </button>
                    <!-- Clear Cache Button -->
                    <button
                        type="button"
                        onclick="clearCache()"
                        class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                        title="Limpiar cache"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                Portal Taxistas
            </h2>

            <p class="mt-2 text-center text-sm text-gray-400">
                Accede con tu NIF o Email
            </p>

            <form wire:submit.prevent="authenticate">
     

                <div class="mb-4 mt-4 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        wire:click="quickLogin('emp')"
                        data-portal-action
                        data-portal-pending-label="Quick login EMP"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-green-400/30 backdrop-blur-sm"
                        title="EMP"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        EMP
                    </button>
                    <button
                        type="button"
                        wire:click="quickLogin('jma')"
                        data-portal-action
                        data-portal-pending-label="Quick login JMA"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-green-400/30 backdrop-blur-sm"
                        title="JMA"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        JMA
                    </button>

                    <button
                        type="button"
                        wire:click="quickLogin('pms')"
                        data-portal-action
                        data-portal-pending-label="Quick login PMS"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-teal-400/30 backdrop-blur-sm"
                        title="PMS"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        PMS
                    </button>
                    <div class="flex items-center gap-2">
                        <!-- Theme Toggle Button -->
                        <button
                            type="button"
                            onclick="toggleTheme()"
                            class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                            title="Cambiar tema"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </button>
                        <!-- Clear Cache Button -->
                        <button
                            type="button"
                            onclick="clearCache()"
                            class="p-2 rounded-lg bg-white/10 hover:bg-white/20 text-white transition-colors"
                            title="Limpiar cache"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <x-filament::button
                    type="submit"
                    class="w-full bg-red-600 hover:bg-red-700"
                >
                    Acceder al Portal
                </x-filament::button>
            </form>


        </div>
    </div>
</div>

<script>
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

    // Clear Cache Function
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

    // Initialize theme on load
    document.addEventListener('DOMContentLoaded', function () {
        const savedTheme = localStorage.getItem('theme') || 'dark';
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
