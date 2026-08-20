@php
    $totalProjects = $projects->count();
    $activeProjects = $projects->where('status', 'active')->count();
    $completedProjects = $projects->where('phase', 'completed')->count();
    $inProgressProjects = $projects->whereIn('phase', ['planning', 'development', 'testing'])->count();
@endphp
<div
    x-data="{
        showStats: true,
        showFilters: false,
        expandedProjects: {},
    }"
    @keydown.ctrl.n.window.prevent="$wire.newProject()"
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

            <span class="text-sm font-semibold text-neutral-800">Proyectos</span>
            <span class="text-xs text-neutral-400">{{ $totalProjects }} proyectos</span>

            {{-- Actions --}}
            <div class="ml-auto flex items-center gap-1.5">
                {{-- Filtrar --}}
                <x-ui.button variant="outline" size="sm" @click="showFilters = !showFilters"
                    ::class="showFilters ? 'bg-accent' : ''">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                    @if($search || $categoryFilter || $phaseFilter || $statusFilter)
                        <x-ui.badge class="ml-1 size-4 p-0 flex items-center justify-center text-[9px]">!</x-ui.badge>
                    @endif
                </x-ui.button>

                <x-ui.button variant="outline" size="sm" @click="$wire.newCategory()">
                    <x-lucide-folder-plus class="size-3.5" />
                    Nueva Categoría
                </x-ui.button>

                <x-ui.button size="sm" @click="$wire.newProject()">
                    <x-lucide-plus class="size-3.5" />
                    Nuevo Proyecto
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
                        <x-lucide-folder class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $activeProjects }}</div>
                        <div class="text-sm text-muted-foreground">Activos</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center text-green-600 bg-primary/10 text-primary size-11 shrink-0 rounded-xl bg-green-50">
                        <x-lucide-check-circle class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $completedProjects }}</div>
                        <div class="text-sm text-muted-foreground">Completados</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center text-blue-500 bg-primary/10 text-primary size-11 shrink-0 rounded-xl bg-blue-50">
                        <x-lucide-timer class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $inProgressProjects }}</div>
                        <div class="text-sm text-muted-foreground">En progreso</div>
                    </div>
                </x-ui.card-content>
            </x-ui.card>
            <x-ui.card variant="sectioned">
                <x-ui.card-content class="flex items-center gap-3 px-4 py-3 border rounded-xl border-neutral-100 bg-neutral-50">
                    <div class="flex items-center justify-center text-purple-600 bg-primary/10 text-primary size-11 shrink-0 rounded-xl bg-purple-50">
                        <x-lucide-layers class="size-5" />
                    </div>
                    <div>
                        <div class="text-2xl font-semibold leading-tight tabular-nums">{{ $totalProjects }}</div>
                        <div class="text-sm text-muted-foreground">Total</div>
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
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Nombre, descripción…">
                        <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                    </x-ui.input>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Categoría</label>
                    <x-ui.select native size="sm" wire:model.live="categoryFilter" class="w-full">
                        <option value="">Todas las categorías</option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Fase</label>
                    <x-ui.select native size="sm" wire:model.live="phaseFilter" class="w-full">
                        <option value="">Todas las fases</option>
                        <option value="planning">Planificación</option>
                        <option value="development">Desarrollo</option>
                        <option value="testing">Testing</option>
                        <option value="deployment">Despliegue</option>
                        <option value="completed">Completado</option>
                    </x-ui.select>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                    <x-ui.select native size="sm" wire:model.live="statusFilter" class="w-full">
                        <option value="">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="archived">Archivado</option>
                    </x-ui.select>
                </div>
                @if($search || $categoryFilter || $phaseFilter || $statusFilter)
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

            {{-- LISTADO PROYECTOS --}}
            <div class="flex flex-col gap-4">

                {{-- Categorías con proyectos --}}
                @foreach($categories as $category)
                    @php
                        $categoryProjects = $projects->where('project_category_id', $category->id);
                        if($categoryProjects->isEmpty()) continue;
                    @endphp
                    <x-ui.card class="overflow-hidden bg-white border shadow-sm border-neutral-200 rounded-2xl">
                        <x-ui.card-header class="flex items-center gap-3 px-4 py-3 border-b border-neutral-100">
                            <div class="flex items-center justify-center size-8 rounded-lg" style="background-color: {{ $category->color }}20; color: {{ $category->color }}">
                                @if($category->icon)
                                    <span>{!! $category->icon !!}</span>
                                @else
                                    <x-lucide-folder class="size-4" />
                                @endif
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm">{{ $category->name }}</h3>
                                <p class="text-xs text-muted-foreground">{{ $categoryProjects->count() }} proyectos</p>
                            </div>
                        </x-ui.card-header>
                        <x-ui.card-content class="p-0">
                            @foreach($categoryProjects as $project)
                                @include('livewire.projects.partials.project-item', ['project' => $project, 'level' => 0])
                            @endforeach
                        </x-ui.card-content>
                    </x-ui.card>
                @endforeach

                {{-- Proyectos sin categoría --}}
                @php
                    $uncategorizedProjects = $projects->where('project_category_id', null);
                @endphp
                @if($uncategorizedProjects->isNotEmpty())
                    <x-ui.card class="overflow-hidden bg-white border shadow-sm border-neutral-200 rounded-2xl">
                        <x-ui.card-header class="flex items-center gap-3 px-4 py-3 border-b border-neutral-100">
                            <div class="flex items-center justify-center size-8 rounded-lg bg-neutral-100 text-neutral-600">
                                <x-lucide-folder class="size-4" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-sm">Sin categoría</h3>
                                <p class="text-xs text-muted-foreground">{{ $uncategorizedProjects->count() }} proyectos</p>
                            </div>
                        </x-ui.card-header>
                        <x-ui.card-content class="p-0">
                            @foreach($uncategorizedProjects as $project)
                                @include('livewire.projects.partials.project-item', ['project' => $project, 'level' => 0])
                            @endforeach
                        </x-ui.card-content>
                    </x-ui.card>
                @endif

                @if($projects->isEmpty())
                    <x-ui.card class="bg-white border shadow-sm border-neutral-200 rounded-2xl">
                        <x-ui.card-content class="py-12">
                            <x-ui.empty class="p-0 border-0">
                                <x-lucide-folder class="mx-auto mb-2 size-10 opacity-30" />
                                <p class="text-sm font-medium text-muted-foreground">Sin proyectos</p>
                                <p class="text-xs text-muted-foreground opacity-60">Crea tu primer proyecto o añade una categoría.</p>
                            </x-ui.empty>
                        </x-ui.card-content>
                    </x-ui.card>
                @endif

            </div>{{-- fin listado --}}

        </div>{{-- flex flex-1 flex-col content --}}

    {{-- PROJECT EDITOR SLIDE-IN --}}
    <x-ui.sheet entangle="$wire.entangle('showEditor')" x-cloak>
        <x-ui.sheet-content
            side="right"
            :show-close="false"
            class="flex flex-col w-screen max-w-2xl gap-0 p-0 overflow-hidden"
        >
            <x-ui.sheet-header class="shrink-0 flex flex-row items-center justify-between px-4 py-2.5 border-b gap-0">
                <x-ui.sheet-title class="flex flex-wrap text-sm">
                    @if($editingId) Editar Proyecto @else Nuevo Proyecto @endif
                </x-ui.sheet-title>
                <button type="button" @click="open = false"
                    class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            <div class="flex-1 overflow-auto p-4">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Nombre</label>
                        <x-ui.input wire:model="form.name" placeholder="Nombre del proyecto" />
                        @error('form.name') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Descripción</label>
                        <x-ui.textarea wire:model="form.description" placeholder="Descripción detallada..." rows="3" />
                        @error('form.description') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Fase</label>
                            <x-ui.select native wire:model="form.phase">
                                <option value="planning">Planificación</option>
                                <option value="development">Desarrollo</option>
                                <option value="testing">Testing</option>
                                <option value="deployment">Despliegue</option>
                                <option value="completed">Completado</option>
                            </x-ui.select>
                            @error('form.phase') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1">Estado</label>
                            <x-ui.select native wire:model="form.status">
                                <option value="active">Activo</option>
                                <option value="archived">Archivado</option>
                            </x-ui.select>
                            @error('form.status') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Fecha inicio</label>
                            <x-ui.date-picker wire:model="form.start_date" />
                            @error('form.start_date') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Fecha fin</label>
                            <x-ui.date-picker wire:model="form.end_date" />
                            @error('form.end_date') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Color</label>
                        <x-ui.input wire:model="form.color" type="color" class="h-10 w-20" />
                    </div>

                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Categoría</label>
                        <x-ui.select native wire:model="form.project_category_id">
                            <option value="">Sin categoría</option>
                            @foreach($categories as $c)
                                <option value="{{ $c['id'] }}">{{ $c['name'] }}</option>
                            @endforeach
                        </x-ui.select>
                        @error('form.project_category_id') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.switch wire:model="form.is_public" id="is_public" />
                        <label for="is_public" class="text-sm">Proyecto público</label>
                    </div>
                </div>
            </div>

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
                <x-ui.dialog-description>Crea una nueva categoría para organizar tus proyectos.</x-ui.dialog-description>
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
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Color</label>
                    <x-ui.input wire:model="categoryForm.color" type="color" class="h-10 w-20" />
                </div>
            </div>
            <x-ui.dialog-footer>
                <x-ui.button variant="outline" @click="open = false">Cancelar</x-ui.button>
                <x-ui.button wire:click="saveCategory">Crear</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
