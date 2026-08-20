<x-filament-panels::page>
    @php
        $devicesCollection = collect($devices ?? []);
        $positionsCollection = collect($positions ?? []);
        $latestPositionAt = $devicesCollection
            ->map(fn (array $device): ?\Carbon\CarbonInterface => \App\Support\TrackingConnectivity::resolveLastCommunicationAt(null, $device))
            ->filter()
            ->sortDesc()
            ->first();
        $onlineDevicesCount = $devicesCollection->where('status', 'online')->count();
        $offlineDevicesCount = $devicesCollection->count() - $onlineDevicesCount;
        $devicesWithPositionCount = $devicesCollection
            ->filter(fn (array $device): bool => $positionsCollection->firstWhere('deviceId', (int) ($device['id'] ?? 0)) !== null)
            ->count();
        $traccarMapBaseUrl = preg_replace('#/api/?$#', '', rtrim((string) (config('traccar.url') ?: config('traccar.base_url') ?: ''), '/'));
        $trackingDevices = $devicesCollection
            ->map(function (array $device) use ($positionsCollection, $traccarMapBaseUrl): array {
                $position = $positionsCollection->firstWhere('deviceId', (int) ($device['id'] ?? 0));
                $latitude = $position['latitude'] ?? null;
                $longitude = $position['longitude'] ?? null;
                $lastUpdate = $device['lastUpdate'] ?? ($position['fixTime'] ?? $position['deviceTime'] ?? null);

                return [
                    'id' => (int) ($device['id'] ?? 0),
                    'name' => (string) ($device['name'] ?? ('Taxi #'.($device['id'] ?? '?'))),
                    'uniqueId' => (string) ($device['uniqueId'] ?? ''),
                    'taxistaUserId' => (int) ($device['taxistaUserId'] ?? 0),
                    'status' => (string) ($device['status'] ?? 'offline'),
                    'latitude' => is_numeric($latitude) ? (float) $latitude : null,
                    'longitude' => is_numeric($longitude) ? (float) $longitude : null,
                    'speed' => is_numeric($position['speed'] ?? null) ? round((float) $position['speed']) : 0,
                    'lastUpdateIso' => filled($lastUpdate) ? \Carbon\Carbon::parse($lastUpdate)->toIso8601String() : null,
                    'lastUpdateLabel' => filled($lastUpdate) ? \Carbon\Carbon::parse($lastUpdate)->diffForHumans() : 'Sin datos',
                    'address' => (string) ($position['address'] ?? ''),
                    'course' => is_numeric($position['course'] ?? null) ? (int) round((float) $position['course']) : null,
                    'traccarUrl' => (filled($traccarMapBaseUrl) && is_numeric($device['id'] ?? null))
                        ? rtrim((string) $traccarMapBaseUrl, '/').'/#map/device/'.(int) $device['id']
                        : null,
                    'replayUrl' => filled($device['uniqueId'] ?? null)
                        ? route('tracking.replay', [
                            'uniqueId' => $device['uniqueId'],
                            'from' => now()->subHours(max(1, (int) config('traccar.sync.default_window_hours', 24)))->toIso8601String(),
                            'to' => now()->toIso8601String(),
                            'embed' => true,
                        ])
                        : null,
                ];
            })
            ->values()
            ->all();
    @endphp

    <div
        x-data="appLocationsPage(@js($trackingDevices))"
        x-init="init()"
        class="space-y-5"
    >
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Conectados</p>
                <div class="mt-3 flex items-end gap-3">
                    <span class="text-4xl font-semibold text-emerald-600">{{ $onlineDevicesCount }}</span>
                    <span class="pb-1 text-sm text-gray-500">de {{ count($trackingDevices) }}</span>
                </div>
                <p class="mt-3 text-sm text-gray-500">Taxis con comunicación reciente</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Desconectados</p>
                <div class="mt-3 flex items-end gap-3">
                    <span class="text-4xl font-semibold text-rose-600">{{ $offlineDevicesCount }}</span>
                    <span class="pb-1 text-sm text-gray-500">sin ping reciente</span>
                </div>
                <p class="mt-3 text-sm text-gray-500">Control visual inmediato</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Con posición</p>
                <div class="mt-3 flex items-end gap-3">
                    <span class="text-4xl font-semibold text-sky-600">{{ $devicesWithPositionCount }}</span>
                    <span class="pb-1 text-sm text-gray-500">marcadores visibles</span>
                </div>
                <p class="mt-3 text-sm text-gray-500">Datos listos para quickview</p>
            </div>

            <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Última comunicación</p>
                <div class="mt-3 text-2xl font-semibold text-gray-900">
                    {{ $latestPositionAt ? $latestPositionAt->format('d/m H:i') : 'Sin datos' }}
                </div>
                <p class="mt-3 text-sm text-gray-500">Snapshot más reciente recibido</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
            <div class="xl:col-span-3">
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4">
                        <div class="flex items-center gap-2">
                            <x-filament::icon
                                icon="heroicon-o-map"
                                class="h-5 w-5 text-gray-400"
                            />
                            <div>
                                <p class="text-xl font-semibold text-gray-950">Mapa de taxis</p>
                                <p class="text-sm text-gray-500">Quickview de estado y ubicación sobre Lanzarote</p>
                            </div>
                        </div>

                        <x-filament::button
                            wire:click="refreshMap"
                            icon="heroicon-m-arrow-path"
                            color="gray"
                            size="sm"
                        >
                            Actualizar
                        </x-filament::button>
                    </div>

                    <div class="p-4">
                        <div id="appLocationsMap" class="h-[66vh] min-h-[520px] w-full rounded-xl border border-gray-200 bg-gray-100"></div>
                    </div>
                </div>
            </div>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-lg font-semibold text-gray-950">Seguimiento del taxi</p>
                        <p class="text-sm text-gray-500">Filtro, foco de mapa y acciones rápidas</p>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
                        @click="collapsed = ! collapsed"
                    >
                        <x-filament::icon
                            x-show="! collapsed"
                            icon="heroicon-m-chevron-up"
                            class="h-5 w-5"
                        />
                        <x-filament::icon
                            x-show="collapsed"
                            icon="heroicon-m-chevron-down"
                            class="h-5 w-5"
                        />
                    </button>
                </div>

                <div x-show="! collapsed" x-cloak class="mt-4 space-y-4">
                    <div class="flex flex-wrap gap-2 rounded-xl bg-gray-100 p-1">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-semibold transition"
                            :class="filter === 'all' ? 'bg-white text-primary-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            @click="filter = 'all'"
                        >
                            Todos <span class="ml-1 text-xs text-gray-400" x-text="devices.length"></span>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-semibold transition"
                            :class="filter === 'online' ? 'bg-white text-emerald-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            @click="filter = 'online'"
                        >
                            Online <span class="ml-1 text-xs text-gray-400" x-text="onlineCount"></span>
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm font-semibold transition"
                            :class="filter === 'offline' ? 'bg-white text-rose-600 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                            @click="filter = 'offline'"
                        >
                            Offline <span class="ml-1 text-xs text-gray-400" x-text="offlineCount"></span>
                        </button>
                    </div>

                    <label class="block">
                        <span class="sr-only">Buscar taxi</span>
                        <input
                            type="search"
                            x-model.debounce.200ms="search"
                            placeholder="Buscar taxi o UUID"
                            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 shadow-sm outline-none transition placeholder:text-gray-400 focus:border-primary-500 focus:ring-2 focus:ring-primary-500/15"
                        >
                    </label>

                    <div class="max-h-[28rem] space-y-2 overflow-y-auto pr-1">
                        <template x-for="device in filteredDevices" :key="device.id">
                            <div
                                class="rounded-xl border px-4 py-3 text-left transition"
                                :class="selectedDeviceId === device.id ? 'border-primary-300 bg-primary-50 shadow-sm' : 'border-gray-200 hover:border-gray-300 hover:bg-gray-50'"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <button
                                        type="button"
                                        class="min-w-0 flex-1 text-left"
                                        @click="selectDevice(device.id)"
                                    >
                                        <p class="truncate text-sm font-semibold text-gray-900" x-text="device.name"></p>
                                        <p class="mt-1 truncate font-mono text-xs text-gray-500" x-text="device.uniqueId || 'Sin UUID'"></p>
                                    </button>

                                    <div class="flex items-center gap-2">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="device.status === 'online' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                            x-text="device.status === 'online' ? 'Online' : 'Offline'"
                                        ></span>

                                        <button
                                            type="button"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-gray-200 bg-white text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
                                            @click.stop="openDeviceInfo(device.id)"
                                        >
                                            <x-filament::icon icon="heroicon-m-information-circle" class="h-5 w-5" />
                                        </button>
                                    </div>
                                </div>

                                <div class="mt-3 flex items-center justify-between text-xs text-gray-500">
                                    <span x-text="device.lastUpdateLabel"></span>
                                    <span x-text="device.address || 'Sin dirección'"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <template x-teleport="body">
            <div
                x-show="infoOpen && selectedDevice"
                x-cloak
                class="fixed inset-0 z-[1000] flex justify-end bg-gray-950/25 backdrop-blur-[1px]"
            >
                <div class="absolute inset-0" @click="infoOpen = false"></div>

                <aside class="relative z-10 h-full w-full max-w-xl overflow-y-auto bg-white shadow-2xl ring-1 ring-gray-950/10">
                    <div class="sticky top-0 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Taxi seleccionado</p>
                            <h3 class="mt-2 text-2xl font-semibold text-gray-950" x-text="selectedDevice?.name"></h3>
                            <p class="mt-2 font-mono text-sm text-gray-500" x-text="selectedDevice?.uniqueId || 'Sin UUID'"></p>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
                            @click="infoOpen = false"
                        >
                            <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="space-y-5 px-6 py-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center rounded-full px-3 py-1.5 text-sm font-semibold"
                                :class="selectedDevice?.status === 'online' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                x-text="selectedDevice?.status === 'online' ? 'Online' : 'Offline'"
                            ></span>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700">
                                <span x-text="selectedDevice?.speed ?? 0"></span> km/h
                            </span>
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-sm font-semibold text-gray-700" x-text="selectedDevice?.lastUpdateLabel"></span>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Estado</p>
                                <p class="mt-2 text-lg font-semibold text-gray-900" x-text="selectedDevice?.status === 'online' ? 'Conectado' : 'Sin ping'"></p>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Última comunicación</p>
                                <p class="mt-2 text-lg font-semibold text-gray-900" x-text="selectedDevice?.lastUpdateLabel"></p>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Velocidad</p>
                                <p class="mt-2 text-lg font-semibold text-gray-900"><span x-text="selectedDevice?.speed ?? 0"></span> km/h</p>
                            </div>
                            <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Curso</p>
                                <p class="mt-2 text-lg font-semibold text-gray-900"><span x-text="selectedDevice?.course ?? '—'"></span></p>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-gray-200 bg-white p-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-gray-400">Dirección</p>
                            <p class="mt-2 text-sm leading-6 text-gray-700" x-text="selectedDevice?.address || 'Sin dirección resuelta'"></p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" class="rounded-xl border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50" @click="focusSelectedDevice(); infoOpen = false;">
                                Ver en mapa
                            </button>
                            <a
                                class="rounded-xl border border-gray-200 px-4 py-3 text-center text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                :href="selectedDevice?.traccarUrl || '#'"
                                target="_blank"
                                rel="noreferrer"
                            >
                                Traccar
                            </a>
                            <a
                                href="#"
                                class="rounded-xl border border-gray-200 px-4 py-3 text-center text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                @click.prevent="openReplay()"
                            >
                                Replay
                            </a>
                            <a
                                class="rounded-xl border border-gray-200 px-4 py-3 text-center text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                :href="googleMapsUrl(selectedDevice)"
                                target="_blank"
                                rel="noreferrer"
                            >
                                Google Maps
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </template>

        <template x-teleport="body">
            <div
                x-show="replayOpen && selectedDevice"
                x-cloak
                class="fixed inset-0 z-[1000] flex justify-end bg-gray-950/30 backdrop-blur-[1px]"
            >
                <div class="absolute inset-0" @click="closeReplay()"></div>

                <aside class="relative z-10 h-full w-full max-w-[min(1200px,100vw)] overflow-hidden bg-white shadow-2xl ring-1 ring-gray-950/10">
                    <div class="sticky top-0 flex items-start justify-between gap-4 border-b border-gray-200 bg-white px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-400">Replay integrado</p>
                            <h3 class="mt-2 text-2xl font-semibold text-gray-950" x-text="selectedDevice?.name"></h3>
                            <p class="mt-2 font-mono text-sm text-gray-500" x-text="selectedDevice?.uniqueId || 'Sin UUID'"></p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center rounded-xl border border-gray-200 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:border-gray-300 hover:bg-gray-50"
                                @click="closeReplay(true)"
                            >
                                Volver
                            </button>

                            <button
                                type="button"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-gray-200 text-gray-500 transition hover:border-gray-300 hover:text-gray-700"
                                @click="closeReplay()"
                            >
                                <x-filament::icon icon="heroicon-m-x-mark" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    <div class="h-[calc(100vh-89px)] bg-gray-100 p-4">
                        <iframe
                            class="h-full w-full rounded-xl border border-gray-200 bg-white shadow-sm"
                            :src="selectedDevice?.replayUrl || 'about:blank'"
                            loading="lazy"
                            title="Replay del taxi"
                        ></iframe>
                    </div>
                </aside>
            </div>
        </template>
    </div>

    @pushOnce('styles')
        <link
            rel="stylesheet"
            href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
            integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
            crossorigin=""
        />
    @endPushOnce

    @pushOnce('scripts')
        <script
            src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
            integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
            crossorigin=""
        ></script>
        <script>
            function appLocationsPage(initialDevices) {
                return {
                    collapsed: false,
                    infoOpen: false,
                    replayOpen: false,
                    devices: initialDevices,
                    filter: 'all',
                    search: '',
                    selectedDeviceId: initialDevices.find((device) => device.status === 'online')?.id ?? initialDevices[0]?.id ?? null,
                    map: null,
                    markers: {},

                    get selectedDevice() {
                        return this.devices.find((device) => device.id === this.selectedDeviceId) ?? null;
                    },

                    get onlineCount() {
                        return this.devices.filter((device) => device.status === 'online').length;
                    },

                    get offlineCount() {
                        return this.devices.filter((device) => device.status !== 'online').length;
                    },

                    get filteredDevices() {
                        return this.devices.filter((device) => {
                            const statusMatches = this.filter === 'all' || device.status === this.filter;
                            const searchNeedle = this.search.trim().toLowerCase();
                            const searchMatches = searchNeedle === ''
                                || device.name.toLowerCase().includes(searchNeedle)
                                || (device.uniqueId || '').toLowerCase().includes(searchNeedle);

                            return statusMatches && searchMatches;
                        });
                    },

                    init() {
                        this.$nextTick(() => {
                            this.initMap();
                            this.renderMarkers();
                            this.focusSelectedDevice(false);
                            this.registerRealtimeListeners();
                            window.setTimeout(() => {
                                this.map?.invalidateSize();
                            }, 120);
                        });
                    },

                    registerRealtimeListeners() {
                        window.addEventListener('taxista-presence-updated', (event) => {
                            this.handleTaxistaPresenceUpdated(event.detail ?? {});
                        });

                        window.addEventListener('taxista-location-updated', (event) => {
                            this.handleTaxistaLocationUpdated(event.detail ?? {});
                        });
                    },

                    initMap() {
                        const mapElement = document.getElementById('appLocationsMap');

                        if (!mapElement) {
                            return;
                        }

                        if (window.appLocationsMapInstance) {
                            window.appLocationsMapInstance.remove();
                            window.appLocationsMapInstance = null;
                        }

                        if (mapElement._leaflet_id) {
                            mapElement._leaflet_id = null;
                            mapElement.innerHTML = '';
                        }

                        this.map = L.map('appLocationsMap', {
                            zoomControl: true,
                            scrollWheelZoom: true,
                        }).setView([28.963, -13.555], 10);

                        window.appLocationsMapInstance = this.map;

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                            maxZoom: 19,
                        }).addTo(this.map);

                        window.addEventListener('resize', () => {
                            this.map?.invalidateSize();
                        });
                    },

                    renderMarkers() {
                        this.devices.forEach((device) => {
                            this.syncDeviceMarker(device);
                        });

                        const bounds = Object.values(this.markers).map((marker) => marker.getLatLng());
                        if (bounds.length > 0) {
                            this.map.fitBounds(bounds, { padding: [40, 40] });
                        }
                    },

                    syncDeviceMarker(device) {
                        if (typeof device.latitude !== 'number' || typeof device.longitude !== 'number') {
                            return;
                        }

                        let marker = this.markers[device.id];

                        if (!marker) {
                            marker = L.circleMarker([device.latitude, device.longitude], {
                                radius: 7,
                                weight: 2,
                                fillOpacity: 0.85,
                            }).addTo(this.map);

                            marker.bindTooltip(device.name, { direction: 'top' });
                            marker.on('click', () => this.selectDevice(device.id));

                            this.markers[device.id] = marker;
                        }

                        marker.setLatLng([device.latitude, device.longitude]);
                        marker.setStyle({
                            radius: device.status === 'online' ? 9 : 7,
                            color: device.status === 'online' ? '#059669' : '#dc2626',
                            fillColor: device.status === 'online' ? '#10b981' : '#f87171',
                        });
                    },

                    handleTaxistaPresenceUpdated(payload) {
                        const taxistaUserId = Number(payload?.taxistaUserId ?? 0);
                        const isOnline = Boolean(payload?.isOnline ?? false);
                        const updatedAtIso = payload?.updatedAtIso ?? new Date().toISOString();

                        if (!taxistaUserId) {
                            return;
                        }

                        this.devices = this.devices.map((device) => {
                            if (Number(device.taxistaUserId ?? 0) !== taxistaUserId) {
                                return device;
                            }

                            const updatedDevice = {
                                ...device,
                                status: isOnline ? 'online' : device.status,
                                lastUpdateIso: updatedAtIso,
                                lastUpdateLabel: 'justo ahora',
                            };

                            this.syncDeviceMarker(updatedDevice);

                            return updatedDevice;
                        });
                    },

                    handleTaxistaLocationUpdated(payload) {
                        const taxistaUserId = Number(payload?.taxistaUserId ?? 0);
                        const lat = Number(payload?.lat);
                        const lng = Number(payload?.lng);
                        const updatedAtIso = payload?.updatedAtIso ?? new Date().toISOString();

                        if (!taxistaUserId || Number.isNaN(lat) || Number.isNaN(lng)) {
                            return;
                        }

                        this.devices = this.devices.map((device) => {
                            if (Number(device.taxistaUserId ?? 0) !== taxistaUserId) {
                                return device;
                            }

                            const updatedDevice = {
                                ...device,
                                status: 'online',
                                latitude: lat,
                                longitude: lng,
                                lastUpdateIso: updatedAtIso,
                                lastUpdateLabel: 'justo ahora',
                            };

                            this.syncDeviceMarker(updatedDevice);

                            return updatedDevice;
                        });

                        if (this.selectedDevice && Number(this.selectedDevice.taxistaUserId ?? 0) === taxistaUserId) {
                            this.focusSelectedDevice(false);
                        }
                    },

                    selectDevice(deviceId) {
                        this.selectedDeviceId = deviceId;
                        this.focusSelectedDevice();
                        this.infoOpen = true;
                        this.replayOpen = false;
                    },

                    openDeviceInfo(deviceId) {
                        this.selectedDeviceId = deviceId;
                        this.infoOpen = true;
                        this.replayOpen = false;
                    },

                    openReplay(deviceId = null) {
                        if (deviceId !== null) {
                            this.selectedDeviceId = deviceId;
                        }

                        if (!this.selectedDevice?.replayUrl) {
                            return;
                        }

                        this.infoOpen = false;
                        this.replayOpen = true;
                    },

                    closeReplay(returnToInfo = false) {
                        this.replayOpen = false;

                        if (returnToInfo && this.selectedDevice) {
                            this.infoOpen = true;
                        }
                    },

                    focusSelectedDevice(animate = true) {
                        const device = this.selectedDevice;
                        const marker = device ? this.markers[device.id] : null;

                        if (!device || !marker) {
                            return;
                        }

                        this.map.setView(marker.getLatLng(), Math.max(this.map.getZoom(), 14), {
                            animate,
                            duration: animate ? 0.6 : 0,
                        });

                        marker.openTooltip();
                    },

                    googleMapsUrl(device) {
                        if (!device || typeof device.latitude !== 'number' || typeof device.longitude !== 'number') {
                            return '#';
                        }

                        return `https://www.google.com/maps?q=${device.latitude},${device.longitude}`;
                    },
                };
            }
        </script>
    @endPushOnce
</x-filament-panels::page>
