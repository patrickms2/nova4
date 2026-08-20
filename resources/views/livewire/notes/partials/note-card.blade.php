@php
    $tags = $note->tags ?? [];
    $isPinned = $note->is_pinned ?? false;
@endphp
<div
    wire:key="note-{{ $note->id }}"
    class="group relative bg-white border rounded-xl border-neutral-200 shadow-sm hover:shadow-md transition-all cursor-pointer"
    @click="$wire.editNote({{ $note->id }})"
>
    <div class="p-4">
        <div class="flex items-start justify-between gap-2 mb-2">
            <h4 class="font-medium text-sm line-clamp-2">{{ $note->title }}</h4>
            <button
                wire:click.stop="togglePin({{ $note->id }})"
                class="flex items-center justify-center transition rounded-md size-6 shrink-0 text-muted-foreground hover:bg-accent hover:text-foreground"
                :class="{{ $isPinned ? "'text-yellow-500'" : "'opacity-0 group-hover:opacity-100'" }}"
            >
                <x-lucide-pin class="size-3.5" :class="{{ $isPinned ? "'fill-current'" : '' }}" />
            </button>
        </div>

        <p class="text-xs text-muted-foreground line-clamp-3 mb-3">{{ $note->excerpt }}</p>

        @if($tags)
            <div class="flex flex-wrap gap-1 mb-3">
                @foreach($tags as $tag)
                    <span class="inline-flex items-center px-2 py-0.5 text-[10px] font-medium rounded-full bg-neutral-100 text-neutral-600">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        <div class="flex items-center justify-between text-xs text-muted-foreground">
            <div class="flex items-center gap-2">
                @if($note->folder)
                    <span class="flex items-center gap-1">
                        <x-lucide-folder class="size-3" />
                        {{ $note->folder->name }}
                    </span>
                @endif
                @if($note->project)
                    <span class="flex items-center gap-1">
                        <x-lucide-layers class="size-3" />
                        {{ $note->project->name }}
                    </span>
                @endif
            </div>
            <span>{{ $note->created_at->diffForHumans() }}</span>
        </div>
    </div>

    <div wire:ignore class="absolute top-2 right-2 flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
        @if($note->clickup_doc_id)
            <span class="text-xs text-green-600 flex items-center gap-1 bg-white/80 px-2 py-1 rounded">
                <x-lucide-check class="size-3" />
                ClickUp
            </span>
        @else
            <x-ui.button size="sm" variant="outline"
                class="flex items-center justify-center rounded-md size-7 hover:bg-accent text-muted-foreground hover:text-foreground"
                wire:click.stop="exportToClickUp({{ $note->id }})"
                title="Exportar a ClickUp">
                <x-lucide-upload class="size-3.5" />
            </x-ui.button>
        @endif
        <x-ui.button size="sm" variant="outline"
            class="flex items-center justify-center rounded-md size-7 hover:bg-accent text-muted-foreground hover:text-foreground"
            wire:click.stop="deleteNote({{ $note->id }})"
            wire:confirm="¿Eliminar esta nota?">
            <x-lucide-trash-2 class="size-3.5" />
        </x-ui.button>
    </div>
</div>
