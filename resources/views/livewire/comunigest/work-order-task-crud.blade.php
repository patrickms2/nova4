@php
    $tasks = $workOrder->tasks;
    $taskCount = $workOrder->tasks_count;
@endphp
<div x-data="{ showFilters: false }" class="flex flex-col h-full bg-white text-neutral-950">

    {{-- TOP BAR --}}
    <div class="bg-white border-b shrink-0 border-neutral-200">
        <div class="flex items-center h-12 gap-2 px-4">
            <a href="{{ route('comunigest.admin.work-orders') }}" class="text-sm text-muted-foreground hover:text-foreground">← Volver a órdenes</a>
            <span class="text-sm font-semibold text-neutral-800">Tareas de {{ $workOrder->code }}</span>
            <span class="text-xs text-neutral-400">{{ $taskCount }} tareas</span>
            <div class="ml-auto flex items-center gap-1.5">
                <x-ui.dialog-trigger for="task-modal">
                    <x-ui.button type="button" size="sm" wire:click="resetForm">
                        <x-lucide-plus class="size-3.5" />
                        Nueva tarea
                    </x-ui.button>
                </x-ui.dialog-trigger>
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
                                <x-ui.table-head>Orden</x-ui.table-head>
                                <x-ui.table-head>Título</x-ui.table-head>
                                <x-ui.table-head>Prioridad</x-ui.table-head>
                                <x-ui.table-head>Estado</x-ui.table-head>
                                <x-ui.table-head>Resultado</x-ui.table-head>
                                <x-ui.table-head>Comentarios</x-ui.table-head>
                                <x-ui.table-head class="w-28"></x-ui.table-head>
                            </x-ui.table-row>
                        </x-ui.table-header>
                        <x-ui.table-body>
                            @forelse($tasks as $task)
                                <x-ui.table-row wire:key="task-{{ $task->id }}" class="group hover:bg-muted/50">
                           
                                    <x-ui.table-cell>
                                        <span class="font-medium text-sm">{{ $task->id }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="font-mono text-sm font-semibold">{{ $task->sort }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">{{ $task->title }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @php
                                            $priorityBadge = match($task->priority) {
                                                'low' => ['bg-slate-100', 'text-slate-600', 'Baja'],
                                                'normal' => ['bg-blue-100', 'text-blue-700', 'Normal'],
                                                'high' => ['bg-amber-100', 'text-amber-700', 'Alta'],
                                                'urgent' => ['bg-rose-100', 'text-rose-700', 'Urgente'],
                                                default => ['bg-slate-100', 'text-slate-600', $task->priority],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full {{ $priorityBadge[0] }} px-1.5 py-0.5 text-[10px] font-medium {{ $priorityBadge[1] }}">{{ $priorityBadge[2] }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        @php
                                            $badge = match($task->status) {
                                                'pending' => ['bg-amber-100', 'text-amber-700', 'Pendiente'],
                                                'completed' => ['bg-emerald-100', 'text-emerald-700', 'Completada'],
                                                'not_done' => ['bg-rose-100', 'text-rose-700', 'No realizada'],
                                                'cancelled' => ['bg-slate-100', 'text-slate-600', 'Cancelada'],
                                                default => ['bg-slate-100', 'text-slate-600', $task->status],
                                            };
                                        @endphp
                                        <span class="inline-flex items-center rounded-full {{ $badge[0] }} px-1.5 py-0.5 text-[10px] font-medium {{ $badge[1] }}">{{ $badge[2] }}</span>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm text-muted-foreground">{{ $task->result ?? '—' }}</x-ui.table-cell>
                                    <x-ui.table-cell class="text-sm">
                                        <x-ui.button type="button" size="sm" variant="outline" wire:click="openComments({{ $task->id }})" class="size-7 p-0" :disabled="$task->comments_count === 0" title="Ver comentarios">
                                            <x-lucide-message-circle class="size-4" />
                                        </x-ui.button>
                                    </x-ui.table-cell>
                                    <x-ui.table-cell class="pr-2">
                                        <div class="flex items-center justify-end gap-1">
                                            <x-ui.dialog-trigger for="task-modal">
                                                <x-ui.button type="button" size="sm" variant="outline" wire:click="edit({{ $task->id }})" class="opacity-0 group-hover:opacity-100 size-7 p-0">
                                                    <x-lucide-pencil class="size-4" />
                                                </x-ui.button>
                                            </x-ui.dialog-trigger>
                                            <x-ui.button type="button" size="sm" variant="outline" wire:click="delete({{ $task->id }})" wire:confirm="¿Eliminar tarea?" class="opacity-0 group-hover:opacity-100 size-7 p-0 text-destructive">
                                                <x-lucide-trash-2 class="size-4" />
                                            </x-ui.button>
                                        </div>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @empty
                                <x-ui.table-row>
                                    <x-ui.table-cell colspan="7" class="py-12 text-center">
                                        <x-ui.empty class="p-0 border-0">
                                            <x-lucide-clipboard-list class="mx-auto mb-2 size-10 opacity-30" />
                                            <p class="text-sm font-medium text-muted-foreground">Sin tareas</p>
                                        </x-ui.empty>
                                    </x-ui.table-cell>
                                </x-ui.table-row>
                            @endforelse
                        </x-ui.table-body>
                    </x-ui.table>
                </x-ui.card-content>
            </x-ui.card>

            <div class="mt-2">
            </div>
        </div>
    </div>

    {{-- TASK MODAL --}}
    <x-ui.dialog id="task-modal">
        <x-ui.dialog-content class="sm:max-w-xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>{{ $taskId ? 'Editar tarea' : 'Nueva tarea' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $workOrder->community?->name ?? 'Sin comunidad' }} — {{ $workOrder->work_date?->format('d/m/Y') }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <x-ui.field label="Título">
                        <x-ui.input wire:model="title" placeholder="Título de la tarea" />
                        @error('title') <p class="text-xs text-destructive mt-1">{{ $message }}</p> @enderror
                    </x-ui.field>
                </div>

                <x-ui.field label="Prioridad">
                    <x-ui.select native wire:model="priority" class="w-full">
                        <option value="low">Baja</option>
                        <option value="normal">Normal</option>
                        <option value="high">Alta</option>
                        <option value="urgent">Urgente</option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Estado">
                    <x-ui.select native wire:model="status" class="w-full">
                        <option value="pending">Pendiente</option>
                        <option value="completed">Completada</option>
                        <option value="not_done">No realizada</option>
                        <option value="cancelled">Cancelada</option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Resultado">
                    <x-ui.select native wire:model="result" class="w-full">
                        <option value="">—</option>
                        <option value="correcto">Correcto</option>
                        <option value="con_observaciones">Con observaciones</option>
                        <option value="no_realizado">No realizado</option>
                        <option value="requiere_seguimiento">Requiere seguimiento</option>
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Orden">
                    <x-ui.input type="number"  label="Orden" wire:model="sort" />
                </x-ui.field>

                <x-ui.field label="Solicitante">
                    <x-ui.input wire:model="requesterName" placeholder="Nombre" />
                </x-ui.field>

                <x-ui.field label="Teléfono">
                    <x-ui.input wire:model="requesterPhone" placeholder="600 000 000" />
                </x-ui.field>

                <x-ui.field label="Referencia">
                    <x-ui.input wire:model="reference" placeholder="Referencia" />
                </x-ui.field>

                <div class="col-span-2">
                    <x-ui.field label="Instrucciones">
                        <textarea wire:model="instructions"  placeholder="Instrucciones" rows="2" class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                    </x-ui.field>
                </div>

                <div class="col-span-2">
                    <x-ui.field label="Requisitos">
                        <textarea wire:model="requirements" placeholder="Requisitos" rows="2" class="border-input bg-background placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border px-3 py-2 text-sm shadow-xs focus-visible:ring-[3px] outline-none resize-none"></textarea>
                    </x-ui.field>
                </div>
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.dialog-close>
                    <x-ui.button type="button" variant="outline" size="sm" wire:click="resetForm">Cancelar</x-ui.button>
                </x-ui.dialog-close>
                <x-ui.dialog-close>
                    <x-ui.button type="button" size="sm" wire:click="save">
                        <x-lucide-save class="size-3.5 mr-1" /> Guardar
                    </x-ui.button>
                </x-ui.dialog-close>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

    {{-- Comments modal --}}
    <x-ui.dialog wire:model="showComments">
        <x-ui.dialog-content class="sm:max-w-xl">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Comentarios de {{ $selectedTaskForComments?->title ?? 'tarea' }}</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $workOrder?->code }} — {{ $workOrder?->community?->name ?? '—' }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 space-y-3 max-h-[60vh] overflow-y-auto">
                <div class="flex flex-col gap-2">
                    <textarea wire:model="newComment" rows="2" placeholder="Añadir comentario..." class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[#E60000] focus:border-transparent resize-none"></textarea>
                    @error('newComment') <p class="text-[10px] text-destructive">{{ $message }}</p> @enderror
                    <x-ui.button type="button" size="sm" wire:click="saveComment" class="self-start">
                        <x-lucide-send class="size-3.5 mr-1" /> Comentar
                    </x-ui.button>
                </div>

                @if($selectedTaskForComments && $selectedTaskForComments->comments->isNotEmpty())
                    @foreach($selectedTaskForComments->comments as $comment)
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
