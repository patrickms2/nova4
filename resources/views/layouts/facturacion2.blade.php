<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="{ dark: $store.theme?.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NovaFact | {{ config('app.name', 'NovaFact') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        .end-64 {
            inset-inline-end: 64px;
        }
        .size-6 {
    width: calc(var(--spacing) * 6) !important;
    height: calc(var(--spacing) * 6) !important;
}
.from-orange-600{--tw-gradient-from:var(--color-orange-600);--tw-gradient-stops:var(--tw-gradient-via-stops,var(--tw-gradient-position), var(--tw-gradient-from) var(--tw-gradient-from-position), var(--tw-gradient-to) var(--tw-gradient-to-position))}

    </style>
</head>
<body class="h-full antialiased font-sans overflow-hidden selection:bg-indigo-500 selection:text-white">
    @livewireScripts
    @livewireMapScripts
   <!-- Contenedor Raíz de Alta Fidelidad (Une los dos niveles en una fila flexible) -->
<div x-data="{ 
        activeTab: $persist('facturacion'), 
        sidebarOpen: $persist(true) 
     }" 
     class="flex flex-row h-screen w-screen overflow-hidden bg-slate-950 text-slate-100" 
     data-name="Two Level Sidebar">
    
    <!-- ========================================== -->
    <!-- NIVEL 1: BARRA DE ICONOS ULTRA-ESTRECHA    -->
    <!-- ========================================== -->
    <aside class="flex flex-col justify-between w-18 h-full bg-slate-950 border-r border-white/5 items-center py-5 shrink-0 z-40 relative shadow-[10px_0_30px_rgba(0,0,0,0.5)]">
        <!-- Branding Superior -->
        <div class="flex flex-col gap-8 items-center w-full px-2">
            <div class="p-2.5 bg-gradient-to-tr from-indigo-500 to-purple-600 rounded-xl shadow-lg shadow-indigo-500/10 group cursor-pointer">
                <x-lucide-file-text class="w-5 h-5 text-white" />
            </div>
            
            <!-- Navegación de Macros/Módulos -->
            <nav class="flex flex-col gap-2.5 w-full items-center">
                <!-- Botón Core de Facturación (Activo) -->
                <button @click="activeTab = 'facturacion'; sidebarOpen = true"
                        :class="activeTab === 'facturacion' ? 'bg-white/10 text-indigo-400 border-indigo-500/40' : 'text-slate-400 hover:text-white border-transparent'"
                        class="p-3 rounded-xl flex justify-center items-center transition-all duration-300 border cursor-pointer relative group">
                    <x-lucide-layers class="w-5 h-5" />
                    <span class="absolute left-20 bg-slate-900 border border-white/10 text-white text-xs px-2.5 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-50">
                        Gestión Comercial
                    </span>
                </button>

                <!-- Botón de Inteligencia Artificial / OCR -->
                <button @click="activeTab = 'ia'; sidebarOpen = true"
                        :class="activeTab === 'ia' ? 'bg-white/10 text-indigo-400 border-indigo-500/40' : 'text-slate-400 hover:text-white border-transparent'"
                        class="p-3 rounded-xl flex justify-center items-center transition-all duration-300 border cursor-pointer relative group">
                    <x-lucide-cpu class="w-5 h-5" />
                    <span class="absolute left-20 bg-slate-900 border border-white/10 text-white text-xs px-2.5 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 transition-all duration-200 pointer-events-none whitespace-nowrap z-50">
                        Automatización OCR
                    </span>
                </button>
            </nav>
        </div>

        <!-- Grupo Inferior (Ajustes y Admin de tu captura) -->
        <div class="flex flex-col gap-4 items-center w-full px-2">
            <button class="p-3 text-slate-400 hover:text-white rounded-xl flex justify-center transition-all cursor-pointer">
                <x-lucide-settings class="w-5 h-5" />
            </button>
            <div class="h-px w-6 bg-white/5"></div>
            <!-- Avatar de Admin -->
            <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-indigo-500 to-pink-500 p-[1px] cursor-pointer shadow-md">
                <div class="w-full h-full bg-slate-900 rounded-xl flex items-center justify-center text-xs font-bold text-white uppercase">
                    Ad
                </div>
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- NIVEL 2: SUBMENÚ DETALLADO DESPLEGABLE    -->
    <!-- ========================================== -->
    <aside x-show="sidebarOpen"
           x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-400"
           x-transition:enter-start="w-0 opacity-0"
           x-transition:enter-end="w-64 opacity-100"
           x-transition:leave="transition ease-in duration-300"
           x-transition:leave-end="w-0 opacity-0"
           class="w-64 h-full bg-slate-950/40 backdrop-blur-xl border-r border-white/5 flex flex-col py-6 px-4 shrink-0 z-30 shadow-[10px_0_30px_rgba(0,0,0,0.2)]">
        
        <!-- SUBMENÚ ASOCIADO A TU PANEL (Facturación) -->
        <div x-show="activeTab === 'facturacion'" class="flex flex-col h-full w-full space-y-6">
            <!-- Título NovaFactu de tu imagen -->
            <div class="px-2">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-white tracking-tight">NovaFactu</h2>
                        <p class="text-[11px] text-slate-500 font-medium">Nova Hub</p>
                    </div>
                    <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.4)]"></span>
                </div>
                
                <!-- Buscador Integrado -->
                <div class="mt-4 relative">
                    <x-lucide-search class="w-3.5 h-3.5 text-slate-500 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input type="text" placeholder="Buscar..." class="w-full bg-white/5 border border-white/5 rounded-xl pl-8 pr-3 py-1.5 text-xs text-slate-300 focus:outline-none focus:border-indigo-500 transition-all">
                </div>
            </div>

            <!-- Listado de Acciones y Módulos de tu captura -->
            <div class="flex-1 overflow-y-auto space-y-4 pr-1 hidden-scrollbar">
                <!-- Bloque Acciones -->
                <div class="space-y-1">
                    <p class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Acciones</p>
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-bold text-indigo-400 bg-indigo-500/5 hover:bg-indigo-500/10 rounded-xl transition-all border border-indigo-500/10">
                        <x-lucide-plus class="w-3.5 h-3.5" />
                        <span>Nueva factura</span>
                    </a>
                </div>

                <!-- Bloque Módulos Esenciales -->
                <div class="space-y-1">
                    <p class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Módulos</p>
                    
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-layout-dashboard class="w-4 h-4 text-slate-500" />
                        <span>Dashboard</span>
                    </a>

                    <a href="#" class="flex items-center justify-between px-3 py-2 text-xs font-bold text-white bg-white/5 rounded-xl transition-all border border-white/5">
                        <div class="flex items-center gap-2.5">
                            <x-lucide-file-text class="w-4 h-4 text-indigo-400" />
                            <span>Facturas</span>
                        </div>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-wallet class="w-4 h-4 text-slate-500" />
                        <span>Gastos</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-layers class="w-4 h-4 text-slate-500" />
                        <span>Remesas</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-users class="w-4 h-4 text-slate-500" />
                        <span>Clientes</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-building class="w-4 h-4 text-slate-500" />
                        <span>Empresas</span>
                    </a>
                </div>

                <!-- Bloque Avanzado y Sistemas Legales -->
                <div class="space-y-1">
                    <p class="px-2 text-[10px] font-bold text-slate-500 uppercase tracking-widest">Sistemas</p>
                    
                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-white hover:bg-white/5 rounded-xl transition-all">
                        <x-lucide-scan-face class="w-4 h-4 text-slate-500" />
                        <span>OCR Inteligente</span>
                    </a>

                    <a href="#" class="flex items-center gap-2.5 px-3 py-2 text-xs font-semibold text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/5 rounded-xl transition-all">
                        <x-lucide-shield-check class="w-4 h-4 text-emerald-500/70" />
                        <span>VeriFactu Activo</span>
                    </a>
                </div>
            </div>
        </div>
    </aside>

    <!-- ========================================== -->
    <!-- ÁREA DE CONTENIDO CENTRAL (WORKSPACE)      -->
    <!-- ========================================== -->
    <main class="flex-1 flex flex-col h-full min-w-0 bg-slate-900/40 relative overflow-y-auto">
        <!-- Barra de Control Superior -->
        <header class="h-16 border-b border-white/5 flex items-center px-6 justify-between shrink-0 bg-slate-950/20 backdrop-blur-md">
 
    <x-ui.sonner position="bottom-right" />
</body>
</html>


