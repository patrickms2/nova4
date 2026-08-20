@php
    $stats = $this->stats();
    $isEmployeePortal = $this->isEmployeePortal();
    $identification = $identification ?? $this->identificationData();
    $portalNotifiable = auth('taxista')->user() ?? auth('web')->user();
    $traccarMapBaseUrl = preg_replace('#/api/?$#', '', rtrim((string) (config('traccar.url') ?: config('traccar.base_url') ?: ''), '/'));
    $googleMapsLastLocationUrl = ($lastLat && $lastLng) ? sprintf('https://www.google.com/maps?q=%s,%s', $lastLat, $lastLng) : null;
@endphp

<div
    x-data="{
        isOnline: @entangle('isOnline'),
        trackingActive: @entangle('trackingActive'),
        pendingOnline: false,
        pendingTracking: false,
        pendingShareLocation: false,
        pendingTaxiTrackingId: null,
        showAnnouncements: @entangle('showAnnouncements'),
        announcementsAutoKey: @entangle('announcementsAutoKey'),
        taxistaUserId: @js((int) ($portalNotifiable?->getKey() ?? 0)),
        notifiableType: @js($portalNotifiable?->getMorphClass() ?? 'App.Models.User'),
        _watchId: null,
        _lastSentAt: 0,
        _trackingInterval: 20000,

        notifyPortalHint(message, tone = 'neutral', duration = 1800) {
            window.dispatchEvent(new CustomEvent('portal-action-hint', {
                detail: { message, tone, duration },
            }))
        },

        syncAnnouncementsAutoOpen() {
            const storageKey = 'portal-mobile-last-announcements-key'

            if (!this.announcementsAutoKey) {
                try {
                    window.localStorage.removeItem(storageKey)
                } catch (e) {}

                return
            }

            try {
                if (window.localStorage.getItem(storageKey) !== this.announcementsAutoKey) {
                    this.showAnnouncements = true
                    window.localStorage.setItem(storageKey, this.announcementsAutoKey)
                }
            } catch (e) {}
        },

        async toggleOnlineAction() {
            if (this.pendingOnline) {
                return
            }

            this.pendingOnline = true
            this.notifyPortalHint('Actualizando estado...')

            try {
                await $wire.toggleOnline()
            } finally {
                window.setTimeout(() => {
                    this.pendingOnline = false
                }, 180)
            }
        },

        shareLocation() {
            this.shareLocationForTaxi()
        },

        shareLocationForTaxi() {
            if (this.pendingShareLocation) {
                return
            }

            this.pendingShareLocation = true
            this.notifyPortalHint('Compartiendo ubicacion...')

            if (!navigator.geolocation) {
                $wire.locationFailed('Geolocalizacion no disponible en este dispositivo.')
                this.pendingShareLocation = false
                return
            }

            navigator.geolocation.getCurrentPosition(
                async (pos) => {
                    try {
                        await $wire.saveLocation(pos.coords.latitude, pos.coords.longitude)
                    } finally {
                        this.pendingShareLocation = false
                    }
                },
                async (err) => {
                    let message = 'No se pudo obtener tu ubicacion.'
                    if (err && err.code === 1) message = 'Permiso denegado para ubicacion.'
                    if (err && err.code === 2) message = 'Ubicacion no disponible.'
                    if (err && err.code === 3) message = 'Timeout obteniendo ubicacion.'
                    await $wire.locationFailed(message)
                    this.pendingShareLocation = false
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
            )
        },

        async startAutoTracking() {
            if (this.pendingTracking || this._watchId !== null || !navigator.geolocation) return

            this.pendingTracking = true
            this.notifyPortalHint('Activando GPS...')

            this._watchId = navigator.geolocation.watchPosition(
                (pos) => {
                    const now = Date.now()
                    if (now - this._lastSentAt < this._trackingInterval) return
                    this._lastSentAt = now

                    const speed = pos.coords.speed ?? 0
                    const heading = pos.coords.heading ?? 0
                    const accuracy = pos.coords.accuracy ?? null

                    $wire.trackLocation(
                        pos.coords.latitude,
                        pos.coords.longitude,
                        speed,
                        heading,
                        accuracy
                    )
                },
                (err) => {
                    console.warn('Auto-tracking error:', err.message)
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 5000 }
            )

            this.trackingActive = true

            try {
                await $wire.startTracking()
            } finally {
                this.pendingTracking = false
            }
        },

        async stopAutoTracking() {
            if (this.pendingTracking) return

            this.pendingTracking = true
            this.notifyPortalHint('Deteniendo GPS...')

            if (this._watchId !== null) {
                navigator.geolocation.clearWatch(this._watchId)
                this._watchId = null
            }
            this.trackingActive = false

            try {
                await $wire.stopTracking()
            } finally {
                this.pendingTracking = false
            }
        },

        toggleTracking() {
            if (this.pendingTracking) {
                return
            }

            if (this.trackingActive) {
                this.stopAutoTracking()
            } else {
                this.startAutoTracking()
            }
        },

        async toggleTaxiTrackingAction(taxiId) {
            if (this.pendingTaxiTrackingId !== null) {
                return
            }

            this.pendingTaxiTrackingId = taxiId
            this.notifyPortalHint('Cambiando tracking...')

            try {
                await $wire.toggleTaxiTracking(taxiId)
            } finally {
                window.setTimeout(() => {
                    if (this.pendingTaxiTrackingId === taxiId) {
                        this.pendingTaxiTrackingId = null
                    }
                }, 180)
            }
        },

        init() {
            window.Laravel = Object.assign(window.Laravel ?? {}, {
                userId: this.taxistaUserId,
                notifiableType: this.notifiableType,
            })

            window.dispatchEvent(new CustomEvent('laravel-user-context', {
                detail: {
                    userId: this.taxistaUserId,
                    notifiableType: this.notifiableType,
                },
            }))

            this.$watch('isOnline', (val) => {
                if (val) {
                    this.startAutoTracking()
                } else {
                    this.stopAutoTracking()
                }
            })

            if (this.isOnline) {
                this.$nextTick(() => this.startAutoTracking())
            }

            this.syncAnnouncementsAutoOpen()

            this.$watch('announcementsAutoKey', () => {
                this.syncAnnouncementsAutoOpen()
            })

            if (window.initSupportScreenshot) {
                window.initSupportScreenshot()
            }
        },
    }"
    @taxista-presence-updated.window="if (($event.detail?.taxistaUserId ?? null) === taxistaUserId) { isOnline = !!($event.detail?.isOnline ?? false) }"
    @open-announcements.window="$wire.openAnnouncements()"
    class="{{ $embedded ? '' : 'min-h-screen mobile-portal-screen' }}"
>
    @if(! $embedded)
        <div class="sticky top-0 z-40 px-3 pt-2">
            <div class="tl-s1 px-3 py-2.5">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-2.5">
                        <div class="shrink-0 [&_img]:h-8 [&_img]:w-auto">
                            {!! view('portal.brand')->render() !!}
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-widest text-white/60"></p>
                            <p class="text-sm font-semibold text-white/90 truncate"></p>
                        </div>
                    </div>

                    <div class="flex items-center gap-1.5">
                        <button
                            type="button"
                            class="tl-s3 flex items-center justify-center"
                            x-on:click="toggleOnlineAction()"
                            x-bind:disabled="pendingOnline"
                            x-bind:aria-label="isOnline ? 'Online' : 'Offline'"
                            x-bind:title="isOnline ? 'Online' : 'Offline'"
                            style="width: 2.35rem; height: 2.35rem; padding: 0;"
                        >
                        <span class="inline-flex items-center gap-2">
                            <span
                                class="h-2 w-2 rounded-full"
                                :class="isOnline ? 'bg-emerald-400' : 'bg-rose-400'"
                            ></span>
                        </span>
                        </button>

                        <span
                            x-show="trackingActive"
                            x-transition
                            class="inline-flex items-center gap-1 rounded-full bg-emerald-500/20 px-2 py-1 text-[10px] font-semibold text-emerald-400"
                            title="GPS activo — enviando ubicación a Traccar"
                        >
                        <span class="relative flex h-2 w-2">
                            <span
                                class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                            <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                        </span>
                        GPS
                    </span>

                        <button type="button" class="tl-s3 flex items-center justify-center" aria-label="Anuncios"
                                style="width: 2.35rem; height: 2.35rem; padding: 0;"
                                wire:click="toggleAnnouncements">
                            <div class="relative">
                                <svg class="text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     style="width: 1.12rem; height: 1.12rem;"
                                     stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M18 8a6 6 0 0 0-12 0c0 3.866-3 6-3 6h18s-3-2.134-3-6ZM13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>
                                @if (($unreadAnnouncements ?? 0) > 0)
                                    <span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-emerald-400"></span>
                                @endif
                            </div>
                        </button>

                        <button type="button" class="tl-s3 flex items-center justify-center" aria-label="Notificaciones"
                                style="width: 2.35rem; height: 2.35rem; padding: 0;"
                                wire:click="toggleNotifications">
                            <div class="relative">
                                <svg class="text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     style="width: 1.12rem; height: 1.12rem;"
                                     stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 0 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 0-2.312 6.022c1.733.64 3.56 1.08 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"/>
                                </svg>
                                @if (($unreadNotifications ?? 0) > 0)
                                    <span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-amber-400"></span>
                                @endif
                            </div>
                        </button>

                        <button
                            class="tl-s3 flex items-center justify-center"
                            type="button"
                            x-on:click="$dispatch('open-spotlight')"
                            aria-label="Buscar"
                            style="width: 2.35rem; height: 2.35rem; padding: 0;"
                        >
                            <svg class="text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 style="width: 1.05rem; height: 1.05rem;"
                                 stroke-width="1.5"
                                 aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6Z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showNotifications)
        <div class="fixed inset-0 z-50" aria-modal="true" role="dialog">
            <div class="absolute inset-0 bg-black/60" wire:click="closeNotifications"></div>
            <div class="absolute left-0 right-0 top-0 mx-auto max-w-xl px-4 pt-6">
                <div class="tl-s3 p-4">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm font-semibold text-white/90">Notificaciones</p>
                            <p class="text-xs text-white/50">{{ (int) ($unreadNotifications ?? 0) }} sin leer</p>
                        </div>
                        <div class="flex items-center gap-2">
                            @if(($unreadNotifications ?? 0) > 0)
                                <button type="button" class="tl-s2 px-3 py-2 text-xs text-white/70"
                                        wire:click="markAllNotificationsAsRead">
                                    Marcar todas
                                </button>
                            @endif
                            <button type="button" class="tl-s2 px-3 py-2" aria-label="Cerrar"
                                    wire:click="closeNotifications">
                                <svg class="h-4 w-4 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 max-h-[70vh] overflow-y-auto">
                        @forelse($notifications as $n)
                            <button
                                type="button"
                                class="w-full text-left tl-s2 tl-s2-hover p-3"
                                wire:click="markNotificationAsRead('{{ $n['id'] }}')"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="text-sm font-semibold text-white/90 truncate">{{ $n['title'] }}</p>
                                        @if(!empty($n['body']))
                                            <p class="mt-1 text-xs text-white/50">{{ $n['body'] }}</p>
                                        @endif
                                        <p class="mt-2 text-[11px] text-white/60">{{ $n['created_at'] }}</p>
                                    </div>
                                    @if(($n['read'] ?? false) === false)
                                        <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-amber-400"></span>
                                    @endif
                                </div>
                            </button>
                        @empty
                            <div class="tl-s3 p-6 text-center">
                                <p class="text-white/70">No notifications</p>
                                <p class="mt-1 text-sm text-white/60">Please check again later.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($showAnnouncements)
        <div class="fixed inset-0 z-[10000] flex items-center justify-center bg-black/70 p-4 backdrop-blur-sm" aria-modal="true" role="dialog">
            <div class="absolute inset-0" wire:click="closeAnnouncements"></div>
            <div class="dark:tl-s1 tl-interactive relative w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-gray-900 shadow-2xl">
                <div class="pointer-events-none absolute inset-x-0 top-0 h-24 bg-[radial-gradient(circle_at_top,rgba(207,31,46,0.16),transparent_65%)]"></div>
                <div class="h-1 bg-white/5">
                    <div
                        class="h-full rounded-r-full bg-red-500 transition-all duration-500 ease-out"
                        style="width: {{ min(100, max(20, (int) (($unreadAnnouncements ?? 0) > 0 ? 100 : 20))) }}%;"
                    ></div>
                </div>

                <div class="p-6 sm:p-8">
                    <div class="mb-6 flex items-center justify-between">
                        <span class="text-xs font-medium uppercase tracking-wider text-white/40">Avisos Importantes</span>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-white/30">{{ (int) ($unreadAnnouncements ?? 0) }} sin leer</span>
                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-white/10 bg-white/5 text-white/50 transition hover:bg-white/10 hover:text-white/80"
                                aria-label="Cerrar avisos"
                                wire:click="closeAnnouncements"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mb-5 flex justify-center">
                        <div class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-[#cf1f2e]/15 ring-1 ring-[#cf1f2e]/30">
                            <div class="absolute inset-2 rounded-xl bg-[#cf1f2e]/10 blur-md"></div>
                            <svg class="relative h-8 w-8 text-[#cf1f2e]" data-slot="icon" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"></path>
                            </svg>
                        </div>
                    </div>

                    <div class="text-center space-y-3">
                        <h3 class="text-xl font-bold text-white">
                            {{ $selectedAnnouncement ? ($selectedAnnouncement['title'] ?? 'Aviso') : 'Avisos de tu portal' }}
                        </h3>
                        <p class="text-sm leading-relaxed text-white/60">
                            {{ $selectedAnnouncement ? 'Contenido completo del aviso seleccionado.' : 'Listado de avisos recientes. Pulsa uno para abrirlo.' }}
                        </p>
                    </div>

                    @if($selectedAnnouncement)
                        <div class="tl-s2 mt-6 rounded-2xl border border-white/10 bg-white/5 p-5">
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-full border border-[#cf1f2e]/25 bg-[#cf1f2e]/12 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-[#ffb3bb]">
                                    {{ ($selectedAnnouncement['read'] ?? false) ? 'Leído' : 'Nuevo' }}
                                </span>
                                <span class="text-[11px] text-white/50">{{ $selectedAnnouncement['starts_at'] ?? '' }}</span>
                            </div>

                            <div class="mt-4 h-px bg-gradient-to-r from-[#cf1f2e]/30 via-white/10 to-transparent"></div>

                            @if(!empty($selectedAnnouncement['content_html']))
                                <div class="announcement-content mt-4 max-h-[40vh] overflow-y-auto pr-1 text-sm leading-7 text-white/75 [&_a]:text-[#ff8a96] [&_a]:underline [&_blockquote]:border-l-2 [&_blockquote]:border-[#cf1f2e]/30 [&_blockquote]:pl-4 [&_blockquote]:text-white/60 [&_h1]:text-2xl [&_h1]:font-semibold [&_h1]:text-white [&_h2]:text-xl [&_h2]:font-semibold [&_h2]:text-white [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-white [&_li]:ml-4 [&_li]:list-disc [&_p]:mb-4">
                                    {!! $selectedAnnouncement['content_html'] !!}
                                </div>
                            @else
                                <p class="mt-4 text-sm text-white/50">Este aviso no tiene contenido.</p>
                            @endif
                        </div>
                    @else
                        <div class="mt-6 max-h-[44vh] space-y-3 overflow-y-auto pr-1">
                            @forelse($announcements as $a)
                                <button
                                    type="button"
                                    class="tl-s2 tl-s2-hover group relative w-full overflow-hidden rounded-2xl border p-4 text-left transition"
                                    @class([
                                        'border-[#cf1f2e]/20 bg-white/5 hover:bg-white/8' => ! ($a['read'] ?? false),
                                        'border-white/6 bg-white/[0.03] hover:bg-white/[0.05]' => ($a['read'] ?? false),
                                    ])
                                    wire:click="openAnnouncement({{ $a['id'] }})"
                                >
                                    <span class="absolute inset-y-3 left-0 w-1 rounded-r-full {{ ($a['read'] ?? false) ? 'bg-white/8' : 'bg-[#cf1f2e]/70' }}"></span>
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="min-w-0 pr-3">
                                            <p class="truncate text-sm font-semibold {{ ($a['read'] ?? false) ? 'text-white/70' : 'text-white/90' }}">{{ $a['title'] }}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if(($a['read'] ?? false) === true)
                                                <span class="rounded-full border border-white/8 bg-white/[0.04] px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-white/28">Leído</span>
                                            @else
                                                <span class="rounded-full border border-[#cf1f2e]/25 bg-[#cf1f2e]/12 px-2 py-0.5 text-[10px] font-medium uppercase tracking-wide text-[#ffb3bb]">Nuevo</span>
                                            @endif
                                            <svg class="h-4 w-4 shrink-0 text-white/25 transition duration-200 group-hover:translate-x-0.5 group-hover:text-white/55" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m9 6 6 6-6 6"/>
                                            </svg>
                                        </div>
                                    </div>
                                </button>
                            @empty
                                <div class="rounded-xl border border-white/8 bg-white/5 p-6 text-center">
                                    <p class="text-white/70">No hay avisos</p>
                                    <p class="mt-1 text-sm text-white/50">Vuelve más tarde para ver nuevas comunicaciones.</p>
                                </div>
                            @endforelse
                        </div>
                    @endif

                    <div class="mt-8 flex items-center justify-between">
                        @if($selectedAnnouncement)
                            <button
                                type="button"
                                class="text-sm text-white/40 transition-colors hover:text-white/60"
                                wire:click="backToAnnouncementsList"
                            >
                                Volver al listado
                            </button>
                        @else
                            <button
                                type="button"
                                class="text-sm text-white/40 transition-colors hover:text-white/60"
                                wire:click="closeAnnouncements"
                            >
                                Ver más tarde
                            </button>
                        @endif

                        <div class="flex items-center gap-3">
                            @if(!$selectedAnnouncement && ($unreadAnnouncements ?? 0) > 0)
                                <button
                                    type="button"
                                    class="rounded-lg border border-white/10 px-4 py-2 text-sm text-white/60 transition-colors hover:border-white/20 hover:text-white/80"
                                    wire:click="markAllAnnouncementsAsRead"
                                >
                                    Marcar todas
                                </button>
                            @endif

                            <button
                                type="button"
                                class="rounded-lg bg-[#cf1f2e] px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-[#e02a3a]"
                                wire:click="{{ $selectedAnnouncement ? 'backToAnnouncementsList' : 'closeAnnouncements' }}"
                            >
                                {{ $selectedAnnouncement ? 'Volver' : 'Entendido' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="mx-auto max-w-4xl px-2 py-2 space-y-6">
        <div class="grid lg:grid-cols-4 grid-cols-2 gap-3">
            @unless($isEmployeePortal)

                <a
                    href="{{ \App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource::getUrl('index', panel: 'portal') }}"
                    class="tl-s2  p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Taxis</p>
                            <p class="mt-2 text-3xl font-semibold text-white/90">{{ (int) ($stats['taxis'] ?? 0) }}</p>
                            <p class="mt-1 text-xs text-white/50">Asignados</p>
                        </div>
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-cyan-500/10 ring-1 ring-cyan-500/20">
                        <svg class="h-5 w-5 text-cyan-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8.25 18.75a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0H15m6.75 0a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0m3 0H23.25V14.25a2.25 2.25 0 0 0-.659-1.591l-1.5-1.5A2.25 2.25 0 0 0 19.5 10.5H15V6.75A2.25 2.25 0 0 0 12.75 4.5h-9A2.25 2.25 0 0 0 1.5 6.75v12h2.25"/>
                        </svg>
                    </span>
                    </div>
                </a>
            @endunless

            <a
                href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'citas'], panel: 'portal') }}"
                class="tl-s2 tl-s2-hover p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Citas</p>
                        <p class="mt-2 text-3xl font-semibold text-white/90">{{ (int) ($stats['citas_hoy'] ?? 0) }}</p>
                        <p class="mt-1 text-xs text-white/50">Total</p>
                    </div>
                    <span
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-500/10 ring-1 ring-blue-500/20">
                        <svg class="h-5 w-5 text-blue-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
                        </svg>
                    </span>
                </div>
            </a>

            <a
                href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'documentos'], panel: 'portal') }}"
                class="tl-s2 tl-s2-hover p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Docs</p>
                        <p class="mt-2 text-3xl font-semibold text-white/90">{{ (int) ($stats['documentos'] ?? 0) }}</p>
                        <p class="mt-1 text-xs text-white/50">PDFs</p>
                    </div>
                    <span
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 ring-1 ring-amber-500/20">
                        <svg class="h-5 w-5 text-amber-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 6.75A2.25 2.25 0 0 1 4.5 4.5h4.379a2.25 2.25 0 0 1 1.591.659l1.622 1.622a2.25 2.25 0 0 0 1.591.659H19.5a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25v-10.5Z"/>
                        </svg>
                    </span>
                </div>
            </a>

            <a
                href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'tickets'], panel: 'portal') }}"
                class="tl-s2 tl-s2-hover p-5"
            >
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Tickets</p>
                        <p class="mt-2 text-3xl font-semibold text-white/90">{{ (int) ($stats['tickets_abiertos'] ?? 0) }}</p>
                        <p class="mt-1 text-xs text-white/50">Abiertos</p>
                    </div>
                    <span
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-500/10 ring-1 ring-red-500/20">
                        <svg class="h-5 w-5 text-red-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m0 3.75h.008v.008H12v-.008Zm9-3.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                </div>
            </a>

            @if($isEmployeePortal)
                <a
                    href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}"
                    class="tl-s2 tl-s2-hover p-5"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Turnos</p>
                            <p class="mt-2 text-3xl font-semibold text-white/90">{{ (int) ($stats['turnos_mes'] ?? 0) }}</p>
                            <p class="mt-1 text-xs text-white/50">
                                M:{{ $stats['turnos_m'] ?? 0 }}
                                P:{{ $stats['turnos_p'] ?? 0 }}
                                N:{{ $stats['turnos_n'] ?? 0 }}
                                L:{{ $stats['turnos_l'] ?? 0 }}
                            </p>
                        </div>
                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-violet-500/10 ring-1 ring-violet-500/20">
                        <svg class="h-5 w-5 text-violet-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                    </div>
                </a>
            @endif
        </div>
        <!-- SEPARADOR DE IDENTIDAD COMPACTO -->
        <div class="border-t    border-white/10 pt-1 pb-0">
        </div>
        <!-- Control Section: SPOTLIGHT | REGISTRO HORA | ONLINE | SEGUIMIENTO -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            <!-- SPOTLIGHT -->
            <button
                type="button"
                x-on:click="$dispatch('open-spotlight')"
                class="tl-s2 tl-s2-hover p-4 text-left group"
                title="Abrir Spotlight - Acceso rápido a todas las funciones"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Spotlight</p>
                        <p class="mt-1 text-xs text-white/60">Búsqueda rápida</p>
                    </div>
                    <span
                        class="inline-flex items-center justify-center rounded-xl bg-indigo-500/10 ring-1 ring-indigo-500/20 group-hover:bg-indigo-500/20 transition-colors"
                        style="width: 2.6rem; height: 2.6rem;">
                        <svg class="text-indigo-200" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             style="width: 1.15rem; height: 1.15rem;"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m21 21-4.35-4.35m0 0A7.5 7.5 0 1 0 6.05 6.05a7.5 7.5 0 0 0 10.6 10.6Z"/>
                        </svg>
                    </span>
                </div>
            </button>

            @php
                $attendance = $this->todayAttendance;
                $isCheckedIn = $attendance['checked_in'];
                $hasCompletedDay = $attendance['end'] !== null;

                // Determine background class
                if ($hasCompletedDay) {
                    $bgClass = 'bg-emerald-500/10 ring-emerald-500/20 group-hover:bg-emerald-500/20';
                    $textClass = 'text-emerald-200';
                } elseif ($isCheckedIn) {
                    $bgClass = 'bg-amber-500/10 ring-amber-500/20 group-hover:bg-amber-500/20';
                    $textClass = 'text-amber-200';
                } else {
                    $bgClass = 'bg-rose-500/10 ring-rose-500/20 group-hover:bg-rose-500/20';
                    $textClass = 'text-red-200';
                }
            @endphp
                <!-- REGISTRO HORA -->
            <button
                type="button"
                wire:click="toggleTimeClock"
                class="tl-s2 tl-s2-hover p-4 text-left group"
                title="{{ $hasCompletedDay ? 'Jornada completa — ' . $attendance['start'] . ' — ' . $attendance['end'] : ($isCheckedIn ? 'Fichado — Entrada: ' . $attendance['start'] : 'Sin fichar') }}"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Registro</p>
                        <p class="mt-1 text-xs {{ $textClass }}">
                            @if($hasCompletedDay)
                                {{ $attendance['start'] }} — {{ $attendance['end'] }}
                            @elseif($isCheckedIn)
                                Entrada: {{ $attendance['start'] }}
                            @else
                                Sin entrada
                            @endif
                        </p>
                    </div>
                    <span
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg ring-1 transition-colors {{ $bgClass }}">
                        <svg class="h-4 w-4 {{ $textClass }}" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                        </svg>
                    </span>
                </div>
            </button>

            <!-- Time Clock Dropdown -->
            @if($showTimeClockDropdown)
                <div class="fixed inset-0 z-50" aria-modal="true" role="dialog"
                     x-data="{ open: @entangle('showTimeClockDropdown'), now: '{{ now()->format('H:i:s') }}' }"
                     x-init="setInterval(() => { now = new Date().toLocaleTimeString('es-ES', { hour12: false }) }, 1000)">
                    <div class="absolute inset-0 bg-black/60" wire:click="toggleTimeClock"></div>
                    <div class="absolute left-0 right-0 top-0 mx-auto max-w-sm px-4 pt-6">
                        <div class="tl-s3 p-4">
                            <!-- Header -->
                            <div class="mb-3 text-center">
                                <p class="text-3xl font-bold text-white tabular-nums font-mono" x-text="now"></p>
                                <p class="mt-1 text-xs text-zinc-400">{{ now()->translatedFormat('l, j F Y') }}</p>
                            </div>

                            <div class="h-px bg-white/10 my-3"></div>

                            <!-- Time Clock Component -->
                            @livewire('portal-time-clock')
                        </div>
                    </div>
                </div>
            @endif

            <!-- ONLINE/OFFLINE - Solo para taxistas -->
            @unless($isEmployeePortal)
            <button
                type="button"
                x-on:click="toggleOnlineAction()"
                x-bind:disabled="pendingOnline"
                x-bind:aria-label="isOnline ? 'Cambiar a Offline' : 'Cambiar a Online'"
                x-bind:title="isOnline ? 'Online - Click para desconectar' : 'Offline - Click para conectar'"
                class="tl-s2 tl-s2-hover p-4 text-left group"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Estado</p>
                        <p class="mt-1 text-xs" :class="isOnline ? 'text-emerald-400' : 'text-rose-400'"
                           x-text="pendingOnline ? 'Actualizando…' : (isOnline ? 'Online' : 'Offline')"></p>
                    </div>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg ring-1 transition-colors"
                          :class="isOnline ? 'bg-emerald-500/10 ring-emerald-500/20 group-hover:bg-emerald-500/20' : 'bg-rose-500/10 ring-rose-500/20 group-hover:bg-rose-500/20'">
                        <span class="relative flex h-3 w-3">
                            <span class="absolute inline-flex h-full w-full rounded-full opacity-75"
                                  :class="isOnline ? 'bg-emerald-400 animate-ping' : 'bg-rose-400'"></span>
                            <span class="relative inline-flex h-3 w-3 rounded-full"
                                  :class="isOnline ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                        </span>
                    </span>
                </div>
            </button>
            @endunless

            <!-- SEGUIMIENTO - Solo para taxistas -->
            @unless($isEmployeePortal)
            <button
                type="button"
                x-on:click="toggleTracking()"
                x-bind:disabled="pendingTracking"
                x-bind:aria-label="trackingActive ? 'Detener seguimiento GPS' : 'Iniciar seguimiento GPS'"
                x-bind:title="trackingActive ? 'GPS Activo - Enviando ubicación' : 'GPS Inactivo - Click para activar'"
                class="tl-s2 tl-s2-hover p-4 text-left group"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-white/60">Seguimiento</p>
                        <p class="mt-1 text-xs" :class="trackingActive ? 'text-emerald-400' : 'text-white/60'"
                           x-text="pendingTracking ? (trackingActive ? 'Deteniendo…' : 'Activando…') : (trackingActive ? 'GPS Activo' : 'GPS Inactivo')"></p>
                    </div>
                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg ring-1 transition-colors"
                          :class="trackingActive ? 'bg-emerald-500/10 ring-emerald-500/20 group-hover:bg-emerald-500/20' : 'bg-gray-500/10 ring-gray-500/20 group-hover:bg-gray-500/20'">
                        <svg class="h-4 w-4" :class="trackingActive ? 'text-emerald-200' : 'text-gray-400'"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"
                             aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                        </svg>
                    </span>
                </div>
            </button>
            @endunless
        </div>


        <x-portal.card padding="p-6">
            <div class="flex items-center justify-between gap-3">


                @if($isEmployeePortal)
                    <x-portal.button
                        variant="ghost"
                        as="a"
                        href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}"
                    >
                        Mis turnos
                    </x-portal.button>
                @else

                @endif
            </div>

            @if($isEmployeePortal)
                @php
                    $pendingReqs = $this->pendingRequests();
                @endphp
                @if(count($pendingReqs['timeoff']) > 0 || count($pendingReqs['swaps']) > 0)
                    <div class="mt-2 space-y-2">
                        <p class="text-amber-400 text-[11px] font-bold uppercase tracking-widest flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                 stroke="currentColor" class="h-3.5 w-3.5">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                            </svg>
                            Solicitudes pendientes
                        </p>
                        @foreach($pendingReqs['timeoff'] as $req)
                            <div
                                class="flex items-center justify-between rounded-xl border border-amber-400/30 bg-amber-500/10 px-3 py-2">
                                <div>
                                    <p class="text-xs font-semibold text-white/90">{{ $req['type'] }}</p>
                                    <p class="text-[10px] text-white/50">{{ $req['range'] }}{{ $req['notes'] ? ' · '.$req['notes'] : '' }}</p>
                                </div>
                                <x-portal.badge color="amber">PENDIENTE</x-portal.badge>
                            </div>
                        @endforeach
                        @foreach($pendingReqs['swaps'] as $req)
                            <div
                                class="flex items-center justify-between rounded-xl border border-amber-400/30 bg-amber-500/10 px-3 py-2">
                                <div>
                                    <p class="text-xs font-semibold text-white/90">{{ $req['type_label'] }}</p>
                                    <p class="text-[10px] text-white/50">{{ $req['date'] }}{{ $req['notes'] ? ' · '.$req['notes'] : '' }}</p>
                                </div>
                                <x-portal.badge color="amber">PENDIENTE</x-portal.badge>
                            </div>
                        @endforeach
                    </div>
                @endif

                <div class="mt-4 space-y-3">
                    <p class="text-white/60 text-[14px] uppercase tracking-widest">Permisos</p>
                    @livewire('employee-profile-tabs', ['embedded' => true, 'context' => 'portal'], key('employee-profile-tabs-'.$unreadNotifications.'-'.count($notifications)))
                </div>
            @else
                @php
                    $trackingTaxis = $this->dashboardTrackingTaxis();
                @endphp

                <div class="mt-0 space-y-3">

        <!-- SEPARADOR DE IDENTIDAD COMPACTO -->
        <div class="tl-s2-hover rounded-2xl border border-white/10 p-4 border-white/10 pt-2 pb-2">
            <div class="text-center space-y-1">
                <h2 class="text-md font-semibold text-white">
                    ¡Hola, {{ $identification['name'] }}!
                </h2>
                @if($isEmployeePortal)
                    <div class="text-xs text-white/60">
                        {{ $identification['department'] ?? 'Empleado' }}
                    </div>
                @else
                    <div class="flex items-center justify-center gap-2 text-xs text-white/60">
                        <span>🚗 {{ $identification['licencia'] }}</span>
                        <span>•</span>
                        <span>📱 {{ $identification['nif'] }}</span>
                    </div>
                @endif
            </div>
        </div>
                            <p class="text-white/60 text-[11px] uppercase tracking-widest">Mis Taxis</p>
                    <div class="space-y-3">
                        @forelse($trackingTaxis as $index => $trackingTaxi)
                            <div class="tl-s2 rounded-2xl border border-white/10 p-4">
                                <!-- Header: Taxi info + Online button -->
                                <div class="flex items-start justify-between gap-3 mb-3">
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-semibold text-white/90">
                                            🚗 {{ $trackingTaxi['label'] }}
                                        </p>
                                        <p class="text-xs text-white/60">
                                            {{ $trackingTaxi['brand'] }} {{ $trackingTaxi['model'] }} ·
                                            · {{ $trackingTaxi['seats'] }} plazas
                                            · {{ $trackingTaxi['accessibility_label'] }}
                                        </p>
                                    </div>

                                    <!-- Online/Offline button -->
                                    <button
                                        type="button"
                                        class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-colors
                                            {{ ($trackingTaxi['tracking_enabled'] ?? false)
                                                ? 'bg-emerald-500/20 border-emerald-500/30 text-emerald-300'
                                                : 'bg-rose-500/20 border-rose-500/30 text-rose-300' }}"
                                        x-on:click="toggleTaxiTrackingAction({{ (int) ($trackingTaxi['id'] ?? 0) }})"
                                        x-bind:disabled="pendingTaxiTrackingId === {{ (int) ($trackingTaxi['id'] ?? 0) }}"
                                    >
                                        <span class="flex items-center gap-1.5">
                                            <span
                                                class="h-2 w-2 rounded-full {{ ($trackingTaxi['tracking_enabled'] ?? false) ? 'bg-emerald-400' : 'bg-rose-400' }}"></span>
                                            <span x-text="pendingTaxiTrackingId === {{ (int) ($trackingTaxi['id'] ?? 0) }} ? 'CAMBIANDO…' : '{{ ($trackingTaxi['tracking_enabled'] ?? false) ? 'ONLINE' : 'OFFLINE' }}'"></span>
                                        </span>
                                    </button>
                                </div>

                                <!-- Status badges -->
                                <div class="flex flex-wrap items-center gap-1.5 mb-3">
                                    <x-portal.badge color="{{ $trackingTaxi['status_color'] ?? 'zinc' }}" size="sm">
                                        {{ $trackingTaxi['status_label'] }}
                                    </x-portal.badge>
                                    <x-portal.badge
                                        color="{{ ($trackingTaxi['traccar_validated'] ?? false) ? 'emerald' : 'amber' }}"
                                        size="sm">
                                        {{ ($trackingTaxi['traccar_validated'] ?? false) ? '✓ Validado' : '⏳ Pendiente' }}
                                    </x-portal.badge>
                                    @if(!empty($trackingTaxi['last_located_at']))
                                        <x-portal.badge color="blue" size="sm">
                                            📍 Ultima ubicación {{ $trackingTaxi['last_located_at'] }}
                                        </x-portal.badge>
                                    @endif
                                </div>

                                <!-- Action buttons -->
                                <div class="flex items-center gap-2">
                                    @if(!empty($trackingTaxi['map_url']))
                                        <x-portal.button variant="ghost" size="sm" as="a"
                                                         class="tl-s2 tl-interactive inline-flex  items-center justify-center rounded-full border border-white/10 bg-white/5 p-2 white/80 tl-interactive"
                                                         href="{{ $trackingTaxi['map_url'] }}">
                                            <svg class="h-4 w-4 mr-1.5" viewBox="0 0 24 24" fill="none"
                                                 stroke="currentColor"
                                                 stroke-width="1.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m9 20.25 6-2.25m0 0 6 2.25V6.75L15 4.5m0 13.5L9 20.25m6-2.25V4.5m-6 15.75L3 18V3.75L9 6m0 14.25V6m0 0 6-1.5"/>
                                            </svg>
                                            Mapa
                                        </x-portal.button>
                                    @endif

                                    @if(!empty($trackingTaxi['edit_url']))
                                        <x-portal.button variant="ghost" size="sm" as="a"
                                                         class="tl-s2 tl-interactive inline-flex  items-center justify-center rounded-full border border-white/10 bg-white/5 p-2 white/80 tl-interactive"
                                                         href="{{ $trackingTaxi['edit_url'] }}">
                                            <svg class="h-4 w-4 mr-1.5" fill="none" stroke-width="1.5"
                                                 stroke="currentColor"
                                                 viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                                                 aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="m19.5 7.125-3.375-3.375"/>
                                            </svg>
                                            Editar
                                        </x-portal.button>
                                    @endif

                                    <x-portal.button variant="ghost" size="sm"
                                                     class="tl-s3 tl-interactive inline-flex  items-center justify-center rounded-full border border-white/10 bg-white/5 p-2 white/80 tl-interactive"
                                                     x-on:click.prevent="shareLocationForTaxi({{ (int) ($trackingTaxi['id'] ?? 0) }})"
                                                     x-bind:disabled="pendingShareLocation"
                                    >
                                        <svg class="h-4 w-4 mr-1.5" fill="none" stroke-width="1.5"
                                             stroke="currentColor"
                                             viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/>
                                        </svg>
                                        <span x-text="pendingShareLocation ? 'Compartiendo…' : 'Compartir'"></span>
                                    </x-portal.button>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-8">
                                <p class="text-white/60 text-sm">No tienes taxis registrados</p>
                                <x-portal.button variant="ghost" size="sm" as="a"
                                                 href="{{ \App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource::getUrl('create', panel: 'portal') }}"
                                                 class="mt-2">
                                    + Añadir taxi
                                </x-portal.button>
                            </div>
                        @endforelse
                    </div>
                </div>
            @endif

        </x-portal.card>

        {{-- PRÓXIMAS CITAS --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-white/60 text-[11px] uppercase tracking-widest">Próximas citas</p>
                <x-portal.button variant="ghost" as="a"
                                 href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'citas'], panel: 'portal') }}">
                    Ver todas
                </x-portal.button>
            </div>

            @php
                $upcomingAppointments = $this->upcomingAppointmentsForTaxista(auth('taxista')->id() ?? auth('web')->id());
            @endphp

            @forelse ($upcomingAppointments as $appointment)
                <a
                    href="{{ $appointment['url'] ?? \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'citas'], panel: 'portal') }}"
                    class="block tl-s2 tl-s2-hover rounded-2xl border border-white/10 px-3 py-2"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white/90">{{ $appointment['title'] }}</p>
                            <p class="text-xs text-white/60">
                                📅 {{ $appointment['date'] }} · 🕐 {{ $appointment['time'] }}
                            </p>
                        </div>
                        <x-portal.badge color="{{ $appointment['status'] === 'confirmada' ? 'emerald' : 'amber' }}"
                                        size="sm">{{ $appointment['status'] }}</x-portal.badge>
                    </div>
                </a>
            @empty
                <div class="text-center py-6">
                    <p class="text-white/60 text-sm">No tienes próximas citas</p>
                    <x-portal.button variant="ghost" size="sm" as="a"
                                     href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'citas'], panel: 'portal') }}"
                                     class="mt-2">
                        + Nueva cita
                    </x-portal.button>
                </div>
            @endforelse
        </div>

        {{-- TURNOS - Solo para empleados --}}
        @if($isEmployeePortal)
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-white/60 text-[11px] uppercase tracking-widest">Mis Turnos</p>
                <x-portal.button variant="ghost" as="a"
                                 href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}">
                    Ver calendario
                </x-portal.button>
            </div>
            
            {{-- Mini calendar preview --}}
            <div class="tl-s1 rounded-2xl border border-white/10 p-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-white/90">{{ \Illuminate\Support\Carbon::now()->translatedFormat('F Y') }}</h3>
                    <div class="flex gap-1">
                        @php
                            $currentMonth = \Illuminate\Support\Carbon::now();
                            $shifts = [];
                            // Get current month shifts for preview (simplified)
                            try {
                                $employeeShifts = \App\Models\EmployeeShift::where('user_id', auth()->id())
                                    ->whereMonth('date', $currentMonth->month)
                                    ->whereYear('date', $currentMonth->year)
                                    ->get();
                                
                                foreach ($employeeShifts as $shift) {
                                    $shifts[$shift->date] = $shift;
                                }
                            } catch (\Exception $e) {
                                // Fallback if query fails
                                $shifts = [];
                            }
                            
                            $daysInMonth = $currentMonth->daysInMonth;
                            $startDay = ($currentMonth->copy()->startOfMonth()->dayOfWeekIso - 1);
                        @endphp
                        
                        {{-- Mini calendar grid --}}
                        <div class="grid grid-cols-7 gap-1 text-xs">
                            {{-- Empty cells for start of month --}}
                            @for ($i = 0; $i < $startDay; $i++)
                                <div></div>
                            @endfor
                            
                            {{-- Days of current month --}}
                            @for ($day = 1; $day <= min($daysInMonth, 14); $day++)
                                @php
                                    $dateStr = $currentMonth->format('Y-m-') . str_pad($day, 2, '0', STR_PAD_LEFT);
                                    $shift = $shifts[$dateStr] ?? null;
                                    $isToday = $dateStr === \Illuminate\Support\Carbon::now()->toDateString();
                                    $color = match($shift->shift_code ?? null) {
                                        'M' => '#3b82f6',
                                        'P' => '#f59e0b', 
                                        'N' => '#8b5cf6',
                                        'L' => '#22c55e',
                                        default => 'transparent'
                                    };
                                @endphp
                                <div class="w-6 h-6 flex items-center justify-center rounded {{ $isToday ? 'ring-1 ring-red-400' : '' }}"
                                     @if($color !== 'transparent') style="background-color: {{ $color }}20;" @endif>
                                    <span class="{{ $shift ? 'text-white font-bold' : 'text-white/50' }}">{{ $day }}</span>
                                </div>
                            @endfor
                        </div>
                    </div>
                </div>
                
                {{-- Quick summary --}}
                <div class="flex gap-2 mt-3">
                    @php
                        $summary = [];
                        try {
                            $summary = [
                                'm' => collect($shifts)->where('shift_code', 'M')->count(),
                                'p' => collect($shifts)->where('shift_code', 'P')->count(),
                                'n' => collect($shifts)->where('shift_code', 'N')->count(),
                                'l' => collect($shifts)->where('shift_code', 'L')->count(),
                            ];
                        } catch (\Exception $e) {
                            $summary = ['m' => 0, 'p' => 0, 'n' => 0, 'l' => 0];
                        }
                    @endphp
                    
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold" style="background-color: #3b82f620; color: #3b82f6;">M: {{ $summary['m'] }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold" style="background-color: #f59e0b20; color: #f59e0b;">P: {{ $summary['p'] }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold" style="background-color: #8b5cf620; color: #8b5cf6;">N: {{ $summary['n'] }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold" style="background-color: #22c55e20; color: #22c55e;">L: {{ $summary['l'] }}</span>
                </div>
                
                <div class="mt-3 pt-3 border-t border-white/10">
                    <x-portal.button variant="primary" size="sm" as="a" href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}" class="w-full justify-center">
                        Ver calendario completo
                    </x-portal.button>
                </div>
            </div>
        </div>
        @endif

        {{-- DOCUMENTOS --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-white/60 text-[11px] uppercase tracking-widest">Últimos documentos</p>
                <x-portal.button variant="ghost" as="a"
                                 href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'documentos'], panel: 'portal') }}">
                    Ver todos
                </x-portal.button>
            </div>

            @php
                $recentDocuments = $this->recentDocuments();
            @endphp

            @forelse ($recentDocuments as $doc)
                <a href="{{ $doc['url'] }}"
                   class="block tl-s2 tl-s2-hover rounded-2xl border border-white/10 px-3 py-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white/90 truncate">{{ $doc['title'] }}</p>
                            <p class="mt-1 text-xs text-white/50">{{ $doc['date'] }}</p>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            <button
                                type="button"
                                class="tl-s3 px-2 py-2"
                                wire:click.stop.prevent="toggleDocumentFavorite({{ (int) $doc['id'] }})"
                                aria-label="Marcar como favorito"
                            >
                                @if (($doc['is_favorite'] ?? false) === true)
                                    <svg class="h-4 w-4 text-amber-300" viewBox="0 0 24 24" fill="currentColor"
                                         aria-hidden="true">
                                        <path fill-rule="evenodd"
                                              d="M10.788 3.21c.448-1.077 1.976-1.077 2.424 0l2.082 5.006 5.404.434c1.164.093 1.636 1.545.749 2.305l-4.117 3.527 1.257 5.273c.271 1.136-.964 2.033-1.96 1.425L12 18.354l-4.627 2.826c-.996.608-2.231-.29-1.96-1.425l1.257-5.273-4.117-3.527c-.887-.76-.415-2.212.749-2.305l5.404-.434 2.082-5.005Z"
                                              clip-rule="evenodd"/>
                                    </svg>
                                @else
                                    <svg class="h-4 w-4 text-white/50" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="m11.48 3.499 2.275 4.611 5.089.74-3.682 3.588.869 5.068L12 15.113l-4.551 2.393.869-5.068L4.636 8.85l5.089-.74 2.275-4.611Z"/>
                                    </svg>
                                @endif
                            </button>
                            <x-portal.badge color="amber">PDF</x-portal.badge>
                        </div>
                    </div>
                </a>
            @empty
                <x-portal.card padding="p-6">
                    <p class="text-white/60">Aún no hay documentos.</p>
                </x-portal.card>
            @endforelse
        </div>

        {{-- TICKETS ABIERTOS --}}
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <p class="text-white/60 text-[11px] uppercase tracking-widest">Tickets abiertos</p>
                <x-portal.button variant="ghost" as="a"
                                 href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'tickets'], panel: 'portal') }}">
                    Ver todos
                </x-portal.button>
            </div>

            @php
                $recentTickets = $this->recentTickets();
            @endphp

            @forelse ($recentTickets as $ticket)
                <a href="{{ $ticket['url'] }}"
                   class="block tl-s2 tl-s2-hover rounded-2xl border border-white/10 px-3 py-2">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white/90 truncate">{{ $ticket['title'] }}</p>
                            <p class="mt-1 text-xs text-white/50">{{ $ticket['subtitle'] }}</p>
                        </div>

                        <div class="shrink-0 flex items-center gap-2">
                            <x-portal.badge :color="$ticket['badge_color'] ?? 'zinc'">
                                {{ $ticket['status_label'] ?? $ticket['status'] }}
                            </x-portal.badge>
                        </div>
                    </div>
                </a>
            @empty
                <x-portal.card padding="p-6">
                    <p class="text-white/60">Sin tickets abiertos.</p>
                </x-portal.card>
            @endforelse
        </div>

        {{-- TAXIS --}}
        @unless($isEmployeePortal)
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <p class="text-white/60 text-[11px] uppercase tracking-widest">Mis taxis</p>
                    <x-portal.button variant="ghost" as="a"
                                     href="{{ \App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource::getUrl('index', panel: 'portal') }}">
                        Ver todos
                    </x-portal.button>
                </div>

                @php
                    $recentTaxis = $this->recentTaxis();
                @endphp

                @forelse ($recentTaxis as $taxi)
                    <div class="tl-s2 tl-s2-hover rounded-2xl border border-white/10 px-3 py-2">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-white/90">{{ $taxi['plate'] }}</p>
                                <p class="mt-1 text-xs text-white/55">{{ $taxi['brand'] }} · {{ $taxi['model'] }}</p>
                                <p class="mt-1 text-xs text-white/50">{{ $taxi['municipality'] }} ·
                                    Plazas: {{ $taxi['seats'] }} · {{ $taxi['accessibility_label'] }}</p>
                                <p class="mt-1 text-xs text-white/50 font-mono truncate">
                                    UUID: {{ $taxi['tracking_uuid'] ?? 'Sin UUID' }}</p>
                                <p class="mt-1 text-xs text-white/45">Última
                                    ubicación: {{ $taxi['last_located_at'] ?? 'Sin ubicacion' }}</p>

                                @if (! empty($taxi['next_appointments'] ?? []))
                                    <div class="mt-2 space-y-1">
                                        <p class="text-[11px] uppercase tracking-widest text-white/35">Próximas
                                            citas</p>
                                        @foreach (($taxi['next_appointments'] ?? []) as $appointment)
                                            <p class="text-xs text-white/55 truncate">
                                                {{ $appointment['date'] ?? '-' }} · {{ $appointment['time'] ?? '-' }}
                                                <span class="text-white/35">·</span>
                                                {{ $appointment['title'] ?? 'Cita' }}
                                            </p>
                                        @endforeach
                                    </div>
                                @endif
                            </div>

                            <div class="shrink-0 flex flex-col items-end gap-2">
                                <x-portal.badge :color="$taxi['badge_color'] ?? 'zinc'">
                                    {{ $taxi['status_label'] ?? $taxi['status'] }}
                                </x-portal.badge>
                                <x-portal.badge :color="$taxi['tracking_badge_color'] ?? 'gray'">
                                    {{ $taxi['tracking_state_label'] ?? 'Sin código' }}
                                </x-portal.badge>
                            </div>
                        </div>

                        <div class="mt-2 flex items-center gap-2">
                            <x-portal.button variant="ghost" as="a" href="{{ $taxi['url'] }}" target="_blank"
                                             rel="noopener noreferrer">
                                Abrir ficha
                            </x-portal.button>
                            <x-portal.button variant="ghost" as="a" href="{{ $taxi['map_url'] ?? '#' }}" target="_blank"
                                             rel="noopener noreferrer">
                                Ver en mapa
                            </x-portal.button>
                        </div>
                    </div>
                @empty
                    <x-portal.card padding="p-6">
                        <p class="text-white/60">Sin taxis asignados.</p>
                    </x-portal.card>
                @endforelse
            </div>
        @endunless
    </div>

    @if(! $embedded)
        <div class="fixed bottom-0 left-0 right-0 z-40 px-4 pb-4">
            <div class="tl-s3 p-2">
                <form x-ref="logoutForm" method="POST" action="{{ route('taxista.logout') }}" class="hidden">
                    @csrf
                </form>

                <div class="grid grid-cols-4">
                    <a href="{{ route('mobile-portal') }}" class="tl-s2-hover rounded-xl px-2 py-2 text-center">
                        <svg class="mx-auto h-5 w-5 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="m2.25 12 8.954-8.955a1.125 1.125 0 0 1 1.592 0L21.75 12M4.5 9.75V19.5A1.5 1.5 0 0 0 6 21h12a1.5 1.5 0 0 0 1.5-1.5V9.75"/>
                        </svg>
                        <span class="mt-1 block text-[11px] text-white/50">Inicio</span>
                    </a>

                    <a href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'documentos'], panel: 'portal') }}"
                       class="tl-s2-hover rounded-xl px-2 py-2 text-center">
                        <svg class="mx-auto h-5 w-5 text-amber-200" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 6.75A2.25 2.25 0 0 1 4.5 4.5h4.379a2.25 2.25 0 0 1 1.591.659l1.622 1.622a2.25 2.25 0 0 0 1.591.659H19.5a2.25 2.25 0 0 1 2.25 2.25v7.5a2.25 2.25 0 0 1-2.25 2.25H4.5a2.25 2.25 0 0 1-2.25-2.25v-10.5Z"/>
                        </svg>
                        <span class="mt-1 block text-[11px] text-white/50">Docs</span>
                    </a>

                    <a href="{{ \App\Filament\Portal\Pages\TaxistaChats::getUrl(panel: 'portal') }}"
                       class="tl-s2-hover rounded-xl px-2 py-2 text-center">
                        <svg class="mx-auto h-5 w-5 text-emerald-200" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M2.25 12c0-4.556 3.694-8.25 8.25-8.25h3A8.25 8.25 0 0 1 21.75 12v.75a3 3 0 0 1-3 3h-1.386a2.25 2.25 0 0 0-1.591.659l-1.82 1.82a1.5 1.5 0 0 1-2.56-1.06V15.75h-.893A8.25 8.25 0 0 1 2.25 12Z"/>
                        </svg>
                        <span class="mt-1 block text-[11px] text-white/50">Chat</span>
                    </a>

                    <button type="button" class="tl-s2-hover rounded-xl px-2 py-2 text-center"
                            x-on:click.prevent="$refs.logoutForm.submit()">
                        <svg class="mx-auto h-5 w-5 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6A2.25 2.25 0 0 0 5.25 5.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/>
                        </svg>
                        <span class="mt-1 block text-[11px] text-white/50">Salir</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    @include('livewire.mobile-portal._onboarding')

    @vite(['resources/js/support-screenshot.js'])
</div>
