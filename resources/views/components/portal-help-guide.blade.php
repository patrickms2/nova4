@php
    $manualUrl = asset('docs/manual-taxista.html');
    $taxistaName = auth()->user()?->taxista?->name ?? 'Taxista';
@endphp

<div
    x-data="{
        showGuide: false,
        openGuide() {
            this.showGuide = true
        },
    }"
    @open-portal-help-guide.window="openGuide()"
    class="inline-flex"
>
    <button
        type="button"
        x-on:click="openGuide()"
        class="inline-flex items-center gap-2 rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-primary-700"
    >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-4 w-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
        </svg>
        Manual de Uso
    </button>

    <template x-teleport="body">
        <div
            x-show="showGuide"
            x-transition.opacity
            x-on:keydown.escape.window="showGuide = false"
            class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-950/70 p-4 backdrop-blur-sm"
            style="display: none;"
        >
            <div class="absolute inset-0" x-on:click="showGuide = false"></div>

            <div
                x-show="showGuide"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3 scale-[0.98]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-3 scale-[0.98]"
                class="relative flex h-[min(92vh,860px)] w-full max-w-7xl flex-col overflow-hidden rounded-[28px] border border-white/10 bg-[#07111f] text-white shadow-[0_40px_120px_-36px_rgba(2,6,23,0.72)]"
            >
                <div class="relative overflow-hidden border-b border-white/10 bg-[linear-gradient(135deg,#07111f_0%,#10223a_52%,#0f6cbd_120%)] px-6 py-5">
                    <div class="absolute inset-y-0 right-0 w-1/3 bg-[radial-gradient(circle_at_top_right,rgba(255,255,255,0.14),transparent_60%)]"></div>

                    <div class="relative flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="max-w-3xl">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-white/50">Portal taxista</p>
                            <h3 class="mt-2 text-2xl font-black tracking-tight">Manual del Taxista</h3>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-white/76">
                                Guía visual para {{ $taxistaName }} con dashboard, citas, documentos, taxis, gastos, soporte y mapa en vivo.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2 text-xs">
                                <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">Dashboard y perfil</span>
                                <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">Citas y documentos</span>
                                <span class="rounded-full border border-white/10 bg-white/8 px-3 py-1.5">Gastos y soporte</span>
                            </div>
                        </div>

                        <div class="relative flex items-center gap-3">
                            <a
                                href="{{ $manualUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="inline-flex items-center gap-2 rounded-2xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 transition hover:bg-slate-100"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-7.5 0 7.5-7.5m0 0H12m6 0v6" />
                                </svg>
                                Abrir completo
                            </a>

                            <button
                                type="button"
                                x-on:click="showGuide = false"
                                class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/15 bg-white/8 text-white transition hover:bg-white/14"
                                aria-label="Cerrar ayuda"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-5 w-5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid flex-1 gap-0 lg:grid-cols-[300px_minmax(0,1fr)]">
                    <aside class="border-b border-white/10 bg-[#091524] p-5 lg:border-b-0 lg:border-r">
                        <div class="rounded-3xl border border-white/10 bg-white/5 p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/45">Contenido</p>
                            <ul class="mt-4 space-y-3 text-sm text-white/72">
                                <li class="rounded-2xl bg-white/5 px-3 py-2">Panel principal y perfil</li>
                                <li class="rounded-2xl bg-white/5 px-3 py-2">Mapa, taxis y conductores</li>
                                <li class="rounded-2xl bg-white/5 px-3 py-2">Citas, hoteles y calendario</li>
                                <li class="rounded-2xl bg-white/5 px-3 py-2">Documentos, vencimientos y gastos</li>
                                <li class="rounded-2xl bg-white/5 px-3 py-2">Chat, tickets y app móvil</li>
                            </ul>
                        </div>

                        <div class="mt-4 rounded-3xl bg-[linear-gradient(160deg,rgba(255,255,255,0.08)_0%,rgba(12,74,110,0.28)_100%)] p-4 text-sm text-white/72 ring-1 ring-white/10">
                            <p class="font-semibold text-white">Consejo</p>
                            <p class="mt-2 leading-6">
                                Usa el modal para consulta rápida y “Abrir completo” si quieres navegar el manual con más espacio.
                            </p>
                        </div>
                    </aside>

                    <div class="min-h-0 bg-[#050d18] p-3 sm:p-4">
                        <div class="h-full overflow-hidden rounded-[24px] border border-white/10 bg-white">
                            <iframe
                                src="{{ $manualUrl }}"
                                title="Manual del taxista"
                                class="h-full min-h-[56vh] w-full bg-white"
                                loading="lazy"
                            ></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
