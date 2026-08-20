@php
    $isExpanded = isset($expandedProjects[$project->id]) ? $expandedProjects[$project->id] : false;
    $hasChildren = $project->children->isNotEmpty();
    $progress = $project->progress ?? 0;
    $isOverdue = $project->is_overdue ?? false;

    $phaseColors = [
        'planning' => 'bg-gray-100 text-gray-700',
        'development' => 'bg-blue-100 text-blue-700',
        'testing' => 'bg-purple-100 text-purple-700',
        'deployment' => 'bg-orange-100 text-orange-700',
        'completed' => 'bg-green-100 text-green-700',
    ];
    $phaseLabels = [
        'planning' => 'Planificación',
        'development' => 'Desarrollo',
        'testing' => 'Testing',
        'deployment' => 'Despliegue',
        'completed' => 'Completado',
    ];
@endphp
<div
    wire:key="project-{{ $project->id }}"
    class="border-b last:border-b-0"
    style="padding-left: {{ $level * 20 }}px"
>
    <div
        class="flex items-center gap-3 px-4 py-3 hover:bg-muted/50 transition-colors cursor-pointer"
        @click="expandedProjects[{{ $project->id }}] = !expandedProjects[{{ $project->id }}]"
    >
        {{-- Expand/Collapse icon --}}
        @if($hasChildren)
            <div class="flex items-center justify-center size-5 shrink-0 text-muted-foreground">
                <svg class="size-3.5 transition-transform duration-200"
                    :class="expandedProjects[{{ $project->id }}] ? 'rotate-90' : ''"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                    <path d="m9 18 6-6-6-6"/>
                </svg>
            </div>
        @else
            <div class="size-5 shrink-0"></div>
        @endif

        {{-- Project icon/color --}}
        <div class="flex items-center justify-center size-8 rounded-lg shrink-0"
            style="background-color: {{ $project->color }}20; color: {{ $project->color }}">
            @if($project->icon)
                <span>{!! $project->icon !!}</span>
            @else
                <x-lucide-folder class="size-4" />
            @endif
        </div>

        {{-- Project info --}}
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span class="font-medium text-sm truncate {{ $project->status === 'archived' ? 'text-muted-foreground line-through' : '' }}">
                    {{ $project->name }}
                </span>
                @if($isOverdue)
                    <span class="text-[10px] text-red-600 font-medium">Vencido</span>
                @endif
            </div>
            @if($project->description)
                <div class="text-[11px] text-muted-foreground truncate max-w-xs">{{ $project->description }}</div>
            @endif
        </div>

        {{-- Progress bar --}}
        <div class="flex items-center gap-2 w-32">
            <div class="flex-1 h-1.5 bg-neutral-200 rounded-full overflow-hidden">
                <div class="h-full rounded-full transition-all duration-300"
                    style="width: {{ $progress }}%; background-color: {{ $project->color }}">
                </div>
            </div>
            <span class="text-[10px] text-muted-foreground tabular-nums">{{ number_format($progress, 0) }}%</span>
        </div>

        {{-- Phase badge --}}
        <span class="inline-flex items-center rounded-full px-2 py-1 text-[10px] font-medium shrink-0 {{ $phaseColors[$project->phase] ?? 'bg-gray-100 text-gray-700' }}">
            {{ $phaseLabels[$project->phase] ?? $project->phase }}
        </span>

        {{-- Tasks count --}}
        <div class="flex items-center gap-1 text-xs text-muted-foreground shrink-0">
            <x-lucide-list-todo class="size-3" />
            <span>{{ $project->tasks_count ?? 0 }}</span>
        </div>

        {{-- Actions --}}
        <div wire:ignore class="flex items-center gap-1 ml-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <x-ui.button size="sm" variant="outline"
                href="{{ \App\Filament\App\Facturacion\Resources\TaskResource::getUrl('index', [], false, 'fact') . '?tableFilters[project_id][value]=' . $project->id }}"
                class="flex items-center justify-center rounded-md size-7 hover:bg-accent text-muted-foreground hover:text-foreground"
                title="Ver tareas">
                <x-lucide-list-todo class="size-3.5" />
            </x-ui.button>
            <x-ui.button size="sm" variant="outline" @click.stop="$wire.editProject({{ $project->id }})"
                class="flex items-center justify-center rounded-md size-7 hover:bg-accent text-muted-foreground hover:text-foreground">
                <x-lucide-pencil class="size-3.5" />
            </x-ui.button>
            <x-ui.button size="sm" variant="outline"
                class="flex items-center justify-center rounded-md size-7 hover:bg-accent text-muted-foreground hover:text-foreground"
                wire:click.stop="deleteProject({{ $project->id }})"
                wire:confirm="¿Eliminar este proyecto y todos sus subproyectos?">
                <x-lucide-trash-2 class="size-3.5" />
            </x-ui.button>
        </div>
    </div>

    {{-- Children (recursive) --}}
    @if($hasChildren)
        <div x-show="expandedProjects[{{ $project->id }}]" x-collapse class="border-t border-neutral-100">
            @foreach($project->children as $child)
                @include('livewire.projects.partials.project-item', ['project' => $child, 'level' => $level + 1])
            @endforeach
        </div>
    @endif
</div>
