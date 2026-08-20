<div
    class="portal-taxista-pro"
    x-data="{
        activeTab: @entangle('activeTab'),
        showSpotlight: @entangle('showSpotlight'),
        showAnnouncements: @entangle('showAnnouncements'),
        showDocsControls: false,
        showDocsHomeControls: false,
        showDocActions: false,
        docActionItem: null,
        showCitaInfo: false,
        citaInfoItem: null,
        showCitaStatusActions: false,
        citaStatusActionItem: null,
        citaAskCancelReason: false,
        citaCancelMotivo: '',
        showTicketInfo: false,
        ticketInfoItem: null,
        showCitasTopFilters: false,
        showDocumentosTopFilters: false,
        showTicketsTopFilters: false,
        docsFolder: @entangle('docsFolder'),
        selectedDocumentId: @entangle('selectedDocumentId'),
        documentMode: @entangle('documentMode'),
        pendingAction: null,
        docsViewState: @js($docsView ?? 'home'),
        docsSegmentState: @js($docsSegment ?? 'all'),
        docsOrderState: @js($docsOrder ?? 'recent'),
        favoriteDocuments: {},
        pendingOnline: false,
        pendingShareLocation: false,
        isOnline: @entangle('isOnline'),
        trackingActive: @entangle('trackingActive'),
        taxistaUserId: @js((int) (auth('taxista')->id() ?? auth('web')->id() ?? 0)),
        _watchId: null,
        _lastSentAt: 0,
        _trackingInterval: 60000, // REDUCIDO: 60s en lugar de 20s
        _debounceTimer: null,
        hasOpenedAnnouncementsOnboarding: false,

        init() {
            const readPersistedToggle = (key) => {
                try {
                    return window.localStorage.getItem(key) === '1'
                } catch (e) {
                    return false
                }
            }

            const hasUnreadAnnouncements = @js((int) ($stats['anuncios'] ?? 0)) > 0;

            if (hasUnreadAnnouncements && !this.hasOpenedAnnouncementsOnboarding) {
                this.activeTab = 'anuncios';
                this.hasOpenedAnnouncementsOnboarding = true;
            }

            const persistToggle = (key, value) => {
                try {
                    window.localStorage.setItem(key, value ? '1' : '0')
                } catch (e) {
                    // Silenciar errores de localStorage
                }
            }

            this.showCitasTopFilters = readPersistedToggle('portal-taxista-pro.filters.citas')
            this.showDocumentosTopFilters = readPersistedToggle('portal-taxista-pro.filters.documentos')
            this.showTicketsTopFilters = readPersistedToggle('portal-taxista-pro.filters.tickets')

            // DEBOUNCE: Optimizado con debounce de 300ms
            this.$watch('showCitasTopFilters', (value) => {
                this.debouncePersist('portal-taxista-pro.filters.citas', value)
            })
            this.$watch('showDocumentosTopFilters', (value) => {
                this.debouncePersist('portal-taxista-pro.filters.documentos', value)
            })
            this.$watch('showTicketsTopFilters', (value) => {
                this.debouncePersist('portal-taxista-pro.filters.tickets', value)
            })

            // LAZY LOADING: Solo iniciar tracking si está online
            this.$watch('isOnline', (val) => {
                if (val) {
                    this.$nextTick(() => this.startAutoTracking())
                } else {
                    this.stopAutoTracking()
                }
            })

            if (this.isOnline) {
                this.$nextTick(() => this.startAutoTracking())
            }
        },

        async runPendingAction(key, callback) {
            if (this.pendingAction !== null) {
                return
            }

            this.pendingAction = key

            try {
                await callback()
            } finally {
                window.setTimeout(() => {
                    if (this.pendingAction === key) {
                        this.pendingAction = null
                    }
                }, 180)
            }
        },

        async openSpotlightAction() {
            await $wire.openSpotlight()
        },

        async switchTabAction(tab) {
            this.activeTab = tab

            if (tab !== 'documentos') {
                this.docsViewState = 'home'
                this.docsSegmentState = 'all'
            }

            await this.runPendingAction(`tab:${tab}`, async () => {
                await $wire.switchTab(tab)
            })
        },

        async openAnnouncementsAction() {
            this.showAnnouncements = true

            await this.runPendingAction('announcements', async () => {
                await $wire.openAnnouncements()
            })
        },

        async runCreateAction(actionName) {
            await this.runPendingAction(`create:${actionName}`, async () => {
                await $wire.mountAction(actionName)
            })
        },

        async setDocsViewAction(view) {
            this.docsViewState = view
            await $wire.setDocsView(view)
        },

        async setDocsSegmentAction(segment) {
            this.docsSegmentState = segment
            await $wire.setDocsSegment(segment)
        },

        async setDocsOrderAction(order) {
            this.docsOrderState = order
            await $wire.setDocsOrder(order)
        },

        isDocumentoFavorito(documentId, currentValue = false) {
            return this.favoriteDocuments[documentId] ?? currentValue
        },

        async toggleDocumentoFavoritoAction(documentId, currentValue = false) {
            const nextValue = !this.isDocumentoFavorito(documentId, currentValue)

            this.favoriteDocuments[documentId] = nextValue

            try {
                await $wire.toggleDocumentoFavorito(documentId)
            } catch (error) {
                this.favoriteDocuments[documentId] = currentValue
            }
        },

        async toggleOnlineAction() {
            if (this.pendingOnline) {
                return
            }

            this.pendingOnline = true

            try {
                await $wire.toggleOnline()
            } finally {
                window.setTimeout(() => {
                    this.pendingOnline = false
                }, 180)
            }
        },

        // DEBOUNCE: Función optimizada para persistencia
        debouncePersist(key, value) {
            clearTimeout(this._debounceTimer)
            this._debounceTimer = setTimeout(() => {
                persistToggle(key, value)
            }, 300)
        },

        shareLocation() {
            if (this.pendingShareLocation) {
                return
            }

            this.pendingShareLocation = true

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

        startAutoTracking() {
            if (this._watchId !== null || !navigator.geolocation) return

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
                { enableHighAccuracy: false, timeout: 15000, maximumAge: 30000 } // MENOS PRECISO para ahorrar batería
            )

            this.trackingActive = true
            $wire.startTracking()
        },

        stopAutoTracking() {
            if (this._watchId !== null) {
                navigator.geolocation.clearWatch(this._watchId)
                this._watchId = null
            }
            this.trackingActive = false
            $wire.stopTracking()
        },

        // CLEANUP: Limpiar event listeners cuando se destruye el componente
        destroy() {
            this.stopAutoTracking()
            clearTimeout(this._debounceTimer)
        }
    }"
    @keydown.meta.k.window.prevent="openSpotlightAction()"
    @keydown.ctrl.k.window.prevent="openSpotlightAction()"
    @keydown.escape.window="$wire.closeSpotlight()"
    @open-spotlight.window="openSpotlightAction()"
    @taxista-presence-updated.window="if (($event.detail?.taxistaUserId ?? null) === taxistaUserId) { isOnline = !!($event.detail?.isOnline ?? false) }"
    x-on:livewire:destroy.window="destroy()"
>

    <!-- CACHE: Stats calculados una sola vez -->


    <div class="mx-auto max-w-5xl px-4 sm:px-6 pt-20 pb-4 space-y-1 md:space-y-2">

        @include('livewire.portal-taxista-pro._docs-sheets')

        <!-- LAZY LOADING: Solo renderizar el tab activo -->
        @if (($activeTab ?? 'dashboard') === 'documentos')
            @include('livewire.portal-taxista-pro._tab-documentos')
        @elseif (($activeTab ?? 'dashboard') === 'citas')
            @include('livewire.portal-taxista-pro._tab-citas')
        @elseif (($activeTab ?? 'dashboard') === 'tickets')
            @include('livewire.portal-taxista-pro._tab-tickets')
        @elseif (($activeTab ?? 'dashboard') === 'anuncios')
            @include('livewire.portal-taxista-pro._tab-anuncios')
        @else
            @include('livewire.portal-taxista-pro._tab-dashboard')
        @endif

    </div>

    @include('livewire.portal-taxista-pro._spotlight')
    @include('livewire.portal-taxista-pro._onboarding')

    <x-filament-actions::modals/>
</div>
