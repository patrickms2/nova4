{{-- Onboarding overlay for Taxista portal users --}}
<div
    x-data="{
        show: false,
        step: 0,
        forceOpen: @js(request()->boolean('onboarding')),
        colors: ['cyan', 'amber', 'sky', 'rose', 'emerald'],
        steps: [
            {
                title: 'Tus Taxis y Seguimiento',
                description: 'Gestiona tus vehiculos asignados. Activa el seguimiento GPS para que la central pueda localizar tu taxi en tiempo real via Traccar. Solo tienes que pulsar el boton de estado Online.',
                action: 'Activa Online en el dashboard para compartir tu ubicacion.',
            },
            {
                title: 'Tus Documentos',
                description: 'Aqui gestionas todos tus documentos: licencia, seguro, ITV y mas. Puedes subir nuevos documentos, marcar favoritos y consultarlos en cualquier momento.',
                action: 'Pulsa en la tarjeta Documentos para empezar.',
            },
            {
                title: 'Tus Citas',
                description: 'Solicita y gestiona citas con los departamentos. Puedes ver el estado de cada cita, confirmarla o cancelarla directamente desde aqui.',
                action: 'Pulsa en la tarjeta Citas para ver tu agenda.',
            },
            {
                title: 'Tus Tickets',
                description: 'Abre tickets para reportar incidencias o solicitar ayuda. Puedes hacer seguimiento del estado y recibir respuestas del equipo.',
                action: 'Pulsa en la tarjeta Tickets para abrir uno nuevo.',
            },
            {
                title: 'Tus Gastos',
                description: 'Registra y consulta tus gastos de servicio: combustible, peajes, reparaciones y mas. Todo queda registrado para tu control y el de la central.',
                action: 'Pulsa en la tarjeta Gastos para registrar uno.',
            },
        ],
        get totalSteps() { return this.steps.length },
        get c() { return this.colors[this.step] || 'cyan' },
        persistOnboardingDone() {
            try {
                window.localStorage.setItem('portal-taxista-onboarding-done', '1')
            } catch (e) {}

            try {
                window.sessionStorage.setItem('portal-taxista-onboarding-done', '1')
            } catch (e) {}

            document.cookie = 'portal_taxista_onboarding_done=1; path=/; max-age=31536000; SameSite=Lax'
        },
        init() {
            this.show = this.forceOpen
        },
        next() {
            if (this.step < this.totalSteps - 1) { this.step++; } else { this.finish(); }
        },
        prev() {
            if (this.step > 0) { this.step--; }
        },
        finish() {
            this.show = false;
            this.persistOnboardingDone()
        },
        skip() { this.finish(); },
    }"
    x-show="show"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-cloak
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 backdrop-blur-sm p-4"
    @keydown.escape.window="if (show) skip()"
>
    <div
        class="relative w-full max-w-md overflow-hidden rounded-2xl border border-white/10 bg-gray-900 shadow-2xl"
        x-transition:enter="transition ease-out duration-300 delay-100"
        x-transition:enter-start="opacity-0 scale-95 translate-y-4"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        @click.outside="skip()"
    >
        <button
            type="button"
            class="absolute right-4 top-4 z-10 rounded-full p-2 text-white/40 transition hover:bg-white/5 hover:text-white/70"
            @click="skip()"
            aria-label="Cerrar onboarding"
        >
            <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>

        {{-- Progress bar --}}
        <div class="h-1 bg-white/5">
            <div
                class="h-full transition-all duration-500 ease-out rounded-r-full"
                :class="{
                    'bg-cyan-500': step === 0,
                    'bg-amber-500': step === 1,
                    'bg-sky-500': step === 2,
                    'bg-rose-500': step === 3,
                    'bg-emerald-500': step === 4,
                }"
                :style="'width: ' + ((step + 1) / totalSteps * 100) + '%'"
            ></div>
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <span class="text-white/40 text-xs font-medium tracking-wider uppercase">Bienvenido al Portal Taxista</span>
                <span class="text-white/30 text-xs" x-text="(step + 1) + ' / ' + totalSteps"></span>
            </div>

            {{-- Icon --}}
            <div class="mb-5 flex justify-center">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl transition-colors duration-300"
                    :class="{
                        'bg-cyan-500/15 ring-1 ring-cyan-500/30': step === 0,
                        'bg-amber-500/15 ring-1 ring-amber-500/30': step === 1,
                        'bg-sky-500/15 ring-1 ring-sky-500/30': step === 2,
                        'bg-rose-500/15 ring-1 ring-rose-500/30': step === 3,
                        'bg-emerald-500/15 ring-1 ring-emerald-500/30': step === 4,
                    }"
                >
                    {{-- Taxi / Tracking --}}
                    <template x-if="step === 0">
                        <svg class="h-8 w-8 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h7.5m0 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H21m-2.25-4.5V9.75a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9.75v4.5m15.75 0H3m0 0v2.25A2.25 2.25 0 0 0 5.25 18.75H3"/></svg>
                    </template>
                    {{-- Documents --}}
                    <template x-if="step === 1">
                        <svg class="h-8 w-8 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    </template>
                    {{-- Calendar / Citas --}}
                    <template x-if="step === 2">
                        <svg class="h-8 w-8 text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </template>
                    {{-- Tickets --}}
                    <template x-if="step === 3">
                        <svg class="h-8 w-8 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                    </template>
                    {{-- Gastos --}}
                    <template x-if="step === 4">
                        <svg class="h-8 w-8 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                    </template>
                </div>
            </div>

            {{-- Content --}}
            <div class="text-center space-y-3">
                <h3 class="text-xl font-bold text-white" x-text="steps[step].title"></h3>
                <p class="text-white/60 text-sm leading-relaxed" x-text="steps[step].description"></p>
                <p class="text-sm font-medium" :class="{
                    'text-cyan-400': step === 0,
                    'text-amber-400': step === 1,
                    'text-sky-400': step === 2,
                    'text-rose-400': step === 3,
                    'text-emerald-400': step === 4,
                }" x-text="steps[step].action"></p>
            </div>

            {{-- Step dots --}}
            <div class="mt-6 flex justify-center gap-2">
                <template x-for="(s, i) in steps" :key="i">
                    <button
                        type="button"
                        class="h-2 rounded-full transition-all duration-300"
                        :class="i === step ? 'w-6 bg-white/80' : 'w-2 bg-white/20 hover:bg-white/30'"
                        @click="step = i"
                    ></button>
                </template>
            </div>

            {{-- Actions --}}
            <div class="mt-8 flex items-center justify-between">
                <button
                    type="button"
                    class="text-white/40 text-sm hover:text-white/60 transition-colors"
                    @click="skip()"
                >
                    Saltar
                </button>

                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-white/10 px-4 py-2 text-sm text-white/60 hover:text-white/80 hover:border-white/20 transition-colors"
                        x-show="step > 0"
                        @click="prev()"
                    >
                        Atras
                    </button>

                    <button
                        type="button"
                        class="rounded-lg px-5 py-2 text-sm font-semibold text-white transition-colors"
                        :class="{
                            'bg-cyan-600 hover:bg-cyan-500': step === 0,
                            'bg-amber-600 hover:bg-amber-500': step === 1,
                            'bg-sky-600 hover:bg-sky-500': step === 2,
                            'bg-rose-600 hover:bg-rose-500': step === 3,
                            'bg-emerald-600 hover:bg-emerald-500': step === 4,
                        }"
                        @click="next()"
                        x-text="step < totalSteps - 1 ? 'Siguiente' : 'Empezar'"
                    ></button>
                </div>
            </div>
        </div>
    </div>
</div>
