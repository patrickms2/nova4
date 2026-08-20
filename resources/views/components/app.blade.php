<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-950 text-slate-100 selection:bg-indigo-500 selection:text-white">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $title ?? config('app.name', 'Laravel') }}</title>

    {{ $meta ?? '' }}

    <!-- Tailwind CSS v4 y Compilación de Scripts Activa -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Desactiva barras de scroll visuales pero mantiene funcionalidad nativa */
        .hidden-scrollbar::-webkit-scrollbar { display: none; }
        .hidden-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="antialiased h-full overflow-hidden font-sans">

<!-- CONTENEDOR MAESTRO REACtIVO CON REBOTE LÍQUIDO -->
<div x-data="{ 
        openSidebar: $persist(true), 
        openModulos: $persist(true), 
        openSistemas: $persist(true) 
     }" 
     class="flex flex-row h-screen w-screen overflow-hidden bg-slate-950 relative" 
     data-name="Two Level Sidebar">
    
    <!-- ========================================== -->
    <!-- NIVEL 1: BARRA DE ICONOS (FIJA)            -->
    <!-- ========================================== -->
    <aside class="flex flex-col justify-between w-18 h-full bg-slate-950 border-r border-white/5 items-center py-5 shrink-0 z-40 relative shadow-[10px_0_30px_rgba(0,0,0,0.5)]">
        <div class="flex flex-col gap-8 items-center w-full px-2">
            <!-- Botón Maestro: Al hacer clic, colapsa/expande el Nivel 2 con rebote -->
            <button @click="openSidebar = !openSidebar" 
                    class="p-2.5 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl shadow-lg cursor-pointer transform hover:scale-110 active:scale-95 transition-all duration-300">
                <x-lucide-layers class="w-5 h-5 text-white" />
            </button>
            
            <nav class="flex flex-col gap-3 w-full items-center">
                <!-- Acceso Rápido a Facturas -->
                <button class="p-3 bg-white/5 text-indigo-400 border border-white/10 rounded-xl flex justify-center items-center transition-all duration-300 cursor-pointer hover:scale-105">
                    <x-lucide-file-text class="w-5 h-5" />
                </button>
            </nav>
        </div>

        <div class="flex flex-col gap-4 items-center w-full px-2">
            <button class="p-3 text-slate-500 hover:text-white transition-colors cursor-pointer group">
                <x-lucide-settings class="w-5 h-5 group-hover:rotate-45 transition-transform duration-300" />
            </button>
            <div class="w-9 h-9 rounded-xl bg-white/5 border border-white/10 flex items-center justify-center text-xs font-bold text-slate-300 cursor-pointer">
                AD
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- NIVEL 2: PANEL DINÁMICO E INDEPENDIENTE     -->
    <!-- ========================================== -->
    <!-- Se ensancha y encoge con transiciones de curva física elástica -->
    <aside x-show="openSidebar"
           x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-400 transform"
           x-transition:enter-start="w-0 opacity-0 -translate-x-10"
           x-transition:enter-end="w-64 opacity-100 translate-x-0"
           x-transition:leave="transition ease-[cubic-bezier(0.25,1,0.5,1)] duration-300 transform"
           x-transition:leave-end="w-0 opacity-0 -translate-x-10"
           class="w-64 h-full bg-slate-950/40 backdrop-blur-xl border-r border-white/5 flex flex-col py-6 px-4 shrink-0 z-30 shadow-[10px_0_30px_rgba(0,0,0,0.2)]">
        
        <!-- Cabecera NovaFactu -->
        <div class="px-2 flex items-center justify-between mb-6">
            <div>
                <h2 class="text-base font-bold text-white tracking-tight">NovaFactu</h2>
                <p class="text-[11px] text-slate-500 font-medium">Nova Hub</p>
            </div>
            <span class="relative flex h-2 w-2">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
            </span>
        </div>

        <!-- Buscador -->
        <div class="px-2 mb-6 relative">
            <x-lucide-search class="w-3.5 h-3.5 text-slate-500 absolute left-5 top-1/2 -translate-y-1/2" />
            <input type="text" placeholder="Buscar..." class="w-full bg-white/5 border border-white/5 rounded-xl pl-9 pr-3 py-1.5 text-xs text-slate-300 focus:outline-none focus:border-indigo-500 transition-all duration-300">
        </div>

        <!-- Cuerpo del Menú Deslizable -->
        <div class="flex-1 overflow-y-auto hidden-scrollbar space-y-6">
            
            <!-- SECCIÓN: ACCIONES -->
            <div class="space-y-1.5">
                <p class="px-2 text-[9px] font-bold text-slate-500 uppercase tracking-widest">Acciones</p>
                <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-indigo-400 bg-indigo-500/5 hover:bg-indigo-500/10 rounded-xl transition-all border border-indigo-500/10 group">
                    <x-lucide-plus class="w-3.5 h-3.5 transform group-hover:rotate-90 transition-transform duration-200" />
                    <span>Nueva factura</span>
                </a>
            </div>

            <!-- SECCIÓN ACORDEÓN: MÓDULOS (Colapsable Independiente) -->
            <div class="space-y-1.5">
                <!-- Botón de Control del Grupo -->
                <button @click="openModulos = !openModulos" 
                        class="w-full flex items-center justify-between px-2 text-[9px] font-bold text-slate-500 uppercase tracking-widest hover:text-slate-300 transition-colors cursor-pointer group">
                    <span>Módulos</span>
                    <x-lucide-chevron-down class="w-3 h-3 transform transition-transform duration-300" :class="openModulos ? '' : '-rotate-90'" />
                </button>
                
                <!-- Lista Colapsable con Transición Fluida -->
                <div x-show="openModulos" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                     class="flex flex-col gap-0.5 origin-top">
                    
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-layout-dashboard class="w-4 h-4 text-slate-500" />
                        <span>Dashboard</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-white bg-white/5 rounded-xl border border-white/5 shadow-inner">
                        <x-lucide-file-text class="w-4 h-4 text-indigo-400" />
                        <span>Facturas</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-wallet class="w-4 h-4 text-slate-500" />
                        <span>Gastos</span>
                    </a>
                </div>
            </div>

            <!-- SECCIÓN ACORDEÓN: SISTEMAS (Colapsable Independiente) -->
            <div class="space-y-1.5">
                <button @click="openSistemas = !openSistemas" 
                        class="w-full flex items-center justify-between px-2 text-[9px] font-bold text-slate-500 uppercase tracking-widest hover:text-slate-300 transition-colors cursor-pointer group">
                    <span>Sistemas</span>
                    <x-lucide-chevron-down class="w-3 h-3 transform transition-transform duration-300" :class="openSistemas ? '' : '-rotate-90'" />
                </button>
                
                <div x-show="openSistemas" 
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-end="opacity-0 -translate-y-2 scale-95"
                     class="flex flex-col gap-0.5 origin-top">
                    
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-scan-face class="w-4 h-4 text-slate-500" />
                        <span>OCR Inteligente</span>
                    </a>
                </div>
            </div>

        </div>
    </aside>

    <!-- ========================================== -->
    <!-- ÁREA DE TRABAJO DINÁMICA (WORKSPACE)      -->
    <!-- ========================================== -->
    <main class="flex-1 flex flex-col h-full min-w-0 bg-gradient-to-b from-slate-950/20 to-black/40 relative overflow-y-auto">
        <div class="p-6 md:p-8 flex-1">
            {{ $slot }}
        </div>
    </main>

</div>

@livewireScripts
</body>
</html>