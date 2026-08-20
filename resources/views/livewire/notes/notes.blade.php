@php
    $totalNotes = $notes->count();
    $pinnedCount = $pinnedNotes->count();
@endphp
<div
    x-data="{
        showFilters: false,
        selectedNote: null,
    }"
    @keydown.ctrl.n.window.prevent="$wire.newNote()"
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

            <span class="text-sm font-semibold text-neutral-800">Notas</span>
            <span class="text-xs text-neutral-400">{{ $totalNotes }} notas</span>

            {{-- Actions --}}
            <div class="ml-auto flex items-center gap-1.5">
                {{-- Filtrar --}}
                <x-ui.button variant="outline" size="sm" @click="showFilters = !showFilters"
                    ::class="showFilters ? 'bg-accent' : ''">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                    @if($search || $folderFilter || $projectFilter || $tagFilter)
                        <x-ui.badge class="ml-1 size-4 p-0 flex items-center justify-center text-[9px]">!</x-ui.badge>
                    @endif
                </x-ui.button>

                <x-ui.button variant="outline" size="sm" @click="$wire.newFolder()">
                    <x-lucide-folder-plus class="size-3.5" />
                    Nueva Carpeta
                </x-ui.button>

                <x-ui.button variant="outline" size="sm" @click="$wire.toggleFileManager()">
                    <x-lucide-folder-open class="size-3.5" />
                    Archivos
                </x-ui.button>

                <x-ui.button variant="outline" size="sm" @click="$wire.importFromClickUp()">
                    <x-lucide-download class="size-3.5" />
                    Importar ClickUp
                </x-ui.button>

                <x-ui.button size="sm" @click="$wire.newNote()">
                    <x-lucide-plus class="size-3.5" />
                    Nueva Nota
                </x-ui.button>
            </div>
        </div>

        {{-- Row 2: Filters panel --}}
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
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Título, contenido…">
                        <x-slot:leading><x-lucide-search class="size-3.5" /></x-slot:leading>
                    </x-ui.input>
                </div>
                <div class="w-44">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Carpeta</label>
                    <x-ui.select native size="sm" wire:model.live="folderFilter" class="w-full">
                        <option value="">Todas las carpetas</option>
                        @foreach($folders as $f)
                            <option value="{{ $f['id'] }}">{{ $f['name'] }}</option>
                        @endforeach
                    </x-ui.select>
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
                @if($search || $folderFilter || $projectFilter || $tagFilter)
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

            {{-- Pinned Notes --}}
            @if($pinnedNotes->isNotEmpty())
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Fijadas</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($pinnedNotes as $note)
                            @include('livewire.notes.partials.note-card', ['note' => $note])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Recent Notes --}}
            @if($recentNotes->isNotEmpty() && !$search && !$folderFilter && !$projectFilter)
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Recientes</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($recentNotes as $note)
                            @include('livewire.notes.partials.note-card', ['note' => $note])
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- All Notes (when filtering) --}}
            @if($search || $folderFilter || $projectFilter || $tagFilter)
                <div class="space-y-2">
                    <h3 class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">Resultados</h3>
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                        @foreach($notes as $note)
                            @include('livewire.notes.partials.note-card', ['note' => $note])
                        @endforeach
                    </div>
                </div>
            @endif

            @if($notes->isEmpty() && $pinnedNotes->isEmpty() && $recentNotes->isEmpty())
                <x-ui.card class="bg-white border shadow-sm border-neutral-200 rounded-2xl">
                    <x-ui.card-content class="py-12">
                        <x-ui.empty class="p-0 border-0">
                            <x-lucide-file-text class="mx-auto mb-2 size-10 opacity-30" />
                            <p class="text-sm font-medium text-muted-foreground">Sin notas</p>
                            <p class="text-xs text-muted-foreground opacity-60">Crea tu primera nota o añade una carpeta.</p>
                        </x-ui.empty>
                    </x-ui.card-content>
                </x-ui.card>
            @endif

        </div>{{-- flex flex-1 flex-col content --}}

    {{-- NOTE EDITOR SLIDE-IN --}}
    <x-ui.sheet entangle="$wire.entangle('showEditor')" x-cloak>
        <x-ui.sheet-content
            side="right"
            :show-close="false"
            class="flex flex-col w-screen max-w-4xl gap-0 p-0 overflow-hidden"
        >
            <x-ui.sheet-header class="shrink-0 flex flex-row items-center justify-between px-4 py-2.5 border-b gap-0">
                <x-ui.sheet-title class="flex flex-wrap text-sm">
                    @if($editingId) Editar Nota @else Nueva Nota @endif
                </x-ui.sheet-title>
                <button type="button" @click="open = false"
                    class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent transition-colors">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            <div class="flex-1 overflow-auto p-4">
                <div class="space-y-4">
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Título</label>
                        <x-ui.input wire:model="form.title" placeholder="Título de la nota" />
                        @error('form.title') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Carpeta</label>
                            <x-ui.select native wire:model="form.folder_id">
                                <option value="">Sin carpeta</option>
                                @foreach($folders as $f)
                                    <option value="{{ $f['id'] }}">{{ $f['name'] }}</option>
                                @endforeach
                            </x-ui.select>
                            @error('form.folder_id') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
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
                    </div>

                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Etiquetas (separadas por coma)</label>
                        <x-ui.input wire:model="form.tags" placeholder="etiqueta1, etiqueta2, ..." />
                    </div>

                    <div class="flex-1">
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Contenido (Markdown)</label>
                        <x-ui.textarea wire:model="form.content" placeholder="Escribe tu nota en formato Markdown..." rows="15" class="font-mono text-sm" />
                        @error('form.content') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
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

    {{-- FOLDER MODAL --}}
    <x-ui.dialog wire:model="showFolderModal" x-cloak>
        <x-ui.dialog-content class="max-w-md">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Nueva Carpeta</x-ui.dialog-title>
                <x-ui.dialog-description>Crea una nueva carpeta para organizar tue notas.</x-ui.dialog-description>
            </x-ui.dialog-header>
            <div class="space-y-4 py-4">
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Nombre</label>
                    <x-ui.input wire:model="folderForm.name" placeholder="Nombre de la carpeta" />
                    @error('folderForm.name') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Descripción</label>
                    <x-ui.textarea wire:model="folderForm.description" placeholder="Descripción..." rows="2" />
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Color</label>
                        <x-ui.input wire:model="folderForm.color" type="color" class="h-10 w-20" />
                    </div>
                    <div>
                        <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                        <x-ui.select native wire:model="folderForm.status">
                            <option value="active">Activo</option>
                            <option value="archived">Archivado</option>
                        </x-ui.select>
                        @error('folderForm.status') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Proyecto</label>
                    <x-ui.select native wire:model="folderForm.project_id">
                        <option value="">Sin proyecto</option>
                        @foreach($projects as $p)
                            <option value="{{ $p['id'] }}">{{ $p['name'] }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
            </div>
            <x-ui.dialog-footer>
                <x-ui.button variant="outline" @click="open = false">Cancelar</x-ui.button>
                <x-ui.button wire:click="saveFolder">Crear</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

    {{-- File Manager Modal --}}
    <x-ui.dialog wire:model="showFileManager" class="max-w-4xl">
        <x-ui.dialog-content>
            <x-ui.dialog-header>
                <x-ui.dialog-title>Gestor de Archivos</x-ui.dialog-title>
                <x-ui.dialog-description>
                    Gestiona archivos adjuntos para tus notas
                </x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="h-[500px]">
                <x-livewire-filemanager />
            </div>

            <x-ui.dialog-footer>
                <x-ui.button variant="outline" @click="$wire.toggleFileManager()">Cerrar</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
