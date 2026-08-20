<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} · Acceso</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    @vite(['resources/css/portal.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-screen bg-[#05070A] text-white">

    {{-- Fondo Portal Pro (sistema oficial) --}}
    <div class="pointer-events-none fixed inset-0 -z-10 portal-bg"></div>
    <div class="pointer-events-none fixed inset-0 -z-10 portal-grid"></div>

<div class="min-h-screen flex items-center justify-center p-6">
    <div class="relative w-full max-w-2xl" x-data="{ hovered: null }">

        {{-- Logo --}}
        <div class="flex flex-col items-center mb-10">
            <img src="{{ asset('img/logo.png') }}" alt="Taxilanz"
                 class="h-12 object-contain opacity-95 drop-shadow-[0_16px_48px_rgba(239,68,68,0.3)] mb-5">
            <h1 class="text-2xl font-semibold tracking-tight text-white/90">Selecciona tu acceso</h1>
            <p class="text-sm text-white/40 mt-1.5">Elige el panel que corresponde a tu perfil</p>
        </div>

        {{-- Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

            {{-- Admin --}}
            <a href="{{ url('admin/login') }}"
               @mouseenter="hovered = 'admin'" @mouseleave="hovered = null"
               class="group relative flex flex-col items-center gap-4 rounded-3xl p-7 overflow-hidden
                  border transition-all duration-300 cursor-pointer select-none
                  bg-white/[0.035] border-white/[0.07]
                  hover:bg-white/[0.06] hover:border-white/[0.14] hover:-translate-y-1
                  hover:shadow-[0_24px_80px_rgba(139,92,246,0.18)]">

                {{-- Glow de fondo --}}
                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500
                        bg-gradient-to-br from-violet-500/10 via-transparent to-transparent pointer-events-none"></div>

                {{-- Icono --}}
                <div class="relative z-10 h-14 w-14 rounded-2xl flex items-center justify-center
                        bg-gradient-to-br from-violet-500/20 to-purple-700/15
                        ring-1 ring-violet-500/25 group-hover:ring-violet-400/40
                        shadow-[0_8px_24px_rgba(139,92,246,0.2)] transition-all duration-300
                        group-hover:scale-110 group-hover:shadow-[0_12px_32px_rgba(139,92,246,0.35)]">
                    <svg class="h-7 w-7 text-violet-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                    </svg>
                </div>

                {{-- Texto --}}
                <div class="relative z-10 text-center">
                    <div class="text-base font-semibold text-white/90 group-hover:text-white transition-colors">Admin
                    </div>
                    <div class="text-xs text-white/40 mt-1 group-hover:text-white/55 transition-colors">Panel de
                        administración
                    </div>
                </div>

                {{-- Badge --}}
                <span class="relative z-10 text-[10px] font-bold tracking-widest uppercase
                         px-3 py-1 rounded-full
                         bg-violet-500/10 text-violet-300 ring-1 ring-violet-500/20">
                FILAMENT
            </span>

                {{-- Flecha --}}
                <svg class="absolute bottom-5 right-5 h-4 w-4 text-white/20 group-hover:text-white/50
                        transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/>
                </svg>
            </a>

            {{-- App Staff --}}
            <a href="{{ url('app/login') }}"
               @mouseenter="hovered = 'app'" @mouseleave="hovered = null"
               class="group relative flex flex-col items-center gap-4 rounded-3xl p-7 overflow-hidden
                  border transition-all duration-300 cursor-pointer select-none
                  bg-white/[0.035] border-white/[0.07]
                  hover:bg-white/[0.06] hover:border-white/[0.14] hover:-translate-y-1
                  hover:shadow-[0_24px_80px_rgba(59,130,246,0.18)]">

                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500
                        bg-gradient-to-br from-blue-500/10 via-transparent to-transparent pointer-events-none"></div>

                <div class="relative z-10 h-14 w-14 rounded-2xl flex items-center justify-center
                        bg-gradient-to-br from-blue-500/20 to-cyan-700/15
                        ring-1 ring-blue-500/25 group-hover:ring-blue-400/40
                        shadow-[0_8px_24px_rgba(59,130,246,0.2)] transition-all duration-300
                        group-hover:scale-110 group-hover:shadow-[0_12px_32px_rgba(59,130,246,0.35)]">
                    <svg class="h-7 w-7 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                    </svg>
                </div>

                <div class="relative z-10 text-center">
                    <div class="text-base font-semibold text-white/90 group-hover:text-white transition-colors">App
                        Staff
                    </div>
                    <div class="text-xs text-white/40 mt-1 group-hover:text-white/55 transition-colors">Gestión
                        operativa
                    </div>
                </div>

                <span class="relative z-10 text-[10px] font-bold tracking-widest uppercase
                         px-3 py-1 rounded-full
                         bg-blue-500/10 text-blue-300 ring-1 ring-blue-500/20">
                STAFF
            </span>

                <svg class="absolute bottom-5 right-5 h-4 w-4 text-white/20 group-hover:text-white/50
                        transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/>
                </svg>
            </a>

            {{-- Portal Taxista --}}
            <a href="{{ url('portal/login') }}"
               @mouseenter="hovered = 'portal'" @mouseleave="hovered = null"
               class="group relative flex flex-col items-center gap-4 rounded-3xl p-7 overflow-hidden
                  border transition-all duration-300 cursor-pointer select-none
                  bg-white/[0.035] border-white/[0.07]
                  hover:bg-white/[0.06] hover:border-red-500/25 hover:-translate-y-1
                  hover:shadow-[0_24px_80px_rgba(239,68,68,0.18)]">

                <div class="absolute inset-0 opacity-0 group-hover:opacity-100 transition-opacity duration-500
                        bg-gradient-to-br from-red-500/10 via-transparent to-transparent pointer-events-none"></div>

                <div class="relative z-10 h-14 w-14 rounded-2xl flex items-center justify-center
                        bg-gradient-to-br from-red-500/20 to-rose-700/15
                        ring-1 ring-red-500/25 group-hover:ring-red-400/40
                        shadow-[0_8px_24px_rgba(239,68,68,0.2)] transition-all duration-300
                        group-hover:scale-110 group-hover:shadow-[0_12px_32px_rgba(239,68,68,0.35)]">
                    <svg class="h-7 w-7 text-red-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                         stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12"/>
                    </svg>
                </div>

                <div class="relative z-10 text-center">
                    <div class="text-base font-semibold text-white/90 group-hover:text-white transition-colors">Portal
                        Taxista
                    </div>
                    <div class="text-xs text-white/40 mt-1 group-hover:text-white/55 transition-colors">Acceso con NIF
                    </div>
                </div>

                <span class="relative z-10 text-[10px] font-bold tracking-widest uppercase
                         px-3 py-1 rounded-full
                         bg-red-500/10 text-red-300 ring-1 ring-red-500/20">
                TAXILANZ
            </span>

                <svg class="absolute bottom-5 right-5 h-4 w-4 text-white/20 group-hover:text-white/50
                        transition-all duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25"/>
                </svg>
            </a>

        </div>

        {{-- Footer --}}
        <div class="mt-8 text-center text-[11px] text-white/25 tracking-wider">
            Nova · Lanzarote · Sistema operativo
        </div>

    </div>
</div>
@fluxScripts
</body>
</html>
