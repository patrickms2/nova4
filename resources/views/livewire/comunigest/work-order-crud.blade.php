<div x-data="{ showFilters: false }" class="flex flex-col h-full bg-white text-neutral-950">
    {{-- TOP BAR --}}
    <div class="bg-white border-b shrink-0 border-neutral-200">
        <div class="flex items-center h-12 gap-2 px-4">
            <span class="text-sm font-semibold text-neutral-800">Órdenes de trabajo</span>
            <span class="text-xs text-neutral-400">{{ $workOrders->total() }} registros</span>
            <div class="ml-auto flex items-center gap-1.5">
                <x-ui.button size="sm" variant="outline" @click="showFilters = !showFilters">
                    <x-lucide-filter class="size-3.5" />
                    Filtrar
                </x-ui.button>
                <x-ui.button size="sm" wire:click="openNew">
                    <x-lucide-plus class="size-3.5" />
                    Nueva orden
                </x-ui.button>
            </div>
        </div>

        {{-- FILTERS --}}
        <div x-show="showFilters" x-transition x-cloak class="px-4 py-3 border-t border-neutral-100 bg-neutral-50/50">
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex-1 min-w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Buscar</label>
                    <x-ui.input size="sm" wire:model.live.debounce.300ms="search" placeholder="Código, solicitante o referencia..." />
                </div>
                <div class="w-40">
                    <label class="text-[10px] font-semibold text-muted-foreground uppercase tracking-wider mb-1 block">Estado</label>
                    <x-ui.select native size="sm" wire:model.live="statusFilter">
                        <option value="">Todos</option>
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En progreso</option>
                        <option value="finished">Finalizada</option>
                        <option value="cancelled">Cancelada</option>
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
                @if($search || $statusFilter || $communityFilter)
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
                                <x-ui.table-head>Código</x-ui.table-head>
                                <x-ui.table-head>Fecha</x-ui.table-head>
                                <x-ui.table-head>Comunidad</x-ui.table-head>
                                <x-ui.table-head>Estado</x-ui.table-head>
                                <x-ui.table-head>Resumen</x-ui.table-head>
                                <x-ui.table-head>Comentarios</x-ui.table-head>
                                <x-ui.table-head>Solicitante</x-ui.table-head>
                                <x-ui.table-head class="w-28"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($workOrders as $order)
                                <x-ui.table-row wire:key="order-{{ $order->id }}" class="group hover:bg-muted/50">
                                  
                                    <x-ui.table-cell>
                                        <span class="font-mono text-sm font-semibold">{{ $order->id }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell>
                                        <span class="font-mono text-sm font-semibold cursor-pointer hover:underline"  wire:click="toggle({{ $order->id }})" >{{ $order->code }}</span>
                                        
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm text-muted-foreground whitespace-nowrap">{{ $order->work_date->format('d/m/Y') }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">{{ $order->community?->name ?? '—' }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @php
                                            $badge = match($order->status) {
                                                'pending' => ['bg-amber-100', 'text-amber-700', 'Pendiente'],
                                                'in_progress' => ['bg-blue-100', 'text-blue-700', 'En progreso'],
                                                'finished' => ['bg-emerald-100', 'text-emerald-700', 'Finalizada'],
                                                'cancelled' => ['bg-slate-100', 'text-slate-600', 'Cancelada'],
                                                default => ['bg-slate-100', 'text-slate-600', $order->status],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full {{ $badge[0] }} px-1.5 py-0.5 text-[10px] font-medium {{ $badge[1] }}">{{ $badge[2] }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @php
                                            $completed = $order->tasks->where('status', 'completed')->count();
                                            $pending = $order->tasks->where('status', 'pending')->count();
                                        @endphp
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">
                                                <x-lucide-check class="size-3" /> {{ $completed }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">
                                                <x-lucide-circle class="size-3" /> {{ $pending }}
                                            </span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 px-1.5 py-0.5 text-[10px] font-medium text-rose-700">
                                                <x-lucide-alert-triangle class="size-3" /> {{ $order->incidents->count() }}
                                            </span>
                                        </div>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        <x-ui.button type="button" size="sm" variant="outline" wire:click="openComments({{ $order->id }})" class="size-7 p-0" title="Ver comentarios">
                                            @if($order->comments_count > 0)
                                                <x-lucide-message-circle class="size-4" />
                                                <span class="sr-only">{{ $order->comments_count }}</span>
                                            @else
                                                <x-lucide-plus class="size-4" />
                                            @endif
                                        </x-ui.button>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm text-muted-foreground">{{ $order->requester_name ?? '—' }}</x-ui.table-cell>
                                    <x-ui.table-cell class="pr-2">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.button size="sm" variant="ghost" wire:click="toggle({{ $order->id }})" class="size-7 p-0">
                                                @if(in_array($order->id, $expanded, true))
                                                    <x-lucide-chevron-up class="size-4" />
                                                @else
                                                    <x-lucide-chevron-down class="size-4" />
                                                @endif
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="outline" href="{{ route('comunigest.admin.work-order-tasks', $order) }}" class="opacity-0 group-hover:opacity-100 size-7 p-0">
                                                <x-lucide-list-checks class="size-4" />
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="outline" wire:click="openEdit({{ $order->id }})" class="opacity-0 group-hover:opacity-100 size-7 p-0">
                                                <x-lucide-pencil class="size-4" />
                                            </x-ui.button>
                                            <x-ui.button size="sm" variant="outline" wire:click="delete({{ $order->id }})" wire:confirm="¿Eliminar orden?" class="opacity-0 group-hover:opacity-100 size-7 p-0 text-destructive">
                                                <x-lucide-trash-2 class="size-4" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>

                                @if(in_array($order->id, $expanded, true))
                                    <x-ui.table-row wire:key="order-{{ $order->id }}-details" class="bg-neutral-50/50">
                                        <x-ui.table-cell colspan="7" class="p-0">
                                            <div class="p-4 space-y-4">
                                                <div style="
    max-width: 600px;
    text-align: center;
    margin: auto;
">
                                                    @if($order->tasks->isEmpty())
                                                        <div class="flex items-center justify-between mb-2">
                                                            <p class="text-sm text-muted-foreground">Sin tareas</p>
                                                        </div>
                                                    @else
                                                        <div class="flex items-center justify-between mb-2">
                                                            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Tareas</h4>
                                                            <a href="{{ route('comunigest.admin.work-order-tasks', $order) }}" class="text-xs text-blue-600 hover:underline">Gestionar tareas</a>
                                                        </div>
                                                        <ul class="divide-y divide-neutral-200">
                                                            @foreach($order->tasks as $task)
                                                                <li class="py-2 flex items-center justify-between">
                                                                    <div>
                                                                        <p class="text-sm font-medium">{{ $task->title }}</p>
                                                                        <p class="text-[10px] text-muted-foreground">{{ $task->priority }} · {{ $task->status }}</p>
                                                                    </div>
                                                                    @if($task->status === 'completed')
                                                                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700">Hecha</span>
                                                                    @elseif($task->status === 'pending')
                                                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">Pendiente</span>
                                                                    @else
                                                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ $task->status }}</span>
                                                                    @endif
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                                
                                                <div style="
    max-width: 600px;
    text-align: center;
    margin: auto;
">
                                                    @if($order->incidents->isEmpty())
                                                       <div class="flex items-center justify-between mb-2">
                                                            <p class="text-sm text-muted-foreground">Sin incidencias</p>
                                                        </div>
                                                    @else
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Incidencias</h4>
                                                        <a href="{{ route('comunigest.admin.incidents', $order) }}" class="text-xs text-blue-600 hover:underline">Gestionar incidencias</a>
                                                    </div>
                                                        <ul class="divide-y divide-neutral-200">
                                                            @foreach($order->incidents as $incident)
                                                                <li class="py-2 flex items-center justify-between">
                                                                    <div>
                                                                        <p class="text-sm font-medium">{{ $incident->title ?? 'Incidencia' }}</p>
                                                                        <p class="text-[10px] text-muted-foreground">{{ $incident->priority }}</p>
                                                                    </div>
                                                                    <span class="inline-flex items-center rounded-full {{ $incident->status === 'open' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700' }} px-1.5 py-0.5 text-[10px] font-medium">{{ $incident->status === 'open' ? 'Abierta' : 'Cerrada' }}</span>
                                                                </li>
                                                            @endforeach
                                                        </ul>
                                                    @endif
                                                </div>
                                            </div>
                                        </x-ui.table-cell>
                                    </x-ui.table-row>
                                @endif
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="8" class="py-12 text-center">
                                        <x-ui.empty class="p-0 border-0">
                                            <x-lucide-clipboard-list class="mx-auto mb-2 size-10 opacity-30" />
                                            <p class="text-sm font-medium text-muted-foreground">Sin órdenes</p>
                                        </x-ui.empty>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>

            <div class="mt-2">
                {{ $workOrders->links() }}
            </div>
        </div>
    </div>

    {{-- SHEET --}}
    <x-ui.sheet entangle="$wire.entangle('showForm')" x-cloak>
        <x-ui.sheet-content side="right" :show-close="false" class="flex flex-col w-screen max-w-2xl gap-0 p-0 overflow-hidden">
            <x-ui.sheet-header class="shrink-0 flex items-center justify-between px-4 py-2.5 border-b">
                <x-ui.sheet-title class="text-sm">
                    {{ $workOrderId ? 'Editar orden' : 'Nueva orden' }}
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
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Fecha</label>
                        <x-ui.input size="sm" type="date" wire:model="workDate" />
                        @error('workDate') <span class="text-[10px] text-destructive">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Estado</label>
                        <x-ui.select native size="sm" wire:model="status">
                            <option value="pending">Pendiente</option>
                            <option value="in_progress">En progreso</option>
                            <option value="finished">Finalizada</option>
                            <option value="cancelled">Cancelada</option>
                        </x-ui.select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Solicitante</label>
                        <x-ui.input size="sm" wire:model="requesterName" placeholder="Nombre solicitante" />
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Teléfono</label>
                        <x-ui.input size="sm" wire:model="requesterPhone" placeholder="600 000 000" />
                    </div>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1 block">Referencia</label>
                    <x-ui.input size="sm" wire:model="reference" placeholder="Referencia externa" />
                </div>
            </div>

            <x-ui.sheet-footer class="flex-row justify-between gap-2 px-4 py-2 border-t shrink-0">
                @if($workOrderId)
                    <x-ui.button type="button" variant="secondary" wire:click="delete({{ $workOrderId }})" wire:confirm="¿Eliminar orden?" class="text-destructive">
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

    {{-- Comments modal --}}
    <x-ui.dialog wire:model="showComments">
        <x-ui.dialog-content class="sm:max-w-xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Comentarios de {{ $selectedOrderForComments?->code ?? 'orden' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $selectedOrderForComments?->community?->name ?? '—' }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 space-y-3 max-h-[60vh] overflow-y-auto">
                <div class="flex flex-col gap-2">
                    <textarea wire:model="newComment" rows="2" placeholder="Añadir comentario..." class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E60000] focus:border-transparent resize-none"></textarea>
                    @error('newComment') <p class="text-[10px] text-destructive">{{ $message }}</p> @enderror
                    <x-ui.button type="button" size="sm" wire:click="saveComment" class="self-start">
                        <x-lucide-send class="size-3.5 mr-1" /> Comentar
                    </x-ui.button>
                </div>

                @if($selectedOrderForComments && $selectedOrderForComments->comments->isNotEmpty())
                    @foreach($selectedOrderForComments->comments as $comment)
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
