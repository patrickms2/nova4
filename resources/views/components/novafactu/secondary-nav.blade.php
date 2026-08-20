<aside x-show="sidebarOpen"
       x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-500"
       x-transition:enter-start="w-0 opacity-0 -translate-x-10"
       x-transition:enter-end="w-68 opacity-100 translate-x-0"
       x-transition:leave="transition ease-[cubic-bezier(0.25,1,0.5,1)] duration-400"
       x-transition:leave-start="w-68 opacity-100 translate-x-0"
       x-transition:leave-end="w-0 opacity-0 -translate-x-10"
       class="w-68 h-full bg-slate-950/40 backdrop-blur-xl border-r border-white/5 flex flex-col py-6 px-4 shrink-0 z-30 transition-all shadow-[10px_0_30px_rgba(0,0,0,0.2)]">
    
    <!-- SUBMENÚ 1: DASHBOARD -->
    <div x-show="activeTab === 'dashboard'" 
         x-transition:enter="transition duration-300 delay-150 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex flex-col h-full w-full">
        
        <div class="mb-6 px-2">
            <span class="text-[9px] font-bold text-indigo-400 uppercase tracking-widest bg-indigo-500/10 px-2 py-0.5 rounded border border-indigo-500/20">Módulo Core</span>
            <p class="text-xl font-bold text-white tracking-tight mt-2">Sistemas Integrados</p>
        </div>

        <nav class="flex-1 flex flex-col gap-1.5 overflow-y-auto hidden-scrollbar">
            <!-- Link con efecto Neomórfico/Glow -->
            <a href="#" class="flex items-center justify-between px-3 py-2.5 bg-gradient-to-r from-indigo-500/20 to-purple-500/10 text-white font-medium rounded-xl border border-indigo-500/20 shadow-[0_4px_20px_rgba(99,102,241,0.15)] group transition-all duration-300 hover:border-indigo-500/40">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-indigo-500/20 rounded-lg text-indigo-400 group-hover:scale-110 transition-transform">
                        <x-lucide-terminal class="w-4 h-4" />
                    </div>
                    <span class="text-sm tracking-wide">Terminal Activa</span>
                </div>
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
            </a>

            <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-400 hover:bg-white/5 hover:text-white font-medium rounded-xl transition-all duration-300 group border border-transparent hover:border-white/5">
                <div class="p-1.5 bg-white/5 rounded-lg text-slate-400 group-hover:text-white transition-colors">
                    <x-lucide-activity class="w-4 h-4" />
                </div>
                <span>Telemetría de Servidores</span>
            </a>
        </nav>
    </div>

    <!-- SUBMENÚ 2: ANALÍTICA -->
    <div x-show="activeTab === 'analytics'" 
         x-transition:enter="transition duration-300 delay-150 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex flex-col h-full w-full">
        
        <div class="mb-6 px-2">
            <span class="text-[9px] font-bold text-purple-400 uppercase tracking-widest bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20">Big Data</span>
            <p class="text-xl font-bold text-white tracking-tight mt-2">Analítica Dinámica</p>
        </div>

        <nav class="flex-1 flex flex-col gap-1.5 overflow-y-auto">
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-400 hover:bg-white/5 hover:text-white font-medium rounded-xl transition-all duration-300 group border border-transparent hover:border-white/5">
                <div class="p-1.5 bg-white/5 rounded-lg text-slate-400 group-hover:text-purple-400 transition-colors">
                    <x-lucide-pie-chart class="w-4 h-4" />
                </div>
                <span>Conversiones del Mes</span>
            </a>
        </nav>
    </div>

    <!-- SUBMENÚ 3: AJUSTES -->
    <div x-show="activeTab === 'settings'" 
         x-transition:enter="transition duration-300 delay-150 transform"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="flex flex-col h-full w-full">
        
        <div class="mb-6 px-2">
            <span class="text-[9px] font-bold text-amber-400 uppercase tracking-widest bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">Config</span>
            <p class="text-xl font-bold text-white tracking-tight mt-2">Sistema Base</p>
        </div>

        <nav class="flex-1 flex flex-col gap-1.5 overflow-y-auto">
            <a href="#" class="flex items-center gap-3 px-3 py-2.5 text-sm text-slate-400 hover:bg-white/5 hover:text-white font-medium rounded-xl transition-all duration-300 group border border-transparent hover:border-white/5">
                <div class="p-1.5 bg-white/5 rounded-lg text-slate-400 group-hover:text-amber-400 transition-colors">
                    <x-lucide-sliders-horizontal class="w-4 h-4" />
                </div>
                <span>Preferencias de UI</span>
            </a>
        </nav>
    </div>

</aside>
