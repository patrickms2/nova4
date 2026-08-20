@php
    $totalTasks = $tasks->count();
    $completedTasks = $tasks->where('status', 'completed')->count();
    $pendingTasks = $tasks->where('status', 'pending')->count();
    $inProgressTasks = $tasks->where('status', 'in_progress')->count();
@endphp
<div
    x-data="{
        showStats: true,
        showFilters: false,
    }"
    @keydown.ctrl.n.window.prevent="$wire.newTask()"
    @keydown.enter.window.prevent="$wire.save()"
    @keydown.ctrl.s.window.prevent="$wire.save()"
    class="flex flex-col h-full bg-white text-neutral-950"
>
    {{-- ── TOP BAR ─────────────────────────────────────────────────── --}}
    <div class="bg-white border-b shrink-0 border-neutral-200">

        {{-- Row 1: toggle | title | actions --}}
        <div class="flex items-center h-12 gap-2 px-4">

            {{-- Sidebar secondary toggle --}}
            <button
                type="button"
                @click="secondaryOpen = !secondaryOpen"
                class="flex items-center justify-center transition rounded-md h-7 w-7 shrink-0 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700"
                title="Toggle panel"
            >
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect width="18" height="18" x="3" y="3" rx="2"/><path d="M9 3v18"/>
                </svg>
            </button>

            <div class="w-px h-4 bg-neutral-200 shrink-0"></div>

            <span class="text-sm font-semibold text-neutral-800">Tareas</span>
            <span class="text-xs text-neutral-400">{{ $totalTasks }} tareas</span>

            {{-- Actions --}}
            <div class="ml-auto flex items-center gap-1.5">
                {{-- Filtrar --}}
                <x-ui.button variant="outline" size="sm" @click="showFilters = !showFilters"
                    ::class="showFilters ? 'bg-accent' : ''">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                    @if($search || $projectFilter || $statusFilter || $priorityFilter)
                        <x-ui.badge class="ml-1 size-4 p-0 flex items-center justify-center text-[9px]">!</x-ui.badge>
                    @endif
                </x-ui.button>

                <x-ui.button variant="outline" size="sm" @click="$wire.newCategory()">
                    <x-lucide-tag class="size-3.5" />
                    Nueva Categoría
                </x-ui.button>

                <x-ui.button variant="outline" size="sm" @click="$wire.importFromClickUp()">
                    <x-lucide-download class="size-3.5" />
                    Importar ClickUp
                </x-ui.button>

                <x-ui.button size="sm" @click="$wire.newTask()">
                    <x-lucide-plus class="size-3.5" />
                    Nueva Tarea
                </x-ui.button>

                {{-- Stats toggle --}}
                <div class="h-4 w-px bg-neutral-200 mx-0.5"></div>
                <button
                    type="button"
                    @click="showStats = !showStats"
                    class="flex items-center justify-center transition rounded-md h-7 w-7 shrink-0 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-600"
                    :title="showStats ? 'Ocultar estadísticas' : 'Mostrar estadísticas'"
                >
                    <svg class="h-3.5 w-3.5 transition-transform duration-200" :class="showStats ? 'rotate-180' : ''"
                        viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <path d="m18 15-6-6-6 6"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Row 2: Stats (collapsible) --}}
        <div
            x-show="showStats"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="grid grid-cols-2 gap-3 px-4 py-3 border-t border-neutral-100 sm:grid-cols-4"
        >
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center bg-primary/10 text-primary size-11 shrink-0 rounded-xl">
                        <x-lucide-check-circle class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $completedTasks }}</div>
                        <div class="text-sm text-muted-foreground">Completadas</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center text-blue-500 bg-primary/10 text-primary size-11 shrink-0 rounded-xl bg-blue-50">
                        <x-lucide-clock class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $pendingTasks }}</div>
                        <div class="text-sm text-muted-foreground">Pendientes</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center text-purple-500 bg-primary/10 text-primary size-11 shrink-0 rounded-xl bg-purple-50">
                        <x-lucide-timer class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $inProgressTasks }}</div>
                        <div class="text-sm text-muted-foreground">En progreso</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center text-orange-600 bg-primary/10 text-primary size-11 shrink-0 rounded-xl bg-orange-50">
                        <x-lucide-list-todo class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $totalTasks }}</div>
                        <div class="text-sm text-muted-foreground">Total tareas</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
        </div>

        {{-- Row 3: Filters panel --}}
        <div
            x-show="showFilters"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            x-cloak
            class="px-4 py-3 border-t border-neutral-100 bg-neutral-50/50"
        >
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Título, descripción…">
                        <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                    </x-ui.input>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Proyecto</label>
                    <x-ui.select native size="sm" wire:model.live="projectFilter" class="w-full">
                        <option value="">Todos los proyectos</option>
                        @foreach($projects as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                    <x-ui.select native size="sm" wire:model.live="statusFilter" class="w-full">
                        <option value="">Todos los estados</option>
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En progreso</option>
                        <option value="completed">Completada</option>
                        <option value="cancelled">Cancelada</option>
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Prioridad</label>
                    <x-ui.select native size="sm" wire:model.live="priorityFilter" class="w-full">
                        <option value="">Todas las prioridades</option>
                        <option value="low">Baja</option>
                        <option value="medium">Media</option>
                        <option value="high">Alta</option>
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Tipo</label>
                    <x-ui.select native size="sm" wire:model.live="typeFilter" class="w-full">
                        <option value="">Todos los tipos</option>
                        <option value="general">General</option>
                        <option value="development">Desarrollo</option>
                        <option value="design">Diseño</option>
                        <option value="documentation">Documentación</option>
                        <option value="testing">Testing</option>
                        <option value="deployment">Despliegue</option>
                    </x-ui.select>
                </div>
                @if($search || $projectFilter || $statusFilter || $priorityFilter || $typeFilter)
                    <x-ui.button variant="ghost" size="sm" wire:click="clearFilters" class="gap-1 text-xs text-muted-foreground">
                        <x-lucide-x class="size-3" />
                        Limpiar
                    </x-ui.button>
                @endif
            </div>
        </div>
    </div>

    {{-- ── SCROLLABLE CONTENT ──────────────────────────────────────────── --}}
    <div class="flex-1 overflow-auto bg-[#f7f7f5]">
        <div class="flex flex-col flex-1 w-full gap-4 p-4 mx-auto md:gap-5 md:p-6 max-w-7xl">

            {{-- LISTADO TAREAS --}}
            <div class="flex flex-col gap-4">

                {{-- Tabla de tareas --}}
                <x-ui.card class="overflow-hidden bg-white border shadow-sm border-neutral-200 rounded-2xl">
                    <x-ui.card-content class="p-0">
                        <x-ui.table>
                            <x-ui.table-header>
                                <x-ui.table-row class="hover:bg-transparent">
                                    <x-ui.table-head class="w-8 pl-4">
                                        <div class="flex items-center justify-center">
                                            <input type="checkbox" class="rounded cursor-pointer border-input size-4" disabled />
                                        </div>
                                    </x-ui.table-head>
                                    <x-ui.table-head>Tarea</x-ui.table-head>
                                    <x-ui.table-head>Estado</x-ui.table-head>
                                    <x-ui.table-head>Prioridad</x-ui.table-head>
                                    <x-ui.table-head>Tipo</x-ui.table-head>
                                    <x-ui.table-head>Proyecto</x-ui.table-head>
                                    <x-ui.table-head>Fecha límite</x-ui.table-head>
                                    <x-ui.table-head class="w-35"></x-ui.table-head>
                                </x-ui.table-row>
                            </x-ui.table-header>
                            <x-ui.table-body>
                                @forelse($tasks as $task)
                                    <x-ui.table-row
                                        wire:key="task-{{ $task->id }}"
                                        class="transition-colors border-b group hover:bg-muted/50"
                                        @dblclick="$wire.editTask({{ $task->id }})"
                                    >
                                        <x-ui.table-cell class="pl-4">
                                            <input type="checkbox" class="rounded cursor-pointer border-input size-4"
                                                wire:click="toggleComplete({{ $task->id }})"
                                                @checked($task->is_completed) />
                                        </x-ui.table-cell>
                                        <x-ui.table-cell>
                                            <div class="font-medium text-sm {{ $task->is_completed ? 'line-through text-muted-foreground' : '' }}">{{ $task->title }}</div>
                                            @if($task->description)
                                                <div class="text-[11px] text-muted-foreground truncate max-w-xs">{{ $task->description }}</div>
                                            @endif
                                        </x-ui.table-cell>
                                        <x-ui.table-cell>
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-gray-100 text-gray-700',
                                                    'in_progress' => 'bg-blue-100 text-blue-700',
                                                    'completed' => 'bg-green-100 text-green-700',
                                                    'cancelled' => 'bg-red-100 text-red-700',
                                                ];
                                                $statusLabels = [
                                                    'pending' => 'Pendiente',
                                                    'in_progress' => 'En progreso',
                                                    'completed' => 'Completada',
                                                    'cancelled' => 'Cancelada',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-medium {{ $statusColors[$task->status] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $statusLabels[$task->status] ?? $task->status }}
                                            </span>
                                        </x-ui.table-cell>
                                        <x-ui.table-cell>
                                            @php
                                                $priorityColors = [
                                                    'low' => 'bg-green-100 text-green-700',
                                                    'medium' => 'bg-yellow-100 text-yellow-700',
                                                    'high' => 'bg-red-100 text-red-700',
                                                ];
                                                $priorityLabels = [
                                                    'low' => 'Baja',
                                                    'medium' => 'Media',
                                                    'high' => 'Alta',
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-medium {{ $priorityColors[$task->priority] ?? 'bg-gray-100 text-gray-700' }}">
                                                {{ $priorityLabels[$task->priority] ?? $task->priority }}
                                            </span>
                                        </x-ui.table-cell>
                                        <x-ui.table-cell class="text-sm text-muted-foreground">
                                            <span class="text-xs">{{ $task->type ?? 'general' }}</span>
                                        </x-ui.table-cell>
                                        <x-ui.table-cell class="text-sm text-muted-foreground">
                                            {{ optional($task->project)->name ?? '—' }}
                                        </x-ui.table-cell>
                                        <x-ui.table-cell class="text-sm text-muted-foreground">
                                            {{ optional($task->due_date)->format('d/m/Y') ?? '—' }}
                                        </x-ui.table-cell>
                                        <x-ui.table-cell class="pr-2 w-45">
                                            <div wire:ignore class="flex items-center gap-2 ml-auto">
                                                @if($task->clickup_task_id)
                                                    <span class="text-xs text-green-600 flex items-center gap-1">
                                                        <x-lucide-check class="size-3" />
                                                        ClickUp
                                                    </span>
                                                @else
                                                    <x-ui.button size="sm" variant="outline"
                                                        class="flex items-center justify-center transition-all rounded-md opacity-0 size-7 group-hover:opacity-100 hover:bg-accent text-muted-foreground hover:text-foreground"
                                                        wire:click="exportToClickUp({{ $task->id }})"
                                                        title="Exportar a ClickUp">
                                                        <x-lucide-upload class="size-4" />
                                                    </x-ui.button>
                                                @endif
                                                <x-ui.button size="sm" variant="outline" @click="$wire.editTask({{ $task->id }})"
                                                    class="flex items-center justify-center transition-all rounded-md opacity-0 size-7 group-hover:opacity-100 hover:bg-accent text-muted-foreground hover:text-foreground">
                                                    <x-lucide-pencil class="size-4" />
                                                </x-ui.button>
                                                <x-ui.button size="sm" variant="outline"
                                                    class="flex items-center justify-center transition-all rounded-md opacity-0 size-7 group-hover:opacity-100 hover:bg-accent text-muted-foreground hover:text-foreground"
                                                    wire:click="deleteTask({{ $task->id }})"
                                                    wire:confirm="¿Eliminar esta tarea?">
                                                    <x-lucide-trash-2 class="size-4" />
                                                </x-ui.button>
                                            </div>
                                        </x-ui.table-cell>
                                    </x-ui.table-row>
                                @empty
                                    <x-ui.table-row>
                                        <x-ui.table-cell colspan="7" class="py-12 text-center">
                                            <x-ui.empty class="p-0 border-0">
                                                <x-lucide-list-todo class="mx-auto mb-2 size-10 opacity-30" />
                                                <p class="text-sm font-medium text-muted-foreground">Sin tareas</p>
                                                <p class="text-xs text-muted-foreground opacity-60">Prueba a cambiar los filtros o crea una nueva tarea.</p>
                                            </x-ui.empty>
                                        </x-ui.table-cell>
                                    </x-ui.table-row>
                                @endforelse
                            </x-ui.table-body>
                        </x-ui.table>
                    </x-ui.card-content>
                </x-ui.card>

            </div>{{-- fin listado --}}

        </div>{{-- flex flex-1 flex-col content --}}

    {{-- EDITOR SLIDE-IN — BlatUI Sheet --}}
    <x-ui.sheet entangle="$wire.entangle('showEditor')" x-cloak>
        <x-ui.sheet-content
            side="right"
            :show-close="false"
            class="flex flex-col w-screen max-w-2xl gap-0 p-0 overflow-hidden"
        >
            {{-- Header BlatUI --}}
            <x-ui.sheet-header class="shrink-0 flex flex-row items-center justify-between px-4 py-2.5 border-b gap-0">
                <x-ui.sheet-title class="flex flex-wrap text-sm">
                    @if($editingId) Editar Tarea @else Nueva Tarea @endif
                </x-ui.sheet-title>
                <button type="button" @click="open = false"
                    class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            {{-- Formulario --}}
            <div class="flex-1 overflow-auto p-4">
                <div class="space-y-4">
                    {{-- Título --}}
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Título</label>
                        <x-ui.input wire:model="form.title" placeholder="Título de la tarea" />
                        @error('form.title') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    {{-- Descripción --}}
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Descripción</label>
                        <x-ui.textarea wire:model="form.description" placeholder="Descripción detallada..." rows="3" />
                        @error('form.description') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    {{-- Estado, Prioridad y Tipo --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                            <x-ui.select native wire:model="form.status">
                                <option value="pending">Pendiente</option>
                                <option value="in_progress">En progreso</option>
                                <option value="completed">Completada</option>
                                <option value="cancelled">Cancelada</option>
                            </x-ui.select>
                            @error('form.status') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Prioridad</label>
                            <x-ui.select native wire:model="form.priority">
                                <option value="low">Baja</option>
                                <option value="medium">Media</option>
                                <option value="high">Alta</option>
                            </x-ui.select>
                            @error('form.priority') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Tipo</label>
                            <x-ui.select native wire:model="form.type">
                                <option value="general">General</option>
                                <option value="development">Desarrollo</option>
                                <option value="design">Diseño</option>
                                <option value="documentation">Documentación</option>
                                <option value="testing">Testing</option>
                                <option value="deployment">Despliegue</option>
                            </x-ui.select>
                            @error('form.type') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Fecha límite, Proyecto y Categoría --}}
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Fecha límite</label>
                            <x-ui.date-picker wire:model="form.due_date" />
                            @error('form.due_date') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Proyecto</label>
                            <x-ui.select native wire:model="form.project_id">
                                <option value="">Sin proyecto</option>
                                @foreach($projects as $p)
                                    <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                                @endforeach
                            </x-ui.select>
                            @error('form.project_id') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Categoría</label>
                            <x-ui.select native wire:model="form.task_category_id">
                                <option value="">Sin categoría</option>
                                @foreach($taskCategories as $c)
                                    <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                                @endforeach
                            </x-ui.select>
                            @error('form.task_category_id') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="shrink-0 px-4 py-3 border-t bg-muted/40 flex items-center justify-end gap-2">
                <x-ui.button variant="outline" @click="open = false">
                    Cancelar
                </x-ui.button>
                <x-ui.button wire:click="save" wire:loading.attr="disabled">
                    Guardar
                </x-ui.button>
            </div>
        </x-ui.sheet-content>
    </x-ui.sheet>

    {{-- CATEGORY MODAL --}}
    <x-ui.dialog wire:model="showCategoryModal" x-cloak>
        <x-ui.dialog-content class="max-w-md">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Nueva Categoría</x-ui.dialog-title>
                <x-ui.dialog-description>Crea una nueva categoría para organizar tus tareas.</x-ui.dialog-description>
            </x-ui.dialog-header>
            <div class="space-y-4 py-4">
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Nombre</label>
                    <x-ui.input wire:model="categoryForm.name" placeholder="Nombre de la categoría" />
                    @error('categoryForm.name') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Slug</label>
                    <x-ui.input wire:model="categoryForm.slug" placeholder="slug-de-la-categoria" />
                    @error('categoryForm.slug') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Descripción</label>
                    <x-ui.textarea wire:model="categoryForm.description" placeholder="Descripción..." rows="2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Color</label>
                        <x-ui.input wire:model="categoryForm.color" type="color" class="h-10 w-20" />
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                        <x-ui.select native wire:model="categoryForm.status">
                            <option value="active">Activo</option>
                            <option value="archived">Archivado</option>
                        </x-ui.select>
                        @error('categoryForm.status') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>
            <x-ui.dialog-footer>
                <x-ui.button variant="outline" @click="open = false">Cancelar</x-ui.button>
                <x-ui.button wire:click="saveCategory">Crear</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
