<div x-data="{ showFilters: false }" class="flex flex-col h-full bg-white text-neutral-950">
    {{-- TOP BAR --}}
    <div class="bg-white border-b shrink-0 border-neutral-200">
        <div class="flex items-center h-12 gap-2 px-4">
            <span class="text-sm font-semibold text-neutral-800">Incidencias</span>
            <span class="text-xs text-neutral-400">{{ $incidents->total() }} registros</span>
            <div class="ml-auto flex items-center gap-1.5">
                <x-ui.button size="sm" variant="outline" @click="showFilters = !showFilters">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                </x-ui.button>
                <x-ui.button size="sm" wire:click="openNew">
                    <x-lucide-plus class="size-3.5" />
                    Nueva incidencia
                </x-ui.button>
            </div>
        </div>

        {{-- FILTERS --}}
        <div x-show="showFilters" x-transition x-cloak class="px-4 py-3 border-t border-neutral-100 bg-neutral-50/50">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Título o descripción..." />
                </div>
                <div class="w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                    <x-ui.select native size="sm" wire:model.live="statusFilter">
                        <option value="">Todos</option>
                        <option value="open">Abierta</option>
                        <option value="assigned">Asignada</option>
                        <option value="communicated">Comunicada</option>
                        <option value="resolved">Resuelta</option>
                        <option value="closed">Cerrada</option>
                    </x-ui.select>
                </div>
                <div class="w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Prioridad</label>
                    <x-ui.select native size="sm" wire:model.live="priorityFilter">
                        <option value="">Todas</option>
                        <option value="low">Baja</option>
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </x-ui.select>
                </div>
                <div class="w-56">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Comunidad</label>
                    <x-ui.select native size="sm" wire:model.live="communityFilter">
                        <option value="">Todas</option>
                        @foreach ($communities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                </div>
                @if($search || $statusFilter || $priorityFilter || $communityFilter)
                    <x-ui.button size="sm" variant="ghost" wire:click="clearFilters" class="text-muted-foreground">
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
                                <x-ui.table-head>ID</x-ui.table-head>
                                <x-ui.table-head>Título</x-ui.table-head>
                                <x-ui.table-head>Comunidad</x-ui.table-head>
                                <x-ui.table-head>Prioridad</x-ui.table-head>
                                <x-ui.table-head>Estado</x-ui.table-head>
                                <x-ui.table-head>Orden</x-ui.table-head>
                                <x-ui.table-head>Fotos</x-ui.table-head>
                                <x-ui.table-head>Comentarios</x-ui.table-head>
                                <x-ui.table-head class="w-20"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($incidents as $incident)
                                <x-ui.table-row wire:key="incident-{{ $incident->id }}" class="group hover:bg-muted/50">
                                    <x-ui.table-cell>
                                        <span class="font-medium text-sm">{{ $incident->id }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <span class="font-medium text-sm">{{ $incident->title }}</span>
                                        @if($incident->description)
                                            <div class="text-[11px] text-muted-foreground truncate max-w-xs">{{ $incident->description }}</div>
                                        @endif
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">{{ $incident->community->name }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @php
                                            $p = match($incident->priority) {
                                                'low' => ['bg-slate-100', 'text-slate-600', 'Baja'],
                                                'normal' => ['bg-blue-100', 'text-blue-700', 'Normal'],
                                                'high' => ['bg-amber-100', 'text-amber-700', 'Alta'],
                                                'urgent' => ['bg-red-100', 'text-red-700', 'Urgente'],
                                                default => ['bg-slate-100', 'text-slate-600', $incident->priority],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full {{ $p[0] }} px-1.5 py-0.5 text-[10px] font-medium {{ $p[1] }}">{{ $p[2] }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @php
                                            $s = match($incident->status) {
                                                'open' => ['bg-amber-100', 'text-amber-700', 'Abierta'],
                                                'assigned' => ['bg-blue-100', 'text-blue-700', 'Asignada'],
                                                'communicated' => ['bg-purple-100', 'text-purple-700', 'Comunicada'],
                                                'resolved' => ['bg-emerald-100', 'text-emerald-700', 'Resuelta'],
                                                'closed' => ['bg-slate-100', 'text-slate-600', 'Cerrada'],
                                                default => ['bg-slate-100', 'text-slate-600', $incident->status],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full {{ $s[0] }} px-1.5 py-0.5 text-[10px] font-medium {{ $s[1] }}">{{ $s[2] }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm text-muted-foreground">{{ $incident->workOrder?->code ?? '—' }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        <x-ui.button type="button" size="sm" variant="outline" wire:click="openPhotos({{ $incident->id }})" class="size-7 p-0" :disabled="$incident->photos_count === 0" title="Ver fotos">
                                            <x-lucide-image class="size-4" />
                                        </x-ui.button>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        <x-ui.button type="button" size="sm" variant="outline" wire:click="openComments({{ $incident->id }})" class="size-7 p-0" title="Ver comentarios">
                                            @if($incident->comments_count === 0)
                    <x-lucide-plus class="size-4" />
                                                @else
                                            <x-lucide-message-circle class="size-4" :disabled="$incident->comments_count === 0" />
                                            @endif
                                        </x-ui.button>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="pr-2">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button size="sm" variant="outline" wire:click="openEdit({{ $incident->id }})" class="opacity-0 group-hover:opacity-100 size-7 p-0">
                                                <x-lucide-pencil class="size-4" />
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="outline" wire:click="delete({{ $incident->id }})" wire:confirm="¿Eliminar incidencia?" class="opacity-0 group-hover:opacity-100 size-7 p-0 text-destructive">
                                                <x-lucide-trash-2 class="size-4" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="9" class="py-12 text-center">
                                        <x-ui.empty class="p-0 border-0">
                                            <x-lucide-alert-triangle class="mx-auto mb-2 size-10 opacity-30" />
                                            <p class="text-sm font-medium text-muted-foreground">Sin incidencias</p>
                                        </x-ui.empty>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>

            <div class="mt-2">
                {{ $incidents->links() }}
            </div>
        </div>
    </div>

    {{-- SHEET --}}
    <x-ui.sheet entangle="$wire.entangle('showForm')" x-cloak>
        <x-ui.sheet-content side="right" :show-close="false" class="flex flex-col w-screen max-w-2xl gap-0 p-0 overflow-hidden">
            <x-ui.sheet-header class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b">
                <x-ui.sheet-title class="text-sm">
                    {{ $incidentId ? 'Editar incidencia' : 'Nueva incidencia' }}
                </x-ui.sheet-title>
                <button type="button" wire:click="closeForm" class="rounded-md p-1.5 text-muted-foreground hover:text-foreground hover:bg-accent">
                    <x-lucide-x class="size-4" />
                </button>
            </x-ui.sheet-header>

            <div class="flex-1 overflow-y-auto px-4 py-4 space-y-3.5 text-xs">
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Comunidad</label>
                    <x-ui.select native size="sm" wire:model="communityId">
                        <option value="">—</option>
                        @foreach ($communities as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-ui.select>
                    @error('communityId') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Orden</label>
                        <x-ui.select native size="sm" wire:model.live="workOrderId">
                            <option value="">—</option>
                            @foreach ($workOrders as $id => $code)
                                <option value="{{ $id }}">{{ $code }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Tarea</label>
                        <x-ui.select native size="sm" wire:model="workOrderTaskId">
                            <option value="">—</option>
                            @foreach ($tasks as $id => $title)
                                <option value="{{ $id }}">{{ $title }}</option>
                            @endforeach
                        </x-ui.select>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Título</label>
                    <x-ui.input size="sm" wire:model="title" placeholder="Resumen de la incidencia" />
                    @error('title') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Descripción</label>
                    <textarea wire:model="description" rows="3" class="w-full px-3 py-2 text-xs transition-all border rounded-xl border-slate-200 bg-slate-50/50 text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 focus:border-indigo-500"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Prioridad</label>
                        <x-ui.select native size="sm" wire:model="priority">
                            <option value="low">Baja</option>
                            <option value="normal">Normal</option>
                            <option value="high">Alta</option>
                            <option value="urgent">Urgente</option>
                        </x-ui.select>
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Estado</label>
                        <x-ui.select native size="sm" wire:model="status">
                            <option value="open">Abierta</option>
                            <option value="assigned">Asignada</option>
                            <option value="communicated">Comunicada</option>
                            <option value="resolved">Resuelta</option>
                            <option value="closed">Cerrada</option>
                        </x-ui.select>
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Nota de resolución</label>
                    <x-ui.input size="sm" wire:model="resolutionNote" placeholder="Nota de resolución" />
                </div>
            </div>

            <x-ui.sheet-footer class="flex-row justify-between gap-2 px-4 py-2 border-t shrink-0">
                @if($incidentId)
                    <x-ui.button type="button" variant="secondary" wire:click="delete({{ $incidentId }})" wire:confirm="¿Eliminar incidencia?" class="text-destructive">
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

    {{-- Photos modal --}}
    <x-ui.dialog wire:model="showPhotos">
        <x-ui.dialog-content class="sm:max-w-xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Fotos de {{ $selectedIncident?->title ?? 'incidencia' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $selectedIncident?->community?->name }} — {{ $selectedIncident?->workOrder?->code ?? 'Sin orden' }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 grid grid-cols-3 gap-2 max-h-[60vh] overflow-y-auto">
                @if($selectedIncident && $selectedIncident->photos->isNotEmpty())
                    @foreach($selectedIncident->photos as $photo)
                        <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" class="block rounded-lg overflow-hidden border hover:opacity-80">
                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $photo->filename }}" class="w-full h-24 object-cover" />
                        </a>
                    @endforeach
                @else
                    <p class="col-span-3 text-sm text-muted-foreground text-center py-6">No hay fotos</p>
                @endif
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" size="sm" variant="outline" wire:click="closePhotos">Cerrar</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

    {{-- Comments modal --}}
    <x-ui.dialog wire:model="showComments">
        <x-ui.dialog-content class="sm:max-w-xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Comentarios de {{ $selectedIncidentForComments?->title ?? 'incidencia' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $selectedIncidentForComments?->community?->name }} — {{ $selectedIncidentForComments?->workOrder?->code ?? 'Sin orden' }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 space-y-3 max-h-[60vh] overflow-y-auto">
                <div class="flex flex-col gap-2">
                    <textarea wire:model="newComment" rows="2" placeholder="Añadir comentario..." class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E60000] focus:border-transparent resize-none"></textarea>
                    @error('newComment') <p class="text-[10px] text-destructive">{{ $message }}</p> @enderror
                    <x-ui.button type="button" size="sm" wire:click="saveComment" class="self-start">
                        <x-lucide-send class="size-3.5 mr-1" /> Comentar
                    </x-ui.button>
                </div>

                @if($selectedIncidentForComments && $selectedIncidentForComments->comments->isNotEmpty())
                    @foreach($selectedIncidentForComments->comments as $comment)
                        <div class="border {{ $comment->is_read ? 'border-neutral-200' : 'border-rose-400 bg-rose-50' }} rounded-lg p-3">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-semibold">{{ $comment->user?->name ?? 'Sistema' }}</span>
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-muted-foreground">{{ $comment->created_at?->format('d/m/Y H:i') }}</span>
                                    <button type="button" wire:click="toggleRead({{ $comment->id }})" class="text-muted-foreground hover:text-[#E60000]" title="{{ $comment->is_read ? 'Marcar no visto' : 'Marcar visto' }}">
                                        @if($comment->is_read)
                                            <x-lucide-eye class="size-4 text-emerald-600" />
                                        @else
                                            <x-lucide-eye-off class="size-4 text-rose-600" />
                                        @endif
                                    </button>
                                </div>
                            </div>
                            <p class="text-sm text-muted-foreground whitespace-pre-wrap">{{ $comment->body }}</p>
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-muted-foreground text-center py-6">No hay comentarios</p>
                @endif
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" size="sm" variant="outline" wire:click="closeComments">Cerrar</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
