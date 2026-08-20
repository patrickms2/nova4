<div
    x-data="{
        show: false,
        step: 0,
        role: @js($portalType),
        forceOpen: @js(request()->boolean('onboarding')),
        get storageKey() { return `nova-community-${this.role}-onboarding-v1` },
        get steps() {
            const common = [
                { title: 'Documentos', description: 'Sube y consulta documentación vinculada a tu comunidad. Puedes adjuntar PDF o imágenes y seguir su estado.', action: 'Entra en Documentos y pulsa Añadir.', color: 'amber' },
                { title: 'Citas', description: 'Solicita una cita con la comunidad, consulta cuándo tendrá lugar y sigue su estado desde el mismo listado.', action: 'Entra en Citas y pulsa Añadir.', color: 'sky' },
                { title: 'Tickets e incidencias', description: 'Envía solicitudes generales mediante tickets y comunica incidencias con descripción y fotografía para facilitar su resolución.', action: 'Usa Tickets o Incidencias según el tipo de solicitud.', color: 'rose' },
            ];

            return this.role === 'employee'
                ? [{ title: 'Tu trabajo diario', description: 'Consulta planes, órdenes activas, turnos y asistencia desde un único portal operativo.', action: 'Empieza revisando Órdenes y tu próximo Turno.', color: 'cyan' }, ...common]
                : [{ title: 'Tus propiedades y cuotas', description: 'Consulta tus propiedades, comunidades relacionadas y cuotas pendientes sin salir del portal.', action: 'Empieza en Propiedades para revisar tu información.', color: 'emerald' }, ...common];
        },
        get totalSteps() { return this.steps.length },
        get current() { return this.steps[this.step] },
        init() {
            let completed = false;
            try { completed = window.localStorage.getItem(this.storageKey) === '1'; } catch (error) {}
            this.show = this.forceOpen || ! completed;
        },
        next() { this.step < this.totalSteps - 1 ? this.step++ : this.finish() },
        previous() { if (this.step > 0) this.step-- },
        finish() {
            this.show = false;
            try { window.localStorage.setItem(this.storageKey, '1'); } catch (error) {}
            document.cookie = `${this.storageKey}=1; path=/; max-age=31536000; SameSite=Lax`;
        },
    }"
    x-show="show"
    x-cloak
    x-transition.opacity
    x-on:keydown.escape.window="if (show) finish()"
    class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/80 p-4 backdrop-blur-md"
    role="dialog"
    aria-modal="true"
    aria-label="Bienvenida a NOVA Community"
>
    <div x-show="show" x-transition class="community-glass relative w-full max-w-md overflow-hidden rounded-3xl bg-[#11151a] shadow-2xl">
        <div class="h-1 bg-white/5">
            <div class="h-full rounded-r-full bg-red-500 transition-all duration-500" x-bind:style="`width: ${((step + 1) / totalSteps) * 100}%`"></div>
        </div>

        <button type="button" x-on:click="finish()" class="community-icon-button absolute right-4 top-4" aria-label="Cerrar onboarding"><x-heroicon-o-x-mark class="h-5 w-5" /></button>

        <div class="p-6 sm:p-8">
            <div class="flex items-center justify-between gap-4 pr-12">
                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-white/40" x-text="role === 'employee' ? 'Bienvenido, empleado' : 'Bienvenido, propietario'"></p>
                <p class="text-xs text-white/30" x-text="`${step + 1} / ${totalSteps}`"></p>
            </div>

            <div class="mt-7 flex justify-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-2xl border border-red-400/25 bg-red-500/10 text-red-300 shadow-lg shadow-red-950/20">
                    <x-heroicon-o-squares-2x2 class="h-8 w-8" />
                </div>
            </div>

            <div class="mt-6 space-y-3 text-center">
                <h2 class="text-xl font-bold" x-text="current.title"></h2>
                <p class="text-sm leading-relaxed text-white/55" x-text="current.description"></p>
                <p class="text-sm font-medium text-red-300" x-text="current.action"></p>
            </div>

            <div class="mt-6 flex justify-center gap-2">
                <template x-for="(_, index) in steps" x-bind:key="index">
                    <button type="button" x-on:click="step = index" class="h-2 rounded-full transition-all" x-bind:class="index === step ? 'w-6 bg-white/80' : 'w-2 bg-white/20 hover:bg-white/35'" x-bind:aria-label="`Ir al paso ${index + 1}`"></button>
                </template>
            </div>

            <div class="mt-8 flex items-center justify-between gap-3">
                <button type="button" x-on:click="finish()" class="text-sm text-white/40 transition hover:text-white/70">Saltar</button>
                <div class="flex gap-2">
                    <button type="button" x-show="step > 0" x-on:click="previous()" class="community-button community-button-muted">Atrás</button>
                    <button type="button" x-on:click="next()" class="community-button community-button-primary" x-text="step < totalSteps - 1 ? 'Siguiente' : 'Empezar'"></button>
                </div>
            </div>
        </div>
    </div>
</div>
