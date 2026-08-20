<x-filament::page>
    <div class="space-y-6">
        <div
            x-data="taxistaConnectionBadge({
                taxistaId: @js($this->taxistaId),
                endpoint: @js(route('api.taxistas.connection-status')),
            })"
            x-init="init()"
            class="bg-white dark:bg-gray-800 rounded-lg shadow p-4"
        >
            <div class="flex flex-wrap items-center gap-3">
                <span class="text-sm font-semibold">Estado taxista</span>

                <span
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                    :class="connected ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300'"
                >
                    <span class="h-2 w-2 rounded-full" :class="connected ? 'bg-green-500' : 'bg-red-500'"></span>
                    <span x-text="connected ? 'ON' : 'OFF'"></span>
                </span>

                <span
                    class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-medium"
                    :class="wsConnected ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200'"
                >
                    <span class="h-2 w-2 rounded-full" :class="wsConnected ? 'bg-blue-500' : 'bg-gray-400'"></span>
                    <span x-text="wsConnected ? 'Tiempo real conectado' : 'Tiempo real reconectando'"></span>
                </span>
            </div>
        </div>

        <!-- Bienvenida -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white">
            <h1 class="text-2xl font-bold mb-2">
                ¡Bienvenido de nuevo, {{ auth()->user()->first_name }}!
            </h1>
            <p class="text-blue-100">
                Aquí tienes un resumen de tu actividad reciente y próximas tareas.
            </p>
        </div>

        <!-- Estadísticas rápidas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Próximas Citas</p>
                        <p class="text-xl font-semibold">{{ \App\Models\Taxi\Cita::where('usuario_id', auth()->id())->where('appointment_date', '>=', now())->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900 rounded-lg">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-300" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Documentos</p>
                        <p class="text-xl font-semibold">{{ \App\Models\Taxi\Documento::where('usuario_id', auth()->id())->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-300" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tickets Activos</p>
                        <p class="text-xl font-semibold">{{ \App\Models\Taxi\Ticket::where('usuario_id', auth()->id())->whereIn('status', ['abierto', 'en_progreso'])->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor"
                             viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Mis Taxis</p>
                        <p class="text-xl font-semibold">{{ \App\Models\Taxi::whereHas('client', function($q) { $q->where('client_id', auth()->id()); })->count() }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widgets -->
        <div>
        </div>
    </div>

    <script>
        function taxistaConnectionBadge({ taxistaId, endpoint }) {
            return {
                taxistaId,
                endpoint,
                connected: false,
                wsConnected: false,
                channel: null,
                refreshTimer: null,
                isInitialized: false,

                init() {
                    if (this.isInitialized || !this.taxistaId) {
                        return;
                    }

                    this.isInitialized = true;
                    this.fetchStatus();
                    this.connectRealtime();

                    this.refreshTimer = setInterval(() => this.fetchStatus(), 30000);
                    window.addEventListener('focus', () => this.fetchStatus());
                },

                async fetchStatus() {
                    try {
                        const response = await fetch(`${this.endpoint}?taxista_id=${this.taxistaId}`, {
                            headers: { Accept: 'application/json' },
                        });

                        if (!response.ok) {
                            return;
                        }

                        const data = await response.json();
                        this.connected = !!data.conectado;
                    } catch (_error) {}
                },

                connectRealtime() {
                    if (!window.Echo) {
                        return;
                    }

                    this.channel = window.Echo.channel(`taxistas.${this.taxistaId}.connection`)
                        .listen('.taxista.connection.updated', (event) => {
                            this.connected = !!event.conectado;
                        });

                    const pusher = window.Echo?.connector?.pusher;
                    if (!pusher?.connection) {
                        return;
                    }

                    this.wsConnected = pusher.connection.state === 'connected';

                    pusher.connection.bind('state_change', (states) => {
                        this.wsConnected = states.current === 'connected';
                        if (this.wsConnected) {
                            this.fetchStatus();
                        }
                    });
                },
            };
        }
    </script>
</x-filament::page>
