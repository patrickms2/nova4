<div x-data="{ open: false }" class="relative z-50">
    <!-- Botón de Activación Magnético -->
    <button @click="open = !open" 
            @click.outside="open = false"
            class="relative p-3 bg-white/5 hover:bg-white/10 text-slate-300 hover:text-white rounded-2xl border border-white/5 hover:border-white/10 transition-all duration-300 shadow-xl cursor-pointer group">
        
        <!-- Campana con balanceo orgánico controlado -->
        <x-lucide-bell class="w-5 h-5 transform group-hover:rotate-12 transition-transform duration-200" />
        
        <!-- Indicador de Pulso Radiónico -->
        <span class="absolute -top-1 -right-1 flex h-4 w-4">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-4 w-4 bg-gradient-to-r from-indigo-500 to-purple-500 text-[9px] font-extrabold text-white items-center justify-center shadow-lg">3</span>
        </span>
    </button>

    <!-- Dropdown Orbitario Suspendido -->
    <div x-show="open"
         x-transition:enter="transition ease-[cubic-bezier(0.34,1.56,0.64,1)] duration-400 transform"
         x-transition:enter-start="opacity-0 scale-95 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-95 translate-y-4"
         class="absolute right-0 mt-4 w-96 rounded-3xl border border-white/10 bg-slate-950/80 backdrop-blur-2xl p-4 shadow-[0_30px_60px_rgba(0,0,0,0.6)] space-y-4 text-left">
        
        <div class="flex justify-between items-center border-b border-white/5 pb-3">
            <h4 class="text-sm font-bold text-white tracking-wide">Frecuencias de Alerta</h4>
            <button class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300 transition-colors">Limpiar canal</button>
        </div>

        <div class="space-y-2 max-h-72 overflow-y-auto hidden-scrollbar">
            <!-- Alerta 1 -->
            <div class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition-all duration-200 flex gap-3 items-start group cursor-pointer">
                <div class="p-2 bg-indigo-500/10 text-indigo-400 rounded-xl mt-0.5">
                    <x-lucide-activity class="w-4 h-4" />
                </div>
                <div class="space-y-0.5 flex-1">
                    <p class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors">Despliegue completado</p>
                    <p class="text-[11px] text-slate-400 leading-relaxed">El clúster Blade ha sincronizado las vistas cuánticas con éxito.</p>
                    <span class="text-[9px] font-mono text-slate-500 block pt-1">Hace 2 min</span>
                </div>
            </div>
            
            <!-- Alerta 2 -->
            <div class="p-3 bg-white/5 hover:bg-white/10 rounded-2xl border border-white/5 transition-all duration-200 flex gap-3 items-start group cursor-pointer">
                <div class="p-2 bg-amber-500/10 text-amber-400 rounded-xl mt-0.5">
                    <x-lucide-hard-drive class="w-4 h-4" />
                </div>
                <div class="space-y-0.5 flex-1">
                    <p class="text-xs font-bold text-slate-200 group-hover:text-white transition-colors">Espacio optimizado</p>
                    <p class="text-[11px] text-slate-400 leading-relaxed">Se purgaron 4.2 GB de caché inactiva del ecosistema.</p>
                    <span class="text-[9px] font-mono text-slate-500 block pt-1">Hace 14 min</span>
                </div>
            </div>
        </div>
    </div>
</div>
