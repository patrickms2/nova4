<div class="min-h-screen bg-[#111111]" wire:poll.5s="computeElapsed">
        <header class="w-full border-b border-[#2A2A2A] bg-[#111111]/90 backdrop-blur">
        <div class="flex items-center justify-between h-16 px-4 mx-auto max-w-7xl md:px-8">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#E60000]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                        <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">Nova Community</span>
                <span class="text-xs font-bold tracking-tight">{{ auth()->user()?->role }}</span>
            </div>

              <div class="flex items-center gap-3">
                    <button type="button" class="relative p-2 rounded-full bg-[#2A2A2A] text-[#666666]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a1.999 1.999 0 0 1-3.46 0"/></svg>

                    </button>
                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-[#2A2A2A] text-xs font-bold text-[#FFFFFF]">
                        {{ substr(auth()->user()?->firstname ?? auth()->user()?->name ?? 'U', 0, 1) }}
                    </div>
                </div>
        </div>
    </header>
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 pt-4 pb-4 bg-[#111111]">
        <a href="{{ route('comunigest.inicio') }}" class="p-2 -ml-2 rounded-full text-[#666666] hover:text-white">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div class="text-center">
            <h1 class="text-lg font-bold">Plan {{  $workOrder->reference }} {{ $workOrder->community->name }}</h1>
            <p class="text-[10px] text-[#666666]">Nº {{ $workOrder->code }}</p>
        </div>
        <button type="button" class="p-2 -mr-2 rounded-full text-[#666666] hover:text-white">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
        </button>
    </div>

    {{-- Status bar --}}
    <div class="px-4 pb-6">
        <div class="flex items-center justify-between p-3 rounded-2xl bg-[#2A2A2A]">
            <div class="flex items-center gap-2">
                <span class="w-2 h-2 rounded-full {{ $workOrder->status === 'finished' ? 'bg-emerald-500' : 'bg-[#E60000]' }}"></span>
                <span class="text-xs font-medium">
                    {{ match($workOrder->status) { 'in_progress' => 'Servicio en curso', 'finished' => 'Finalizado', 'cancelled' => 'Cancelado', default => 'Pendiente' } }}
                </span>
            </div>
            <span class="font-mono text-sm font-semibold text-[#E60000]">{{ $elapsed }}</span>
        </div>
    </div>

    {{-- Checklist --}}
    <div class="px-4 pb-4">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-[#666666]">TAREAS CHECKLIST</h2>
            <span class="text-xs text-[#E60000] font-semibold">{{ $workOrder->tasks->where('status', 'completed')->count() }}/{{ $workOrder->tasks->count() }}</span>
        </div>
        <div class="space-y-3">
            @foreach($workOrder->tasks as $task)
                @php
                    $completed = $task->status === 'completed';
                @endphp
                <button wire:click="toggleTask({{ $task->id }})" type="button" class="w-full flex items-center gap-3 p-3 rounded-2xl {{ $completed ? 'bg-[#E60000]/10 border border-[#E60000]/30' : 'bg-[#2A2A2A]' }} text-left transition">
                    <div class="flex items-center justify-center w-6 h-6 rounded {{ $completed ? 'bg-[#E60000]' : 'border border-[#666666]' }} shrink-0">
                        @if($completed)
                            <svg class="w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="4"><polyline points="20 6 9 17 4 12"/></svg>
                        @endif
                    </div>
                    <span class="flex-1 text-sm {{ $completed ? 'line-through text-[#666666]' : 'text-white' }}">{{ $task->title }}</span>
                    @if($task->priority === 'urgent' && !$completed)
                        <span class="px-1.5 py-0.5 text-[10px] font-medium text-white rounded bg-[#E60000]">!</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Photo & Incident cards --}}
    <div class="grid grid-cols-2 gap-3 px-4 mb-6">
        <button type="button" wire:click="openPhotos" class="p-3 text-center rounded-2xl bg-[#2A2A2A] border border-transparent hover:border-[#E60000] transition">
            <div class="relative inline-block">
                <svg class="w-8 h-8 mx-auto mb-1 text-[#666666]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="12" r="5"/><path d="M12 7v10M7 12h10"/></svg>
                <span class="absolute -top-1 -right-2 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white rounded-full bg-[#E60000]">{{ $photosCount }}</span>
            </div>
            <p class="text-[10px] text-[#666666]">Fotografías</p>
        </button>
        <button type="button" wire:click="openIncidents" class="p-3 text-center rounded-2xl bg-[#2A2A2A] border border-transparent hover:border-[#E60000] transition">
            <div class="relative inline-block">
                <svg class="w-8 h-8 mx-auto mb-1 text-[#E60000]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span class="absolute -top-1 -right-2 flex items-center justify-center w-4 h-4 text-[10px] font-bold text-white rounded-full bg-[#E60000]">{{ $incidentsCount }}</span>
            </div>
            <p class="text-[10px] text-[#666666]">Incidencias</p>
        </button>
    </div>

    {{-- New incident link --}}
    <div class="px-4 pb-3">
        <a href="{{ route('comunigest.new-incident', $workOrder) }}" class="block w-full py-3 text-sm font-semibold text-center text-white border border-[#E60000] rounded-2xl hover:bg-[#E60000]/10 transition">
            + Nueva incidencia
        </a>
    </div>

    {{-- Main action --}}
    <div class="px-4 pb-24">
        @if($workOrder->status === 'in_progress')
            <button wire:click="finishOrder" type="button" class="w-full py-4 text-sm font-semibold text-white rounded-2xl bg-[#E60000] shadow-lg shadow-[#E60000]/25">
                Finalizar servicio
            </button>
        @elseif($workOrder->status === 'pending')
            <button wire:click="startOrder" type="button" class="w-full py-4 text-sm font-semibold text-white rounded-2xl bg-[#E60000] shadow-lg shadow-[#E60000]/25">
                Iniciar servicio
            </button>
        @else
            <div class="w-full py-4 text-sm font-semibold text-center rounded-2xl bg-emerald-500 text-white">
                Servicio finalizado
            </div>
        @endif
    </div>

    {{-- Photo modal --}}
    <x-ui.dialog wire:model="showPhotoModal">
        <x-ui.dialog-content class="sm:max-w-md">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Añadir fotografía</x-ui.dialog-title>
                <x-ui.dialog-description>Selecciona la tarea y sube una foto</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 space-y-4">
                <x-ui.field label="Tarea">
                    <x-ui.select native wire:model="selectedTaskId" class="w-full">
                        @foreach($workOrder->tasks as $task)
                            <option value="{{ $task->id }}">{{ $task->title }}</option>
                        @endforeach
                    </x-ui.select>
                </x-ui.field>

                <x-ui.field label="Fotografía">
                    <input type="file" wire:model="photoFile" accept="image/*" class="block w-full text-sm text-neutral-700 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-[#E60000] file:text-white hover:file:bg-[#cc0000]" />
                    @error('photoFile') <p class="text-[10px] text-destructive mt-1">{{ $message }}</p> @enderror
                </x-ui.field>

                @if($photoFile)
                    <div class="h-40 rounded-lg overflow-hidden bg-neutral-100">
                        <img src="{{ $photoFile->temporaryUrl() }}" class="w-full h-full object-contain" />
                    </div>
                @endif
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" size="sm" variant="outline" wire:click="closePhotoModal">Cancelar</x-ui.button>
                <x-ui.button type="button" size="sm" wire:click="uploadPhoto">
                    <x-lucide-camera class="size-3.5 mr-1" /> Subir foto
                </x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

    {{-- View photos modal --}}
    <x-ui.dialog wire:model="showPhotos">
        <x-ui.dialog-content class="sm:max-w-md">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Fotografías de la orden</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $workOrder->code }} — {{ $workOrder->community->name }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 grid grid-cols-2 gap-2 max-h-[60vh] overflow-y-auto">
                @if($workOrder->photos->isNotEmpty())
                    @foreach($workOrder->photos as $photo)
                        <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" class="block rounded-lg overflow-hidden border border-neutral-700 hover:opacity-80">
                            <img src="{{ asset('storage/'.$photo->path) }}" alt="{{ $photo->filename }}" class="w-full h-28 object-cover" />
                        </a>
                    @endforeach
                @else
                    <p class="col-span-2 text-sm text-neutral-500 text-center py-8">No hay fotos</p>
                @endif
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <x-ui.button type="button" size="sm" wire:click="openPhotoModal">
                    <x-lucide-camera class="size-3.5 mr-1" /> Subir foto
                </x-ui.button>
                <x-ui.button type="button" size="sm" variant="outline" wire:click="closePhotos">Cerrar</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>

    {{-- View incidents modal --}}
    <x-ui.dialog wire:model="showIncidents">
        <x-ui.dialog-content class="sm:max-w-md">
            <x-ui.dialog-header>
                <x-ui.dialog-title>Incidencias de la orden</x-ui.dialog-title>
                <x-ui.dialog-description>{{ $workOrder->code }} — {{ $workOrder->community->name }}</x-ui.dialog-description>
            </x-ui.dialog-header>

            <div class="mt-4 space-y-3 max-h-[60vh] overflow-y-auto">
                @if($workOrder->incidents->isNotEmpty())
                    @foreach($workOrder->incidents as $incident)
                        <div class="p-3 rounded-xl border border-neutral-700 bg-[#2A2A2A]">
                            <div class="flex items-center justify-between mb-1">
                                <p class="text-sm font-semibold text-white">{{ $incident->title }}</p>
                                <span class="px-1.5 py-0.5 text-[10px] font-medium rounded text-rose-950 bg-rose-400">{{ $incident->priority }}</span>
                            </div>
                            @if($incident->description)
                                <p class="text-xs text-neutral-400 mb-2">{{ $incident->description }}</p>
                            @endif
                            @if($incident->photos->isNotEmpty())
                                <div class="grid grid-cols-3 gap-1 mt-2">
                                    @foreach($incident->photos as $photo)
                                        <a href="{{ asset('storage/'.$photo->path) }}" target="_blank" class="block rounded-md overflow-hidden">
                                            <img src="{{ asset('storage/'.$photo->path) }}" alt="" class="w-full h-14 object-cover" />
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                @else
                    <p class="text-sm text-neutral-500 text-center py-8">No hay incidencias</p>
                @endif
            </div>

            <x-ui.dialog-footer class="mt-6 flex justify-end gap-2">
                <a href="{{ route('comunigest.new-incident', $workOrder) }}" wire:navigate>
                    <x-ui.button type="button" size="sm">
                        <x-lucide-plus class="size-3.5 mr-1" /> Nueva incidencia
                    </x-ui.button>
                </a>
                <x-ui.button type="button" size="sm" variant="outline" wire:click="closeIncidents">Cerrar</x-ui.button>
            </x-ui.dialog-footer>
        </x-ui.dialog-content>
    </x-ui.dialog>
</div>
