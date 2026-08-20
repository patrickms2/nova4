<div x-data="{ showFilters: false }" class="flex flex-col h-full bg-white text-neutral-950">
    {{-- TOP BAR --}}
    <div class="bg-white border-b shrink-0 border-neutral-200">
        <div class="flex items-center h-12 gap-2 px-4">
            <span class="text-sm font-semibold text-neutral-800">Tipos de tareas</span>
            <span class="text-xs text-neutral-400">{{ $taskTypes->total() }} registros</span>
            <div class="ml-auto flex items-center gap-1.5">
                <x-ui.button size="sm" variant="outline" @click="showFilters = !showFilters">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                </x-ui.button>
                <x-ui.button size="sm" wire:click="openNew">
                    <x-lucide-plus class="size-3.5" />
                    Nuevo tipo
                </x-ui.button>
            </div>
        </div>

        {{-- FILTERS --}}
        <div x-show="showFilters" x-transition x-cloak class="px-4 py-3 border-t border-neutral-100 bg-neutral-50/50">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Nombre..." />
                </div>
                @if($search)
                    <x-ui.button size="sm" variant="ghost" wire:click="$set('search', '')" class="text-muted-foreground">
                        <x-lucide-x class="size-3" />
                        Limpiar
                    </x-ui.button>
                @endif
            </div>
        </div>
    </div>

    {{-- CONTENT --}}
    <div class="flex-1 overflow-auto bg-[#f7f7f5]">
        <div class="flex flex-col flex-1 w-full gap-4 p-4 mx-auto md:gap-5 md:p-6 max-w-7xl">
            <x-ui.card class="overflow-hidden bg-white border shadow-sm border-neutral-200 rounded-2xl">
                <x-ui.card-content class="p-0">
                    <x-ui.table>
                        <x-ui.table-header>
                            <x-ui.table-row class="hover:bg-transparent">
                                <x-ui.table-head>Nombre</x-ui.table-head>
                                <x-ui.table-head>Orden</x-ui.table-head>
                                <x-ui.table-head>Estado</x-ui.table-head>
                                <x-ui.table-head class="w-20"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($taskTypes as $taskType)
                                <x-ui.table-row wire:key="task-type-{{ $taskType->id }}" class="group hover:bg-muted/50">
                                    <x-ui.table-cell>
                                        <span class="font-medium text-sm">{{ $taskType->name }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm text-muted-foreground">{{ $taskType->sort }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @if($taskType->active)
                                            <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Activo</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">Inactivo</span>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="pr-2">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button size="sm" variant="outline" wire:click="openEdit({{ $taskType->id }})" class="opacity-0 group-hover:opacity-100 size-7 p-0">
                                                <x-lucide-pencil class="size-4" />
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="outline" wire:click="delete({{ $taskType->id }})" wire:confirm="¿Eliminar tipo de tarea?" class="opacity-0 group-hover:opacity-100 size-7 p-0 text-destructive">
                                                <x-lucide-trash-2 class="size-4" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="4" class="py-12 text-center">
                                        <x-ui.empty class="p-0 border-0">
                                            <x-lucide-folder-cog class="mx-auto mb-2 size-10 opacity-30" />
                                            <p class="text-sm font-medium text-muted-foreground">Sin tipos de tareas</p>
                                        </x-ui.empty>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>

            <div class="mt-2">
                {{ $taskTypes->links() }}
            </div>
        </div>
    </div>

    {{-- SHEET --}}
    <x-ui.sheet entangle="$wire.entangle('showForm')" x-cloak>
        <x-ui.sheet-content side="right" :show-close="false" class="flex flex-col w-screen max-w-2xl gap-0 p-0 overflow-hidden">
            <x-ui.sheet-header class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b">
                <x-ui.sheet-title class="text-sm">
                    {{ $taskTypeId ? 'Editar tipo de tarea' : 'Nuevo tipo de tarea' }}
                </x-ui.sheet-title>
                <button type="button" wire:click="closeForm" class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3.5 text-xs">
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Nombre</label>
                    <x-ui.input size="sm" wire:model="name" placeholder="Nombre del tipo" />
                    @error('name') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Orden</label>
                        <x-ui.input type="number" size="sm" wire:model="sort" placeholder="0" />
                        @error('sort') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Activo</label>
                        <x-ui.select native size="sm" wire:model="active">
                            <option value="1">Sí</option>
                            <option value="0">No</option>
                        </x-ui.select>
                    </div>
                </div>
            </div>

            <x-ui.sheet-footer class="flex-row justify-between gap-2 px-4 py-2 border-t shrink-0">
                @if($taskTypeId)
                    <x-ui.button type="button" variant="secondary" wire:click="delete({{ $taskTypeId }})" wire:confirm="¿Eliminar tipo de tarea?" class="text-destructive">
                        <x-lucide-trash-2 class="size-4" />
                        Eliminar
                    </x-ui.button>
                @else
                    <div></div>
                @endif
                <div class="flex gap-2">
                    <x-ui.button type="button" variant="secondary" wire:click="closeForm">Cancelar</x-ui.button>
                    <x-ui.button type="button" wire:click="save">
                        <x-lucide-check class="size-4" />
                        Guardar
                    </x-ui.button>
                </div>
            </x-ui.sheet-footer>
        </x-ui.sheet-content>
    </x-ui.sheet>
</div>
