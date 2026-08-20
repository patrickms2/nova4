<div class="min-h-screen bg-[#111111]">


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
        <a href="{{ $workOrder ? route('comunigest.work-order', $workOrder) : route('comunigest.inicio') }}" class="p-2 -ml-2 rounded-full text-[#666666] hover:text-white">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
        </a>
        <div class="text-center">
            <h1 class="text-lg font-bold">Nueva incidencia</h1>
            <p class="text-[10px] text-[#666666]">{{ $communityName }}</p>
        </div>
        <div class="w-8"></div>
    </div>

    <div class="px-4 pb-24 space-y-5">

        {{-- Type --}}
        <div>
            <label class="block mb-2 text-xs text-[#666666]">Tipo de incidencia</label>
            <select wire:model="incidentType" class="w-full p-3 text-sm text-white rounded-xl bg-[#2A2A2A] border border-[#2A2A2A] focus:border-[#E60000] outline-none appearance-none">
                <option>Fuga de agua</option>
                <option>Luces de emergencia</option>
                <option>Limpieza</option>
                <option>Avería eléctrica</option>
                <option>Fontanería</option>
                <option>Jardinería</option>
                <option>Otros</option>
            </select>
        </div>

        {{-- Description --}}
        <div>
            <label class="block mb-2 text-xs text-[#666666]">Descripción</label>
            <textarea wire:model="description" rows="4" class="w-full p-3 text-sm text-white rounded-xl bg-[#2A2A2A] border border-[#2A2A2A] focus:border-[#E60000] outline-none" placeholder="Describe lo ocurrido..."></textarea>
            @error('description') <p class="mt-1 text-[10px] text-[#E60000]">{{ $message }}</p> @enderror
            @error('incidentType') <p class="mt-1 text-[10px] text-[#E60000]">{{ $message }}</p> @enderror
        </div>

        {{-- Priority --}}
        <div>
            <label class="block mb-2 text-xs text-[#666666]">Prioridad</label>
            <div class="grid grid-cols-4 gap-2">
                @foreach(['baja','media','alta','urgente'] as $p)
                    <button wire:click="$set('priority', '{{ $p }}')" type="button" class="py-2 text-xs font-medium rounded-lg {{ $priority === $p ? 'bg-[#E60000] text-white' : 'bg-[#2A2A2A] text-[#666666]' }}">
                        {{ ucfirst($p) }}
                    </button>
                @endforeach
            </div>
            @error('priority') <p class="mt-1 text-[10px] text-[#E60000]">{{ $message }}</p> @enderror
        </div>

        {{-- Photos --}}
        <div>
            <label class="block mb-2 text-xs text-[#666666]">Fotografías</label>

            @if(count($photoFiles) > 0)
                <div class="grid grid-cols-3 gap-2 mb-3">
                    @foreach($photoFiles as $index => $photo)
                        <div class="relative h-20 rounded-lg overflow-hidden bg-[#2A2A2A]">
                            <img src="{{ $photo->temporaryUrl() }}" class="w-full h-full object-cover" />
                            <button type="button" wire:click="removePhoto({{ $index }})" class="absolute top-1 right-1 p-1 rounded-full bg-[#111111]/80 text-[#E60000]">
                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="relative">
                <input type="file" wire:model="photoFiles" multiple accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                <button type="button" class="flex items-center justify-center w-full h-20 rounded-lg border border-dashed border-[#E60000] text-[#E60000]">
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="12" cy="12" r="5"/><path d="M12 7v10M7 12h10"/></svg>
                    <span class="text-xs">Añadir fotos</span>
                </button>
            </div>
            @error('photoFiles.*') <p class="mt-1 text-[10px] text-[#E60000]">{{ $message }}</p> @enderror
        </div>

        {{-- Save --}}
        <button wire:click="save" type="button" class="w-full py-4 text-sm font-semibold text-white rounded-2xl bg-[#E60000] shadow-lg shadow-[#E60000]/25">
            Guardar incidencia
        </button>
    </div>
</div>
