@php
    $loginVideoWebmUrl = asset('video/login.webm');
    $loginVideoMp4Url = asset('video/login.mp4');
@endphp

<div
    class="fi-auth-layout">
    <div class="fi-auth-media-section">
        <div class="fi-auth-media-wrapper">
            <video autoplay="" loop="" muted="" playsinline="" preload="metadata" class="fi-auth-media">
                <source
                    src="{{ $loginVideoWebmUrl }}"
                    type="video/webm">
                <source
                    src="{{ $loginVideoMp4Url }}"
                    type="video/mp4">
            </video>
            <div class="fi-auth-media-overlay"></div>
        </div>

        <div class="fi-auth-media-content">
            <div id="login"
                 class="relative z-10 w-full max-w-sm rounded-3xl border border-white/10 bg-black/20 dark:bg-white/10 text-white p-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-8">
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
                        <a href="/login"><img src="{{ asset('img/logo.png') }}" alt="Taxilanz"
                                              class="h-12 object-contain"></a>
                    </div>

                </div>

                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    App Gestión
                </h2>

                <p class="mt-2 text-center text-sm text-gray-400">
                    Acceso para personal administrativo
                </p>

                <form wire:submit.prevent="authenticate">
                    <div class="space-y-4">
                        {{ $this->form }}
                    </div>
                    <div class="tl-s2 p-2 mb-2 mt-2 flex flex-wrap justify-center gap-1">

                        <button type="button" onclick="quickLogin('comunidad')"
                                class="btn_rrhh inline-flex items-center gap-2  opacity-50 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-amber-400/30 backdrop-blur-sm"
                                title="Admin" style="">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                 style="">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span style="">COMUNIDAD</span>
                        </button>

                        <button type="button" onclick="quickLogin('empleado')"
                                class="btn_laboral inline-flex items-center gap-2  opacity-50 px-4 py-2 bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-blue-400/30 backdrop-blur-sm"
                                title="Empleado">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                 style="">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span style="">EMPLEADO</span>
                        </button>

                        <button type="button" onclick="quickLogin('propietario')"
                                class="btn_fiscal inline-flex items-center  opacity-50 gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-purple-400/30 backdrop-blur-sm"
                                title="Fiscal" style="">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"
                                 style="">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                            </svg>
                            <span style="">PROPIETARIO</span>
                        </button>


                        <button type="button" onclick="quickLogin('admin')"
                                class="btn_admin inline-flex items-center gap-2  opacity-50 px-4 py-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-red-400/30 backdrop-blur-sm"
                                title="Admin" style="">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            <span style="">ADMIN</span></button>
                    </div>

                    <x-filament::button
                        type="submit"
                        color="red"
                        class="fi-color bg-red-500 fi-color-red fi-btn fi-size-md  tl-s2 fi-color fi-color-red fi-btn2 fi-size-md  w-full  dark:fi-text-color-0 dark:hover:fi-text-color-0 fi-btn fi-size-md  w-full bg-red-500 hover:bg-red-600">
                        Acceder al Panel
                    </x-filament::button>
                </form>
                <div class="flex items-center gap-2">
                    <div class="fi-auth-theme-switcher-wrapper"
                         style="    background: transparent !important;
    display: block;
    border: none !important;
    position: fixed;
    position: absolute;
    top: 0;
    right: 0;
                                 background: transparent !important;
                             border: none !important; ">
                        <div x-data="{ theme: null }" x-init="
        $watch('theme', () =&gt; {
            $dispatch('theme-changed', theme)
        })

        theme = localStorage.getItem('theme') || 'light'    " class="fi-theme-switcher">
                            <button aria-label="Enable light theme" type="button"
                                    x-on:click="(theme = 'light') &amp;&amp; close()" x-tooltip="{
        content: 'Enable light theme',
        theme: $store.theme,
    }" x-bind:class="{ 'fi-active': theme === 'light' }" class="fi-theme-switcher-btn">
                                <svg class="fi-icon fi-size-md" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                                     data-slot="icon">
                                    <path
                                        d="M10 2a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 2ZM10 15a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0v-1.5A.75.75 0 0 1 10 15ZM10 7a3 3 0 1 0 0 6 3 3 0 0 0 0-6ZM15.657 5.404a.75.75 0 1 0-1.06-1.06l-1.061 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM6.464 14.596a.75.75 0 1 0-1.06-1.06l-1.06 1.06a.75.75 0 0 0 1.06 1.06l1.06-1.06ZM18 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 18 10ZM5 10a.75.75 0 0 1-.75.75h-1.5a.75.75 0 0 1 0-1.5h1.5A.75.75 0 0 1 5 10ZM14.596 15.657a.75.75 0 0 0 1.06-1.06l-1.06-1.061a.75.75 0 1 0-1.06 1.06l1.06 1.06ZM5.404 6.464a.75.75 0 0 0 1.06-1.06l-1.06-1.06a.75.75 0 1 0-1.061 1.06l1.06 1.06Z"></path>
                                </svg>
                            </button>

                            <button aria-label="Enable dark theme" type="button"
                                    x-on:click="(theme = 'dark') &amp;&amp; close()" x-tooltip="{
        content: 'Enable dark theme',
        theme: $store.theme,
    }" x-bind:class="{ 'fi-active': theme === 'dark' }" class="fi-theme-switcher-btn fi-active">
                                <svg class="fi-icon fi-size-md" xmlns="http://www.w3.org/2000/svg"
                                     viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                                     data-slot="icon">
                                    <path fill-rule="evenodd"
                                          d="M7.455 2.004a.75.75 0 0 1 .26.77 7 7 0 0 0 9.958 7.967.75.75 0 0 1 1.067.853A8.5 8.5 0 1 1 6.647 1.921a.75.75 0 0 1 .808.083Z"
                                          clip-rule="evenodd"></path>
                                </svg>
                            </button>
                            <button
                                type="button"
                                onclick="clearCache()"
                                class=" p-2 rounded-lg hover:bg-white/20 text-white transition-colors"
                                title="Limpiar cache"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Clear Cache Button -->

                </div>


            </div>
        </div>
    </div>
    <style>.btn_rrhh {
            background-color: oklch(0.67 0.17 53.12 / 0.82) !important;
            opacity: .6;
            padding: 3px;
            display: inline;
            border-radius: 4px;
            margin-top: 2px !important;
            padding-right: 6px !important;
            padding-left: 6px !important;
        }

        .btn_rrhh > svg {
            color: oklch(0.98 0 0) !important;
            opacity: 1;
            width: 18px !important;
            height: 18px !important;
            margin: auto;
        }

        .btn_rrhh > span {
            color: oklch(1 0 0) !important;
            opacity: 1;
            font-size: 10px !important;
        }

        .btn_fiscal {
            background-color: oklch(0.57 0.28 302.63) !important;
            opacity: .6;
            padding: 0 !important;
            display: inline;
            border-radius: 4px;
            margin-top: 2px !important;
            padding-right: 6px !important;
            padding-left: 6px !important;
        }

        .btn_fiscal:hover, .btn_laboral:hover, .btn_rrhh:hover, .btn_admin:hover, .btn_soporte:hover {
            opacity: 1;
            font-size: 12px;
            padding: 3px;
            display: inline;
            border-radius: 4px;
            margin-top: 2px !important;
        }

        .btn_fiscal > svg {
            color: oklch(0.98 0 0) !important;
            opacity: 1;
            width: 18px !important;
            height: 18px !important;
            margin: auto;
        }

        .btn_fiscal > span {
            color: oklch(1 0 0) !important;
            opacity: 1;
            font-size: 10px !important;
        }

        .btn_admin {
            background-color: oklch(0.58 0.24 26.28 / 0.99) !important;
            opacity: .6;
            padding: 3px;
            display: inline;
            border-radius: 4px;
            margin-top: 2px !important;
            padding-right: 6px !important;
            padding-left: 6px !important;
        }

        .btn_admin > svg {
            color: oklch(0.98 0 0) !important;
            opacity: 1;
            margin: auto;
            width: 18px !important;
            height: 18px !important;
        }

        .btn_admin > span {
            color: oklch(1 0 0) !important;
            opacity: 1;
            font-size: 10px !important;
        }


        .btn_soporte {
            background-color: #2d950b !important;
            opacity: .6;
            padding: 0;
            display: inline;
            border-radius: 4px;
            margin-top: 2px !important;
        }

        .btn_soporte > svg {
            color: oklch(0.98 0 0) !important;
            opacity: 1;
            margin: auto;
            width: 18px !important;
            height: 18px !important;
        }

        .btn_soporte > span {
            color: oklch(1 0 0) !important;
            opacity: 1;
            padding-right: 2px;
            padding-left: 2px;
            font-size: 10px !important;
        }


        .btn_laboral {
            background-color: oklch(0.55 0.25 262.87 / 0.58) !important;
            opacity: .6;
            padding: 3px;
            display: inline;
            border-radius: 4px;
            margin-top: 2px !important;
            padding-right: 2px !important;
            padding-left: 2px !important;

        }

        .btn_laboral > svg {
            color: oklch(0.98 0 0) !important;
            opacity: 1;
            margin: auto;
            width: 18px !important;
            height: 18px !important;
        }

        .btn_laboral > span {
            color: oklch(1 0 0) !important;
            opacity: 1;
            font-size: 10px !important;
        }

        label.fi-fo-field-label {
            color: white !important;
        }

        span.fi-fo-field-label-content {
            line-height: var(--tw-leading, var(--text-sm--line-height));
            --tw-font-weight: var(--font-weight-medium);
            font-weight: var(--font-weight-medium);
            color: white !important;
        }

        p.fi-fo-field-wrp-error-message {
            font-size: var(--text-sm);
            line-height: var(--tw-leading, var(--text-sm--line-height));
            color: oklch(0.73 0.12 19.44);
        }
    </style>
    <!-- Formulario de login -->
    <script>
        (function cleanupAppLoginServiceWorker() {
            if (!('serviceWorker' in navigator)) {
                return;
            }

            const reloadKey = 'app-login-sw-cleaned';

            window.addEventListener('load', function () {
                navigator.serviceWorker.getRegistrations()
                    .then(function (registrations) {
                        const targets = registrations.filter(function (registration) {
                            return registration.active?.scriptURL?.includes('/sw-app.js')
                                || registration.waiting?.scriptURL?.includes('/sw-app.js')
                                || registration.installing?.scriptURL?.includes('/sw-app.js');
                        });

                        if (!targets.length) {
                            sessionStorage.removeItem(reloadKey);

                            return;
                        }

                        return Promise.all(targets.map(function (registration) {
                            return registration.unregister();
                        })).then(function () {
                            return caches.keys().then(function (cacheNames) {
                                return Promise.all(
                                    cacheNames
                                        .filter(function (cacheName) {
                                            return cacheName.startsWith('taxilanz-app-');
                                        })
                                        .map(function (cacheName) {
                                            return caches.delete(cacheName);
                                        })
                                );
                            });
                        }).then(function () {
                            if (sessionStorage.getItem(reloadKey) === '1') {
                                sessionStorage.removeItem(reloadKey);

                                return;
                            }

                            sessionStorage.setItem(reloadKey, '1');
                            window.location.reload();
                        });
                    })
                    .catch(function () {
                    });
            });
        })();

        function quickLogin(role) {
            const users = {
                soporte: {email: 'soporte@taxilanz.com', password: 'password', label: 'Soporte'},
                superadmin: {email: 'admin@admin.com', password: 'password', label: 'Super Admin'},
                admin: {email: 'admin@comunigest.test', password: 'password', label: 'Admin'},
                empleado: {email: 'empleado@comunigest.test', password: 'password', label: 'Empleado'},
                propietario: {email: 'propietario@comunigest.test', password: 'password', label: 'Propietario'},
                comunidad: {email: 'comunidad@comunigest.test', password: 'password', label: 'Comunidad'},
            };
            const defaultPasswords = {
                soporte: 'password',
                superadmin: 'password',
                admin: 'password',
                empleado: 'password',
                propietario: 'password',
                comunidad: 'password',
            };

            const defaultEmails = {
               soporte: 'soporte@taxilanz.com',
                superadmin: 'admin@admin.com',
                admin: 'admin@comunigest.test',
                empleado: 'empleado@comunigest.test',
                propietario: 'propietario@comunigest.test',
                comunidad: 'comunidad@comunigest.test',
            };
            const user = users[role] || users.default;

            const email = defaultEmails[role] || '';
            const password = defaultPasswords[role] || '';

            const form = document.querySelector('form');
            if (!form) {
                console.error('App login form not found');
                return;
            }

            const emailInput = document.querySelector('input[id="form.email"]')
            const passwordInput = document.querySelector('input[id="form.password"]')
            const rememberInput = form.querySelector('input[name="remember"], input[name="Remember"]');

            if (!emailInput || !passwordInput) {
                console.error('No se encontró el formulario de login (email/password).');
                return;
            }

            emailInput.value = email;
            passwordInput.value = password;

            emailInput.dispatchEvent(new Event('input', {bubbles: true}));
            passwordInput.dispatchEvent(new Event('input', {bubbles: true}));

            if (rememberInput) {
                rememberInput.checked = true;
                rememberInput.dispatchEvent(new Event('change', {bubbles: true}));
            }

            form.dispatchEvent(new Event('submit', {cancelable: true, bubbles: true}));


        }

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
    function quickLogin(?string $user = null): void
    {
        $label = 'Portal';
        $credentials = match ($user) {
            'propietario' => [
                'email' => 'propietario@comunigest.test',
                'password' => 'password',
            ],
            'comunidad' => [
                'email' => 'comunidad@comunigest.test',
                'password' => 'password',
            ],
            'empleado' => [
                'email' => 'empleado@comunigest.test',
                'password' => 'password',
            ],
            'admin' => [
                'email' => 'admin@comunigest.test',
                'password' => 'password',
            ],
            'superadmin' => [
                'email' => 'admin@admin.com',
                'password' => 'password',
            ],
            default => [
                'email' => 'admin@admin.com',
                'password' => 'password',
            ],
        };

        $label = match ($user) {
            'jma' => 'JMA',
            'pms' => 'PMS',
            'emp' => 'EMP',
            default => 'Portal',
        };

        $this->form->fill($credentials);
        $this->dispatch('portal-action-hint', message: "Credenciales {$label} cargadas", tone: 'success', duration: 2200);
    }
    function clearCache() {
        $csrfToken = $_POST['csrf_token'] ?? '';
        $url = '/app/clear-cache-login';

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
</div>
