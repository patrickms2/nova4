<div wire:poll.10s="loadMapData">

    {{-- Header stats --}}
    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <span class="relative flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-red-500"></span>
            </span>
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                {{ count($markers) }} hoteles activos · {{ $totalSolicitudes }} solicitudes
            </span>
        </div>
        <div class="ml-auto flex items-center gap-2 text-xs text-gray-400">
            @if($loading)
                <x-filament::loading-indicator class="h-4 w-4 text-blue-500"/>
            @endif
            <span>{{ $lastUpdated }}</span>
        </div>
    </div>

    {{-- Map container --}}
    <div
        id="solicitudes-live-map"
        class="h-[500px] w-full rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10"
        wire:ignore
    ></div>

    {{-- Google Maps is loaded globally via AppPanelProvider HEAD_END renderHook --}}

    @script
    <script>
        let map = null;
        let markersOnMap = [];
        let infoWindow = null;

        function initSolicitudesMap() {
            const mapEl = document.getElementById('solicitudes-live-map');
            if (!mapEl || map) return;

            map = new google.maps.Map(mapEl, {
                center: {lat: 28.921144, lng: -13.641344},
                zoom: 12,
                mapTypeId: 'roadmap',
                styles: [
                    {featureType: 'poi', stylers: [{visibility: 'off'}]},
                    {featureType: 'transit', stylers: [{visibility: 'off'}]},
                ],
                mapTypeControl: true,
                streetViewControl: false,
                fullscreenControl: true,
            });

            infoWindow = new google.maps.InfoWindow();
            updateMarkers($wire.markers);
        }

        function getMarkerIcon(count, pendientes) {
            const size = Math.min(20 + count * 3, 50);
            let color;
            if (pendientes > 0) {
                color = '#ef4444'; // red - has pending
            } else if (count > 5) {
                color = '#f59e0b'; // amber - busy
            } else {
                color = '#22c55e'; // green - normal
            }

            return {
                path: google.maps.SymbolPath.CIRCLE,
                fillColor: color,
                fillOpacity: 0.85,
                strokeColor: '#fff',
                strokeWeight: 2,
                scale: size / 3,
            };
        }

        function buildInfoContent(marker) {
            let solicitudesHtml = '';
            if (marker.solicitudes && marker.solicitudes.length > 0) {
                solicitudesHtml = '<div style="margin-top:8px;border-top:1px solid #e5e7eb;padding-top:6px;">';
                marker.solicitudes.forEach(s => {
                    const estadoColor = s.estado === 'SOLICITADO' ? '#f59e0b' :
                        s.estado === 'TRAMITADO' ? '#22c55e' :
                            s.estado === 'CANCELADO' ? '#ef4444' : '#9ca3af';
                    solicitudesHtml += `
                        <div style="font-size:11px;margin-bottom:4px;display:flex;align-items:center;gap:6px;">
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:${estadoColor};flex-shrink:0;"></span>
                            <span>${s.fecha ? s.fecha.substring(11, 16) : ''}</span>
                            <span style="font-weight:600;">${s.nombre || ''}</span>
                            <span>Hab.${s.hab}</span>
                            <span>${s.pax}pax</span>
                            <span style="color:${estadoColor};font-weight:600;">${s.estado}</span>
                        </div>`;
                });
                solicitudesHtml += '</div>';
            }

            return `
                <div style="font-family:system-ui;min-width:260px;max-width:350px;">
                    <div style="font-size:14px;font-weight:700;margin-bottom:4px;">${marker.nombre}</div>
                    <div style="font-size:12px;color:#6b7280;margin-bottom:6px;">${marker.municipio}</div>
                    <div style="display:flex;gap:12px;font-size:12px;">
                        <span style="color:#ef4444;font-weight:600;">${marker.pendientes} pendientes</span>
                        <span style="color:#22c55e;font-weight:600;">${marker.tramitados} tramitados</span>
                        <span style="color:#6b7280;">${marker.count} total</span>
                    </div>
                    ${solicitudesHtml}
                </div>
            `;
        }

        function updateMarkers(data) {
            if (!map) return;

            // Clear existing markers
            markersOnMap.forEach(m => m.setMap(null));
            markersOnMap = [];

            const bounds = new google.maps.LatLngBounds();

            data.forEach(item => {
                const pos = {lat: item.lat, lng: item.lng};

                const marker = new google.maps.Marker({
                    position: pos,
                    map: map,
                    icon: getMarkerIcon(item.count, item.pendientes),
                    title: `${item.nombre} (${item.count})`,
                    animation: item.pendientes > 0 ? google.maps.Animation.BOUNCE : null,
                    zIndex: item.pendientes > 0 ? 1000 : item.count,
                });

                // Stop bounce after 2 seconds
                if (item.pendientes > 0) {
                    setTimeout(() => marker.setAnimation(null), 2100);
                }

                // Label
                const label = new google.maps.Marker({
                    position: pos,
                    map: map,
                    icon: {
                        path: 'M 0 0',
                        scale: 0,
                    },
                    label: {
                        text: String(item.count),
                        color: '#ffffff',
                        fontSize: '10px',
                        fontWeight: 'bold',
                    },
                    zIndex: 1001,
                });

                marker.addListener('click', () => {
                    infoWindow.setContent(buildInfoContent(item));
                    infoWindow.open(map, marker);
                });

                markersOnMap.push(marker, label);
                bounds.extend(pos);
            });

            if (data.length > 0) {
                map.fitBounds(bounds, {padding: 50});
                if (map.getZoom() > 15) map.setZoom(15);
            }
        }

        // Initialize map
        if (typeof google !== 'undefined' && google.maps) {
            initSolicitudesMap();
        } else {
            const checkGoogle = setInterval(() => {
                if (typeof google !== 'undefined' && google.maps) {
                    clearInterval(checkGoogle);
                    initSolicitudesMap();
                }
            }, 200);
        }

        // Listen for Livewire updates
        $wire.$watch('markers', (value) => {
            if (!map) {
                initSolicitudesMap();
            } else {
                updateMarkers(value);
            }
        });

        // Listen for real-time WebSocket events
        window.addEventListener('solicitud-taxi-recibida', (e) => {
            const data = e.detail;
            if (!data?.solicitud) return;

            const hotel = data.solicitud.nombreUsuario || 'Hotel';
            const tipo = data.tipo || 'nueva';
            const estado = data.solicitud.nombreEstado || '';

            // Flash notification
            const toast = document.createElement('div');
            toast.className = 'fixed top-4 right-4 z-[9999] rounded-lg bg-red-600 px-4 py-3 text-sm font-semibold text-white shadow-lg transition-all duration-500';
            toast.innerHTML = `🚕 ${tipo === 'nueva' ? 'Nueva solicitud' : tipo === 'cancelada' ? 'Cancelada' : 'Actualizada'}: <strong>${hotel}</strong> ${estado ? '— ' + estado : ''}`;
            document.body.appendChild(toast);
            setTimeout(() => {
                toast.style.opacity = '0';
            }, 3000);
            setTimeout(() => {
                toast.remove();
            }, 3500);
        });
    </script>
    @endscript
</div>
