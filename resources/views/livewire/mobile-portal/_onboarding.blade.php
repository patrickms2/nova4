{{-- Onboarding overlay for Employee/Taxista mobile portal users --}}
@php
    $isEmployee = $this->isEmployeePortal();
@endphp
<div
    x-data="{
        show: false,
        showAnnouncements: @entangle('showAnnouncements'),
        step: 0,
        isEmployee: @js($isEmployee),
        get steps() {
            if (this.isEmployee) {
                return [
                    {
                        title: 'Tus Turnos',
                        description: 'Consulta tu calendario de turnos asignados: Manana (M), Partido (P) y Noche (N). Puedes ver los proximos 7 dias y el resumen mensual desde tu ficha.',
                        action: 'Pulsa en Mis turnos o en la pestana Turnos de tu ficha.',
                        color: 'blue',
                    },
                    {
                        title: 'Vacaciones y Permisos',
                        description: 'Solicita vacaciones y dias libres. Puedes ver el estado de cada solicitud (pendiente, aprobada, denegada) y cuantos dias tienes aprobados.',
                        action: 'Pulsa en la pestana Vacaciones de tu ficha.',
                        color: 'amber',
                    },
                    {
                        title: 'Tus Documentos',
                        description: 'Gestiona tus documentos laborales: contrato, nominas, certificados y mas. Puedes consultar y descargar en cualquier momento.',
                        action: 'Pulsa en Docs o en la pestana Documentos.',
                        color: 'purple',
                    },
                    {
                        title: 'Tus Citas',
                        description: 'Solicita y gestiona citas con los departamentos de la empresa. Puedes ver el estado, confirmar o cancelar directamente.',
                        action: 'Pulsa en la tarjeta Citas para ver tu agenda.',
                        color: 'sky',
                    },
                    {
                        title: 'Tus Tickets',
                        description: 'Abre tickets para reportar incidencias o solicitar ayuda al equipo. Haz seguimiento del estado y recibe respuestas.',
                        action: 'Pulsa en la tarjeta Tickets para abrir uno nuevo.',
                        color: 'rose',
                    },
                ];
            }
            return [
                {
                    title: 'Tus Taxis y Seguimiento',
                    description: 'Gestiona tus vehiculos asignados. Activa el seguimiento GPS para que la central pueda localizar tu taxi en tiempo real via Traccar. Solo tienes que pulsar el boton de estado Online.',
                    action: 'Activa Online para compartir tu ubicacion con la central.',
                    color: 'cyan',
                },
                {
                    title: 'Tus Documentos',
                    description: 'Aqui gestionas todos tus documentos: licencia, seguro, ITV y mas. Puedes subir nuevos, marcar favoritos y consultarlos en cualquier momento.',
                    action: 'Pulsa en Docs para empezar.',
                    color: 'amber',
                },
                {
                    title: 'Tus Citas',
                    description: 'Solicita y gestiona citas con los departamentos. Puedes ver el estado de cada cita, confirmarla o cancelarla directamente.',
                    action: 'Pulsa en la tarjeta Citas para ver tu agenda.',
                    color: 'sky',
                },
                {
                    title: 'Tus Tickets',
                    description: 'Abre tickets para reportar incidencias o solicitar ayuda. Haz seguimiento del estado y recibe respuestas del equipo.',
                    action: 'Pulsa en la tarjeta Tickets para abrir uno nuevo.',
                    color: 'rose',
                },
            ];
        },
        get totalSteps() { return this.steps.length },
        get c() { return this.steps[this.step]?.color || 'cyan' },
        init() {
            const key = this.isEmployee ? 'portal-employee-onboarding-done' : 'portal-taxista-mobile-onboarding-done';
            try {
                const localDone = window.localStorage.getItem(key) === '1';
                const sessionDone = window.sessionStorage.getItem(key) === '1';
                const cookieDone = document.cookie.split('; ').includes(`${key}=1`);

                if (!localDone && !sessionDone && !cookieDone) {
                    this.show = true;
                }
            } catch (e) {}
        },
        next() {
            if (this.step < this.totalSteps - 1) { this.step++; } else { this.finish(); }
        },
        prev() {
            if (this.step > 0) { this.step--; }
        },
        finish() {
            this.show = false;
            const key = this.isEmployee ? 'portal-employee-onboarding-done' : 'portal-taxista-mobile-onboarding-done';
            try { window.localStorage.setItem(key, '1'); } catch (e) {}
            try { window.sessionStorage.setItem(key, '1'); } catch (e) {}
            document.cookie = `${key}=1; path=/; max-age=31536000; SameSite=Lax`;
        },
        skip() { this.finish(); },
    }"
    x-show="show && !showAnnouncements"
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
        {{-- Progress bar --}}
        <div class="h-1 bg-white/5">
            <div
                class="h-full transition-all duration-500 ease-out rounded-r-full"
                :class="{
                    'bg-cyan-500': c === 'cyan',
                    'bg-blue-500': c === 'blue',
                    'bg-amber-500': c === 'amber',
                    'bg-purple-500': c === 'purple',
                    'bg-sky-500': c === 'sky',
                    'bg-rose-500': c === 'rose',
                    'bg-emerald-500': c === 'emerald',
                }"
                :style="'width: ' + ((step + 1) / totalSteps * 100) + '%'"
            ></div>
        </div>

        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between mb-6">
                <span class="text-white/40 text-xs font-medium tracking-wider uppercase"
                      x-text="isEmployee ? 'Bienvenido al Portal Empleado' : 'Bienvenido al Portal Taxista'"></span>
                <span class="text-white/30 text-xs" x-text="(step + 1) + ' / ' + totalSteps"></span>
            </div>

            {{-- Icon --}}
            <div class="mb-5 flex justify-center">
                <div
                    class="flex h-16 w-16 items-center justify-center rounded-2xl transition-colors duration-300"
                    :class="{
                        'bg-cyan-500/15 ring-1 ring-cyan-500/30': c === 'cyan',
                        'bg-blue-500/15 ring-1 ring-blue-500/30': c === 'blue',
                        'bg-amber-500/15 ring-1 ring-amber-500/30': c === 'amber',
                        'bg-purple-500/15 ring-1 ring-purple-500/30': c === 'purple',
                        'bg-sky-500/15 ring-1 ring-sky-500/30': c === 'sky',
                        'bg-rose-500/15 ring-1 ring-rose-500/30': c === 'rose',
                        'bg-emerald-500/15 ring-1 ring-emerald-500/30': c === 'emerald',
                    }"
                >
                    {{-- Taxi / Tracking (cyan) --}}
                    <template x-if="c === 'cyan'">
                        <svg class="h-8 w-8 text-cyan-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h7.5m0 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0H21m-2.25-4.5V9.75a2.25 2.25 0 0 0-2.25-2.25H5.25A2.25 2.25 0 0 0 3 9.75v4.5m15.75 0H3m0 0v2.25A2.25 2.25 0 0 0 5.25 18.75H3"/></svg>
                    </template>
                    {{-- Turnos (blue) --}}
                    <template x-if="c === 'blue'">
                        <svg class="h-8 w-8 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </template>
                    {{-- Documents (amber) --}}
                    <template x-if="c === 'amber'">
                        <svg class="h-8 w-8 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z"/></svg>
                    </template>
                    {{-- Vacaciones (purple) --}}
                    <template x-if="c === 'purple'">
                        <svg class="h-8 w-8 text-purple-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/></svg>
                    </template>
                    {{-- Calendar / Citas (sky) --}}
                    <template x-if="c === 'sky'">
                        <svg class="h-8 w-8 text-sky-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/></svg>
                    </template>
                    {{-- Tickets (rose) --}}
                    <template x-if="c === 'rose'">
                        <svg class="h-8 w-8 text-rose-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z"/></svg>
                    </template>
                    {{-- Gastos (emerald) --}}
                    <template x-if="c === 'emerald'">
                        <svg class="h-8 w-8 text-emerald-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z"/></svg>
                    </template>
                </div>
            </div>

            {{-- Content --}}
            <div class="text-center space-y-3">
                <h3 class="text-xl font-bold text-white" x-text="steps[step].title"></h3>
                <p class="text-white/60 text-sm leading-relaxed" x-text="steps[step].description"></p>
                <p class="text-sm font-medium" :class="{
                    'text-cyan-400': c === 'cyan',
                    'text-blue-400': c === 'blue',
                    'text-amber-400': c === 'amber',
                    'text-purple-400': c === 'purple',
                    'text-sky-400': c === 'sky',
                    'text-rose-400': c === 'rose',
                    'text-emerald-400': c === 'emerald',
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
                            'bg-cyan-600 hover:bg-cyan-500': c === 'cyan',
                            'bg-blue-600 hover:bg-blue-500': c === 'blue',
                            'bg-amber-600 hover:bg-amber-500': c === 'amber',
                            'bg-purple-600 hover:bg-purple-500': c === 'purple',
                            'bg-sky-600 hover:bg-sky-500': c === 'sky',
                            'bg-rose-600 hover:bg-rose-500': c === 'rose',
                            'bg-emerald-600 hover:bg-emerald-500': c === 'emerald',
                        }"
                        @click="next()"
                        x-text="step < totalSteps - 1 ? 'Siguiente' : 'Empezar'"
                    ></button>
                </div>
            </div>
        </div>
    </div>
</div>
