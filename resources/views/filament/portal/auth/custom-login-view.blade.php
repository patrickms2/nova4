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
                 class="relative z-10 w-full max-w-sm rounded-3xl border border-white/10 dark:bg-black/20 text-white p-6 shadow-2xl shadow-black/30 backdrop-blur-xl sm:p-8">
                    <div class="mb-4">

                <div class="flex justify-between items-center mb-6">
                    <div class="flex items-center gap-2">
                        <a href="/login"><img src="{{ asset('img/logo.png') }}" alt="Taxilanz"
                                              class="h-12 object-contain"></a>
                    </div>

                </div>

                <h2 class="mt-6 text-center text-3xl font-extrabold text-white">
                    Portal Taxistas
                </h2>

                <p class="mt-2 text-center text-sm text-gray-400">
                    Accede con tu NIF o Email
                </p>

<form wire:submit.prevent="authenticate" class="portal-login-form">
                    <div class="space-y-4">
                        {{ $this->form }}
                    </div>

                    <div class="tl-s2 p-2 mb-4 mt-4 flex flex-wrap justify-center gap-2">
                        <button
                            type="button"
                            onclick="quickLogin('emp')"
                            class="tl-s2 inline-flex items-center  opacity-90 gap-2 px-4 py-2 bg-gradient-to-r from-purple-500 to-purple-600 hover:from-purple-600 hover:to-purple-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-purple-400/30 backdrop-blur-sm"
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
                            onclick="quickLogin('jma')"
                            class="tl-s2 inline-flex items-center gap-2  opacity-90 px-4 py-2 bg-gradient-to-r from-amber-500 to-amber-600 hover:from-amber-600 hover:to-amber-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-amber-400/30 backdrop-blur-sm"
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
                            onclick="quickLogin('pms')"
                            class="tl-s2 inline-flex items-center gap-2 px-4 py-2 opacity-90 bg-gradient-to-r from-teal-500 to-teal-600 hover:from-teal-600 hover:to-teal-700 text-white text-xs font-bold rounded-lg shadow-xl transition-all duration-300 transform hover:scale-105 border border-teal-400/30 backdrop-blur-sm"
                            title="PMS"
                        >
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            PMS
                        </button>
                    </div>

                    <x-filament::button
                        type="submit"
                        color="red"
                        class="tl-s2 fi-color fi-color-red fi-btn2 fi-size-md  w-full  dark:fi-text-color-0 dark:hover:fi-text-color-0 fi-btn fi-size-md  w-full bg-red-600/20 hover:bg-red-600">
                        Acceder al Portal
                    </x-filament::button>
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

                </form>


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

        function quickLogin(userType) {
            const defaultPasswords = {
                emp: 'password',
                jma: 'password',
                pms: 'password',
            };

            const defaultEmails = {
                emp: 'empleado1@taxilanz.com',
                jma: '38504658J',
                pms: '45532522C',
            };

            const email = defaultEmails[userType] || '';
            const password = defaultPasswords[userType] || '';

            const form = document.querySelector('form.portal-login-form');
            if (!form) {
                console.error('Portal login form not found');
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

            emailInput.dispatchEvent(new Event('input', { bubbles: true }));
            passwordInput.dispatchEvent(new Event('input', { bubbles: true }));

            if (rememberInput) {
                rememberInput.checked = true;
                rememberInput.dispatchEvent(new Event('change', { bubbles: true }));
            }

            form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));

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
            const savedTheme = 'dark';//localStorage.getItem('theme') || 'dark';
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
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const savedTheme = '<?php echo $_COOKIE['theme'] ?? 'dark'; ?>';
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
</div>
</div>
