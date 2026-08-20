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
             class="relative z-10 w-full max-w-3xl rounded-3xl border border-white/10 dark:bg-white/90 bg-white/10 text-white p-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-8">
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
                                  d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
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
                App Gestión
            </h2>

            <p class="mt-2 text-center text-sm text-gray-400">
                Acceso para personal administrativo
            </p>

            <form wire:submit.prevent="authenticate" class="text-white">

                <div class="mb-4 mt-4 flex flex-wrap justify-end gap-2">
                    <button
                        type="button"
                        wire:click="quickLogin('admin')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-amber-400/30 backdrop-blur-sm"
                        title="Admin"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                        Admin
                    </button>

                    <button
                        type="button"
                        wire:click="quickLogin('laboral')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-blue-400/30 backdrop-blur-sm"
                        title="Laboral"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Laboral
                    </button>

                    <button
                        type="button"
                        wire:click="quickLogin('fiscal')"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-purple-400/30 backdrop-blur-sm"
                        title="Fiscal"
                    >
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                        Fiscal
                    </button>
                </div>

                <x-filament::button
                    type="submit"
                    class="w-full bg-blue-600 hover:bg-blue-700"
                >
                    Acceder al Panel
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
        fetch('/app/clear-cache-login', {
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
        const savedTheme = localStorage.getItem('theme') || 'light';
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
