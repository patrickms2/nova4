@vite(['resources/js/react-flow-panel-builder.jsx'])
<div 
    class="nova-graph min-h-full bg-black text-white"
x-data="reactFlowBuilder(); novaGraphPalette()" x-init="init()">

    <header class="mx-auto flex max-w-7xl items-center justify-between px-6 py-8 lg:px-10">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-orange-500">Live System Graph</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $businessName }}</h1>
        </div>
        <a href="{{ route('nova.nova-workspace') }}" class="text-sm text-neutral-500 transition hover:text-white">← Volver a NOVA</a>
    </header>

    <main class="mx-auto flex max-w-7xl gap-6 px-6 pb-16 lg:px-10">
        <aside class="w-56 shrink-0">
            <p class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Capacidades disponibles</p>
            <p class="mt-1 text-[11px] text-neutral-600">Arrastra al lienzo para añadir.</p>
            <div class="mt-3 space-y-2">
                @forelse ($this->availableCapabilities() as $capability)
                    <div
                        draggable="true"
                        x-on:dragstart="$event.dataTransfer.setData('type', 'capability'); $event.dataTransfer.setData('id', '{{ $capability['id'] }}')"
                        class="cursor-grab rounded-lg border border-sky-500/30 bg-sky-500/5 px-3 py-2 text-xs font-medium text-sky-200 transition active:cursor-grabbing hover:border-sky-500/60"
                    >
                        {{ $capability['icon'] ?? '◇' }} {{ $capability['name'] }}
                    </div>
                @empty
                    <p class="text-xs text-neutral-600">No hay más capacidades sugeridas.</p>
                @endforelse
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-neutral-500">Acciones</p>
            <p class="mt-1 text-[11px] text-neutral-600">Suelta sobre una capacidad.</p>
            <div class="mt-3">
                <div
                    draggable="true"
                    x-on:dragstart="$event.dataTransfer.setData('type', 'action')"
                    class="cursor-grab rounded-lg border border-neutral-700 bg-neutral-900 px-3 py-2 text-xs font-medium text-neutral-300 transition active:cursor-grabbing hover:border-neutral-500"
                >
                    ＋ Nueva acción
                </div>
            </div>

            <p class="mt-6 text-xs font-semibold uppercase tracking-wide text-neutral-500">Relaciones</p>
            <p class="mt-1 text-[11px] text-neutral-600">Arrastra desde el punto naranja de un nodo hasta otro nodo para conectarlos.</p>
        </aside>

        <div
            x-on:dragover.prevent
            x-on:drop.prevent="handleDrop($event)"
            class="relative min-h-[560px] flex-1 overflow-hidden rounded-2xl border border-neutral-900 bg-neutral-950"
        >
            @if ($nodes === [])
                <p class="p-10 text-sm text-neutral-500">No hay Workspace activo. Crea uno desde Studio.</p>
            @else
                <div x-node="{type: 'workspace'}" x-data="{ props: {} }" x-ignore>
                    <div x-ignore class="flow-node flow-node--workspace" wire:click="selectNode(node.id)" x-on:contextmenu.prevent="openNodeMenu($event, node.id, node.type, node.data.meta.deletable)">
                        <p class="flow-node__type">WORKSPACE</p>
                        <p class="flow-node__label" x-text="(node.data.meta.icon ?? '✦') + ' ' + node.data.label"></p>
                    </div>
                </div>

                <div x-node="{type: 'capability'}" x-data="{ props: {} }" x-ignore>
                    <div x-ignore class="flow-node flow-node--capability" :data-capability="node.data.meta.id" wire:click="selectNode(node.id)" x-on:mouseup="window.novaFinishConnection && window.novaFinishConnection(node.id)" x-on:contextmenu.prevent="openNodeMenu($event, node.id, node.type, node.data.meta.deletable)">
                        <p class="flow-node__type">CAPABILITY</p>
                        <p class="flow-node__label" x-text="(node.data.meta.icon ?? '\u25c7') + ' ' + node.data.label"></p>
                        <button type="button" class="flow-node__handle" x-on:mousedown.stop="window.__novaConnecting = node.id" title="Arrastra a otro nodo para relacionar">&#128279;</button>
                    </div>
                </div>

                <div x-node="{type: 'resource'}" x-data="{ props: {} }" x-ignore>
                    <div x-ignore class="flow-node flow-node--resource" wire:click="selectNode(node.id)" x-on:mouseup="window.novaFinishConnection && window.novaFinishConnection(node.id)" x-on:contextmenu.prevent="openNodeMenu($event, node.id, node.type, node.data.meta.deletable)">
                        <p class="flow-node__type">RESOURCE</p>
                        <p class="flow-node__label" x-text="node.data.label"></p>
                        <button type="button" class="flow-node__handle" x-on:mousedown.stop="window.__novaConnecting = node.id" title="Arrastra a otro nodo para relacionar">&#128279;</button>
                    </div>
                </div>

                <div x-node="{type: 'action'}" x-data="{ props: {} }" x-ignore>
                    <div x-ignore class="flow-node flow-node--action" wire:click="selectNode(node.id)" x-on:mouseup="window.novaFinishConnection && window.novaFinishConnection(node.id)" x-on:contextmenu.prevent="openNodeMenu($event, node.id, node.type, node.data.meta.deletable)">
                        <p class="flow-node__type">ACTION</p>
                        <p class="flow-node__label" x-text="node.data.label"></p>
                    </div>
                </div>

                <div
                    wire:key="flow-{{ md5(json_encode($nodes).json_encode($edges)) }}"
                    wire:ignore
                    x-data="editor = flowEditor({
                        nodes: @js($nodes),
                        edges: @js($edges),
                        dagreConfig: { rankdir: 'LR', nodesep: 24, ranksep: 90 },
                        autoCenter: true,
                        panOnScroll: true,
                        zoomOnPinch: true,
                    })"
                    style="width: 100%; height: 560px;"
                ></div>
            @endif
        </div>

        <aside class="w-80 shrink-0 rounded-2xl border border-neutral-900 bg-neutral-950 p-5">
            @php $selected = $this->selectedNode(); @endphp

            @if ($selected === null)
                <p class="text-sm text-neutral-500">Selecciona un nodo para inspeccionarlo.</p>
            @else
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-neutral-500">{{ strtoupper($selected['type']) }}</p>
                        <h2 class="mt-1 text-lg font-semibold">{{ $selected['data']['label'] }}</h2>
                    </div>
                    <button wire:click="closeInspector" type="button" class="text-neutral-600 hover:text-white">✕</button>
                </div>

                <p class="mt-4 text-[10px] font-semibold uppercase tracking-wide text-neutral-600">NOVA Address</p>
                <p class="mt-1 break-all text-xs text-neutral-400">{{ $selected['id'] }}</p>

                @if (!empty($selected['data']['meta']))
                    <p class="mt-4 text-[10px] font-semibold uppercase tracking-wide text-neutral-600">Meta</p>
                    <ul class="mt-1 space-y-1 text-xs text-neutral-400">
                        @foreach ($selected['data']['meta'] as $key => $value)
                            @if (is_scalar($value))
                                <li><span class="text-neutral-600">{{ $key }}:</span> {{ $value }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif

                <p class="mt-4 text-[10px] font-semibold uppercase tracking-wide text-neutral-600">Relaciones</p>
                <ul class="mt-1 space-y-1 text-xs text-neutral-400">
                    @forelse ($this->relationsFor($selected['id']) as $relation)
                        <li>{{ $relation['source'] }} <span class="text-orange-400">{{ $relation['data']['type'] }}</span> {{ $relation['target'] }}</li>
                    @empty
                        <li>Sin relaciones.</li>
                    @endforelse
                </ul>
            @endif
        </aside>
    </main>

    <div
        x-show="nodeMenu.open"
        x-cloak
        x-on:click.outside="closeNodeMenu()"
        x-on:keydown.escape.window="closeNodeMenu()"
        :style="{ top: nodeMenu.y + 'px', left: nodeMenu.x + 'px' }"
        class="fixed z-50 min-w-[9rem] rounded-lg border border-neutral-800 bg-neutral-950 py-1 shadow-xl"
    >
        <button
            type="button"
            x-show="nodeMenu.canDelete"
            x-on:click="confirmDeleteNode()"
            class="block w-full px-3 py-1.5 text-left text-xs text-red-400 transition hover:bg-neutral-900"
        >
            Eliminar
        </button>
        <p x-show="!nodeMenu.canDelete" class="px-3 py-1.5 text-left text-xs text-neutral-600">
            No eliminable
        </p>
    </div>
</div>
