<div class="p-4 max-w-5xl mx-auto">
    <h1 class="text-2xl font-bold text-foreground mb-6">Ajustes</h1>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <a href="{{ route('comunigest.admin.order-types') }}" wire:navigate class="block p-4 border border-neutral-200 rounded-2xl bg-white hover:bg-muted/50 transition">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
                    <x-lucide-layers class="size-5" />
                </div>
                <div>
                    <h2 class="text-sm font-semibold">Catálogo de tareas</h2>
                    <p class="text-xs text-muted-foreground">Gestiona las tareas del catálogo</p>
                </div>
            </div>
        </a>

        <a href="{{ route('comunigest.admin.task-types') }}" wire:navigate class="block p-4 border border-neutral-200 rounded-2xl bg-white hover:bg-muted/50 transition">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
                    <x-lucide-folder-cog class="size-5" />
                </div>
                <div>
                    <h2 class="text-sm font-semibold">Tipos de tareas</h2>
                    <p class="text-xs text-muted-foreground">Gestiona los tipos de tareas</p>
                </div>
            </div>
        </a>
    </div>
</div>
