@php
    $googleMapsApiKey = config('filament-google-maps.keys.web_key') ?: config('filament-google-maps.key');
@endphp

<div
    x-data="rideGeocoderForm({
        mapsKey: @js($googleMapsApiKey),
        pickup: @js($detectedPickup),
        destination: @js($destination),
        pickupResolved: true,
        destinationResolved: false,
        pickupLat: @js($pickupLat),
        pickupLng: @js($pickupLng),
        destinationLat: @js($destinationLat),
        destinationLng: @js($destinationLng),
    })"
    x-init="init()"
    class="min-h-screen bg-[radial-gradient(circle_at_top_left,_#fff7ed,_transparent_30%),radial-gradient(circle_at_top_right,_#ecfeff,_transparent_35%),#f4f7fb]"
>
    <div class="mx-auto flex min-h-screen max-w-md flex-col px-5 py-6">
        <section class="space-y-6">
            <div class="opacity-100 translate-y-0 transition-all duration-[620ms] ease-out-soft">
                <h2 class="text-5xl font-black leading-none tracking-tight text-slate-950">
                    ¿A dónde
                    <br>vas hoy?
                </h2>
                <p class="mt-4 max-w-sm text-lg leading-7 text-slate-500">
                    Detectamos tu ubicación para pedir el taxi más rápido y ayudarte justo al llegar.
                </p>
            </div>

            <div
                class="opacity-100 translate-y-0 transition-all duration-[620ms] ease-out-soft rounded-[32px] border border-white/40 bg-white/72 p-5 backdrop-blur-xl shadow-[0_12px_40px_rgba(15,23,42,0.08)]"
            >
                <div class="space-y-5">
                    <div>
                        <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                            Punto de recogida
                        </label>
                        <div class="relative flex items-center gap-2">
                            <div class="relative flex-1">
                                <input
                                    x-ref="pickupInput"
                                    x-model="pickup"
                                    @input.debounce.250ms="syncPickup()"
                                    @keydown.enter="handleAutocompleteEnter($event, 'pickup')"
                                    type="text"
                                    placeholder="Ej. Aeropuerto César Manrique Lanzarote"
                                    class="w-full rounded-2xl border border-white/40 bg-slate-100/70 px-4 py-4 pr-10 text-base font-semibold text-slate-700 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 transition duration-300"
                                />
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">📍</span>
                            </div>

                            <button
                                type="button"
                                @click="detectCurrentLocation()"
                                class="inline-flex h-14 w-14 items-center justify-center rounded-2xl border border-white/40 bg-white/70 text-xl text-slate-700 shadow-[0_10px_24px_rgba(15,23,42,0.08)] transition duration-300 hover:bg-white"
                                aria-label="Usar ubicación actual"
                            >
                                <span x-show="!detecting">🧭</span>
                                <span
                                    x-show="detecting"
                                    class="h-5 w-5 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700"
                                ></span>
                            </button>
                        </div>

                        @error('detectedPickup')
                        <p class="mt-2 text-xs font-semibold text-rose-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label class="block text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                            Tu destino
                        </label>

                        <input
                            x-ref="destinationInput"
                            x-model="destination"
                            @input.debounce.250ms="syncDestination()"
                            @keydown.enter="handleAutocompleteEnter($event, 'destination')"
                            type="text"
                            placeholder="Ej. Costa Teguise"
                            class="w-full rounded-2xl border border-white/40 bg-slate-100/70 px-4 py-4 text-lg font-bold text-slate-950 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-4 focus:ring-blue-100 transition duration-300"
                        />

                        <p
                            x-show="!destinationResolved && destination.length > 0"
                            class="text-xs font-semibold text-slate-400"
                        >
                            Usa flechas y Enter para elegir una sugerencia de Google.
                        </p>

                        @error('destination')
                        <p class="text-xs font-semibold text-rose-500">{{ $message }}</p>
                        @enderror

                        <div
                            x-show="destinationResolved && destinationLat && destinationLng"
                            x-transition.opacity.duration.250ms
                            class="overflow-hidden rounded-3xl border border-white/50 bg-white/80 shadow-[0_12px_30px_rgba(15,23,42,0.08)]"
                        >
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <div>
                                    <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                                        Vista previa
                                    </p>
                                    <p class="mt-1 text-sm font-semibold text-slate-700" x-text="destination"></p>
                                </div>

                                <div
                                    class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-700">
                                    ✓ Listo para continuar
                                </div>
                            </div>

                            <div x-ref="destinationMap" class="h-44 w-full bg-slate-100"></div>

                            <div
                                class="grid grid-cols-2 gap-3 border-t border-slate-100 px-4 py-3 text-xs text-slate-500">
                                <div>
                                    <span class="block text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Lat</span>
                                    <span x-text="destinationLat"></span>
                                </div>
                                <div>
                                    <span class="block text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">Lng</span>
                                    <span x-text="destinationLng"></span>
                                </div>
                            </div>

                            <div class="border-t border-slate-100 bg-slate-50/80 px-4 py-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-100 text-lg">
                                        ✨
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-slate-400">
                                            Zona detectada
                                        </p>
                                        <p class="mt-1 truncate text-sm font-semibold text-slate-800">
                                            📍 <span x-text="detectedZone()"></span>
                                        </p>
                                        <p class="mt-1 text-xs leading-5 text-slate-500">
                                            Todo listo para confirmar el trayecto y activar las recomendaciones al
                                            llegar.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border border-dashed border-slate-200 bg-slate-50/80 px-4 py-3 text-center text-sm font-medium text-slate-500">
                        <template x-if="!canSubmit() && destination.length === 0">
                            <span>Introduce tu destino para continuar.</span>
                        </template>

                        <template x-if="!canSubmit() && destination.length > 0 && !destinationResolved">
                            <span>Selecciona una opción de la lista para confirmar el destino.</span>
                        </template>

                        <template x-if="canSubmit() && !submitting">
                            <span>Trayecto listo. Continúa y te mostraremos el taxi confirmado.</span>
                        </template>

                        <template x-if="submitting">
                            <span>Confirmando trayecto y preparando tu llegada…</span>
                        </template>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-auto pt-8 pb-28">
            <div class="rounded-2xl bg-white/40 p-4 backdrop-blur-md">
                <p class="text-center text-xs font-medium text-slate-400">
                    TAXILANZ utiliza inteligencia de trayecto para conectar movilidad con experiencias locales únicas.
                </p>
            </div>
        </section>

        <div class="pointer-events-none fixed inset-x-0 bottom-0 z-40 px-4 pb-4 pt-6">
            <div class="mx-auto max-w-md">
                <div
                    x-show="destinationResolved"
                    x-transition.opacity.duration.200ms
                    class="mb-3 rounded-2xl border border-white/60 bg-white/78 px-4 py-3 shadow-[0_10px_24px_rgba(15,23,42,0.10)] backdrop-blur-xl"
                >
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-slate-400">
                                Zona detectada
                            </p>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-800">
                                📍 <span x-text="detectedZone()"></span>
                            </p>
                        </div>

                        <div class="rounded-full bg-emerald-100 px-3 py-1 text-[11px] font-bold text-emerald-700">
                            ✓ válida
                        </div>
                    </div>
                </div>

                <div
                    class="pointer-events-auto rounded-[28px] border border-white/60 bg-white/88 p-3 shadow-[0_20px_50px_rgba(15,23,42,0.18)] backdrop-blur-xl">
                    <div class="flex items-center gap-3">
                        <div class="min-w-0 flex-1 px-2">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-400">
                                Siguiente paso
                            </p>
                            <p
                                class="mt-1 truncate text-sm font-semibold text-slate-700"
                                x-text="canSubmit() ? ('Continuar hacia ' + destination) : 'Completa el trayecto para continuar'"
                            ></p>
                        </div>

                        <button
                            type="button"
                            @click="proceed()"
                            :disabled="!canSubmit() || submitting"
                            class="inline-flex min-w-[148px] items-center justify-center rounded-2xl px-5 py-4 text-sm font-black tracking-wide text-white transition duration-300"
                            :class="canSubmit() && !submitting
                                ? 'bg-slate-950 shadow-[0_16px_32px_rgba(15,23,42,0.28)] hover:-translate-y-1 active:scale-[0.98]'
                                : destination.length > 0
                                    ? 'bg-blue-400 text-white/90'
                                    : 'bg-slate-300 cursor-not-allowed'"
                        >
                            <span x-show="!submitting">Continuar</span>
                            <span x-show="submitting" class="inline-flex items-center gap-2">
                                <span
                                    class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white"></span>
                                Confirmando...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        x-show="confirmedTransition"
        x-transition.opacity.duration.220ms
        class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center bg-slate-950/18 backdrop-blur-[4px]"
    >
        <div
            class="mx-6 w-full max-w-xs rounded-[28px] bg-white/95 p-6 text-center shadow-[0_24px_60px_rgba(15,23,42,0.24)] animate-[fadeIn_.25s_ease-out]">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100 text-2xl">
                🚕
            </div>
            <p class="mt-4 text-[11px] font-bold uppercase tracking-[0.24em] text-slate-400">
                Preparando trayecto
            </p>
            <p class="mt-2 text-lg font-black text-slate-900">
                Confirmando taxi y siguiente paso
            </p>
            <p class="mt-2 text-sm leading-6 text-slate-500">
                Validamos tu destino y te llevamos al estado confirmado.
            </p>
        </div>
    </div>
</div>

@script
<script>
    Alpine.data('rideGeocoderForm', ({
                                         mapsKey,
                                         pickup,
                                         destination,
                                         pickupResolved,
                                         destinationResolved,
                                         pickupLat,
                                         pickupLng,
                                         destinationLat,
                                         destinationLng
                                     }) => ({
        mapsKey,
        pickup,
        destination,
        pickupResolved,
        destinationResolved,
        pickupAutocomplete: null,
        destinationAutocomplete: null,
        destinationMap: null,
        destinationMarker: null,
        pickupLat,
        pickupLng,
        destinationLat,
        destinationLng,
        geocoder: null,
        detecting: false,
        submitting: false,
        confirmedTransition: false,

        async init() {
            this.syncPickup(false);
            this.syncDestination(false);

            if (!this.mapsKey) {
                return;
            }

            await this.loadMaps();
            this.setupAutocomplete();
            this.setupDestinationMap();
        },

        async loadMaps() {
            if (window.google?.maps?.places) {
                this.geocoder = new window.google.maps.Geocoder();
                return;
            }

            if (!window.__rideMapsLoader) {
                window.__rideMapsLoader = new Promise((resolve, reject) => {
                    const callbackName = 'initRideMapsAutocomplete';

                    window[callbackName] = () => {
                        this.geocoder = new window.google.maps.Geocoder();
                        resolve();
                        delete window[callbackName];
                    };

                    const script = document.createElement('script');
                    script.src = `https://maps.googleapis.com/maps/api/js?key=${this.mapsKey}&libraries=places&callback=${callbackName}`;
                    script.async = true;
                    script.defer = true;
                    script.onerror = reject;
                    document.head.appendChild(script);
                });
            }

            await window.__rideMapsLoader;

            if (!this.geocoder && window.google?.maps) {
                this.geocoder = new window.google.maps.Geocoder();
            }
        },

        setupAutocomplete() {
            const lanzaroteBounds = new window.google.maps.LatLngBounds(
                new window.google.maps.LatLng(28.78, -13.95),
                new window.google.maps.LatLng(29.47, -13.37),
            );

            const options = {
                componentRestrictions: {country: 'es'},
                bounds: lanzaroteBounds,
                fields: ['address_components', 'formatted_address', 'geometry', 'name'],
                strictBounds: true,
                types: ['geocode', 'establishment'],
            };

            this.pickupAutocomplete = new window.google.maps.places.Autocomplete(this.$refs.pickupInput, options);
            this.destinationAutocomplete = new window.google.maps.places.Autocomplete(this.$refs.destinationInput, options);

            this.pickupAutocomplete.addListener('place_changed', () => {
                this.applyPlace(this.pickupAutocomplete.getPlace(), 'pickup');
            });

            this.destinationAutocomplete.addListener('place_changed', () => {
                this.applyPlace(this.destinationAutocomplete.getPlace(), 'destination');
            });
        },

        setupDestinationMap() {
            if (!window.google?.maps || !this.$refs.destinationMap) {
                return;
            }

            const initialCenter = {
                lat: this.destinationLat ? parseFloat(this.destinationLat) : 28.9574,
                lng: this.destinationLng ? parseFloat(this.destinationLng) : -13.5552,
            };

            this.destinationMap = new window.google.maps.Map(this.$refs.destinationMap, {
                center: initialCenter,
                zoom: this.destinationLat && this.destinationLng ? 14 : 10,
                disableDefaultUI: true,
                zoomControl: true,
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });

            if (this.destinationLat && this.destinationLng) {
                this.destinationMarker = new window.google.maps.Marker({
                    position: initialCenter,
                    map: this.destinationMap,
                    title: this.destination,
                });
            }
        },

        updateDestinationMap(lat, lng, title = '') {
            if (!this.destinationMap || !window.google?.maps) {
                return;
            }

            const position = {
                lat: parseFloat(lat),
                lng: parseFloat(lng),
            };

            this.destinationMap.setCenter(position);
            this.destinationMap.setZoom(14);

            if (!this.destinationMarker) {
                this.destinationMarker = new window.google.maps.Marker({
                    position,
                    map: this.destinationMap,
                    title,
                });

                return;
            }

            this.destinationMarker.setPosition(position);
            this.destinationMarker.setTitle(title);
        },

        applyPlace(place, type) {
            if (!place?.geometry?.location) {
                return;
            }

            const label = place.formatted_address || place.name || '';
            const lat = place.geometry.location.lat().toString();
            const lng = place.geometry.location.lng().toString();

            if (type === 'pickup') {
                this.pickup = label;
                this.pickupResolved = true;
                this.pickupLat = lat;
                this.pickupLng = lng;
                this.$wire.setPickupLocation(label, lat, lng);
                return;
            }

            this.destination = label;
            this.destinationLat = lat;
            this.destinationLng = lng;
            this.destinationResolved = true;
            this.$wire.setDestinationLocation(label, lat, lng);
            this.$nextTick(() => this.updateDestinationMap(lat, lng, label));
        },

        syncPickup(fromUser = true) {
            if (fromUser) {
                this.pickupResolved = false;
                this.pickupLat = null;
                this.pickupLng = null;
                this.$wire.set('pickupLat', null);
                this.$wire.set('pickupLng', null);
            }

            this.$wire.set('detectedPickup', this.pickup);
        },

        syncDestination(fromUser = true) {
            if (fromUser) {
                this.destinationResolved = false;
                this.destinationLat = null;
                this.destinationLng = null;
                this.$wire.set('destinationLat', null);
                this.$wire.set('destinationLng', null);
            }

            this.$wire.set('destination', this.destination);

            if (!fromUser || !this.destinationMap || !this.destinationMarker) {
                return;
            }

            this.destinationMarker.setMap(null);
            this.destinationMarker = null;
            this.destinationMap.setCenter({lat: 28.9574, lng: -13.5552});
            this.destinationMap.setZoom(10);
        },

        canSubmit() {
            return this.pickupResolved && this.destinationResolved && this.pickup.length > 0 && this.destination.length > 0;
        },

        proceed() {
            if (!this.canSubmit() || this.submitting) {
                return;
            }

            this.submitting = true;

            setTimeout(() => {
                this.confirmedTransition = true;

                setTimeout(() => {
                    this.$wire.submit()
                        .then(() => {
                            // puedes redirigir aquí si quieres
                        })
                        .catch(() => {
                        })
                        .finally(() => {
                            this.submitting = false;
                            this.confirmedTransition = false;
                        });
                }, 400);
            }, 120);
        },

        maybeSubmit() {
            return;
        },

        getVisiblePacContainer() {
            return [...document.querySelectorAll('.pac-container')]
                .find((element) => element.offsetParent !== null);
        },

        handleAutocompleteEnter(event, type) {
            const visibleContainer = this.getVisiblePacContainer();

            if (!visibleContainer) {
                return;
            }

            event.preventDefault();

            const highlightedItem = visibleContainer.querySelector('.pac-item-selected');
            const firstItem = visibleContainer.querySelector('.pac-item');
            const targetItem = highlightedItem || firstItem;

            if (!targetItem) {
                return;
            }

            targetItem.dispatchEvent(new MouseEvent('mousedown', {bubbles: true}));
            targetItem.dispatchEvent(new MouseEvent('mouseup', {bubbles: true}));
            targetItem.click();
        },

        detectCurrentLocation() {
            if (!navigator.geolocation || !this.geocoder) {
                return;
            }

            this.detecting = true;

            navigator.geolocation.getCurrentPosition(
                ({coords}) => {
                    const location = {
                        lat: coords.latitude,
                        lng: coords.longitude,
                    };

                    this.geocoder.geocode({location}, (results, status) => {
                        this.detecting = false;

                        if (status !== 'OK' || !results?.[0]) {
                            return;
                        }

                        const place = {
                            formatted_address: results[0].formatted_address,
                            geometry: {
                                location: {
                                    lat: () => coords.latitude,
                                    lng: () => coords.longitude,
                                },
                            },
                        };

                        this.pickupLat = coords.latitude.toString();
                        this.pickupLng = coords.longitude.toString();
                        this.applyPlace(place, 'pickup');
                    });
                },
                () => {
                    this.detecting = false;
                },
                {
                    enableHighAccuracy: true,
                    timeout: 8000,
                },
            );
        },

        detectedZone() {
            if (!this.destination || typeof this.destination !== 'string') {
                return 'Destino listo';
            }

            const parts = this.destination
                .split(',')
                .map(part => part.trim())
                .filter(Boolean);

            return parts[0] || 'Destino listo';
        },
    }));
</script>
@endscript
