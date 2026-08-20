<!doctype html>
<html lang="es" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Portal Kit · Demo</title>
    @vite(['resources/css/portal.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-[#05070A] text-white">

{{-- Background --}}
<!--  <div class="pointer-events-none fixed inset-0 -z-10 portal-bg"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 portal-grid"></div> -->

<div class="mx-auto max-w-2xl px-4 py-12 space-y-10">

    {{-- ── HEADER ─────────────────────────────────────────── --}}
    <div>
        <x-portal.badge color="violet">Portal Kit v1.0</x-portal.badge>
        <h1 class="mt-3 text-2xl font-semibold text-white/90">Demo de componentes</h1>
        <p class="mt-1 text-sm text-white/50">Referencia visual del sistema de diseño del Portal Taxista.</p>
    </div>

    {{-- ── SECCIÓN: CARDS ──────────────────────────────────── --}}
    <section class="space-y-3">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-white/40">Cards · x-portal.card</h2>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
            <x-portal.card class="p-4">
                <p class="text-xs font-medium tracking-wide text-white/50">DOCUMENTOS</p>
                <p class="mt-1 text-2xl font-semibold text-white/90">12</p>
                <p class="mt-1 text-xs text-white/40">Este mes</p>
            </x-portal.card>

            <x-portal.card class="p-4">
                <p class="text-xs font-medium tracking-wide text-white/50">CITAS</p>
                <p class="mt-1 text-2xl font-semibold text-white/90">3</p>
                <p class="mt-1 text-xs text-white/40">Pendientes</p>
            </x-portal.card>

            <x-portal.card class="p-4">
                <p class="text-xs font-medium tracking-wide text-white/50">TICKETS</p>
                <p class="mt-1 text-2xl font-semibold text-white/90">1</p>
                <p class="mt-1 text-xs text-white/40">En proceso</p>
            </x-portal.card>

            <x-portal.card class="p-4">
                <p class="text-xs font-medium tracking-wide text-white/50">TAXIS</p>
                <p class="mt-1 text-2xl font-semibold text-white/90">2</p>
                <p class="mt-1 text-xs text-white/40">Activos</p>
            </x-portal.card>
        </div>
    </section>

    {{-- ── SECCIÓN: BADGES ────────────────────────────────── --}}
    <section class="space-y-3">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-white/40">Badges · x-portal.badge</h2>
        <x-portal.card class="p-4 flex flex-wrap gap-2">
            <x-portal.badge color="red">Urgente</x-portal.badge>
            <x-portal.badge color="blue">Nómina</x-portal.badge>
            <x-portal.badge color="emerald">Pagado</x-portal.badge>
            <x-portal.badge color="amber">Pendiente</x-portal.badge>
            <x-portal.badge color="violet">Comunicación</x-portal.badge>
            <x-portal.badge color="zinc">Archivo</x-portal.badge>
        </x-portal.card>
    </section>

    {{-- ── SECCIÓN: BUTTONS ───────────────────────────────── --}}
    <section class="space-y-3">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-white/40">Buttons · x-portal.button</h2>
        <x-portal.card class="p-4 flex flex-wrap gap-3">
            <x-portal.button variant="primary">+ Nuevo documento</x-portal.button>
            <x-portal.button variant="ghost">Ver todo</x-portal.button>
            <x-portal.button variant="primary" as="a" href="#">Enlace primario</x-portal.button>
            <x-portal.button variant="ghost" as="a" href="#">Enlace ghost</x-portal.button>
        </x-portal.card>
    </section>

    {{-- ── SECCIÓN: ROWS ──────────────────────────────────── --}}
    <section class="space-y-3">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-white/40">Rows · x-portal.row</h2>

        <div class="space-y-2">
            <x-portal.row
                title="Nómina Enero 2024"
                subtitle="Hace 2 días"
                href="#"
                iconBg="bg-blue-500/10 ring-1 ring-blue-500/20"
            >
                <x-slot:icon>
                    <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot:icon>
                <x-slot:right>
                    <x-portal.badge color="blue">NÓMINA</x-portal.badge>
                </x-slot:right>
            </x-portal.row>

            <x-portal.row
                title="Justificante de pago"
                subtitle="Ayer"
                href="#"
                iconBg="bg-emerald-500/10 ring-1 ring-emerald-500/20"
            >
                <x-slot:icon>
                    <svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </x-slot:icon>
                <x-slot:right>
                    <x-portal.badge color="emerald">PAGADO</x-portal.badge>
                </x-slot:right>
            </x-portal.row>

            <x-portal.row
                title="Ticket soporte abierto"
                subtitle="Hace 3 horas"
                iconBg="bg-amber-500/10 ring-1 ring-amber-500/20"
            >
                <x-slot:icon>
                    <svg class="h-5 w-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </x-slot:icon>
                <x-slot:right>
                    <x-portal.badge color="amber">EN PROCESO</x-portal.badge>
                </x-slot:right>
            </x-portal.row>

            <x-portal.row
                title="Cita: Revisión ITV"
                subtitle="Mañana 10:00"
                href="#"
                iconBg="bg-violet-500/10 ring-1 ring-violet-500/20"
            >
                <x-slot:icon>
                    <svg class="h-5 w-5 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </x-slot:icon>
                <x-slot:right>
                    <x-portal.badge color="violet">CITA</x-portal.badge>
                </x-slot:right>
            </x-portal.row>
        </div>
    </section>

    {{-- ── SECCIÓN: INPUTS ────────────────────────────────── --}}
    <section class="space-y-3">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-white/40">Inputs · .input-glass</h2>
        <x-portal.card class="p-4 space-y-3">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-white/70">Campo de texto</label>
                <input type="text" class="input-glass" placeholder="Escribe aquí…">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-white/70">Contraseña</label>
                <input type="password" class="input-glass" placeholder="••••••••">
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-white/70">Área de texto</label>
                <textarea class="input-glass" rows="3" placeholder="Descripción…"></textarea>
            </div>
        </x-portal.card>
    </section>

    {{-- ── SECCIÓN: CARD COMPLEJA ──────────────────────────── --}}
    <section class="space-y-3">
        <h2 class="text-xs font-semibold uppercase tracking-widest text-white/40">Composición completa</h2>

        <x-portal.card class="p-5 space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-semibold text-white/90">Documentos recientes</p>
                    <p class="text-sm text-white/50">Últimos 30 días</p>
                </div>
                <x-portal.button variant="ghost" as="a" href="#">Ver todos</x-portal.button>
            </div>

            <div class="space-y-2">
                <x-portal.row title="Contrato 2024" subtitle="01/01/2024" href="#"
                              iconBg="bg-red-500/10 ring-1 ring-red-500/20">
                    <x-slot:icon>
                        <svg class="h-5 w-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </x-slot:icon>
                    <x-slot:right>
                        <x-portal.badge color="red">CONTRATO</x-portal.badge>
                    </x-slot:right>
                </x-portal.row>

                <x-portal.row title="Nómina Diciembre" subtitle="31/12/2023" href="#"
                              iconBg="bg-blue-500/10 ring-1 ring-blue-500/20">
                    <x-slot:icon>
                        <svg class="h-5 w-5 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                             stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </x-slot:icon>
                    <x-slot:right>
                        <x-portal.badge color="blue">NÓMINA</x-portal.badge>
                    </x-slot:right>
                </x-portal.row>
            </div>

            <x-portal.button variant="primary" class="w-full justify-center">
                + Subir documento
            </x-portal.button>
        </x-portal.card>
    </section>

    {{-- ── FOOTER ──────────────────────────────────────────── --}}
    <footer class="text-center text-xs text-white/20 pb-6">
        Portal Kit v1.0 · Nova Taxi System · {{ date('Y') }}
    </footer>

</div>

</body>
</html>
