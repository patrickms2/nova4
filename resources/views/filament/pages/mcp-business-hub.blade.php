<x-filament-panels::page>

    {{-- Stats bar --}}
    <div class="grid grid-cols-3 gap-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">MCP Servers</p>
            <p class="mt-1 text-3xl font-bold text-gray-950 dark:text-white">{{ $totals['servers'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tools activas</p>
            <p class="mt-1 text-3xl font-bold text-success-600 dark:text-success-400">{{ $totals['tools'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompts editables</p>
            <p class="mt-1 text-3xl font-bold text-warning-600 dark:text-warning-400">{{ $totals['prompts'] }}</p>
        </div>
    </div>

    {{-- Nova hub node --}}
    @if($novaServer)
    <div class="mt-6">
        <div class="relative rounded-2xl border-2 border-primary-500 bg-primary-50 p-5 dark:border-primary-400 dark:bg-primary-950/30">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-primary-500 text-white">
                            <x-filament::icon icon="heroicon-m-cpu-chip" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-lg font-bold text-primary-700 dark:text-primary-300">{{ $novaServer->name }} <span class="ml-1 text-xs font-normal text-primary-500">— Orquestador principal</span></h2>
                            <code class="text-xs text-primary-600 dark:text-primary-400">{{ $novaServer->endpoint }}</code>
                        </div>
                    </div>
                    @if($novaServer->description)
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $novaServer->description }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-wrap gap-2">
                    <x-filament::button
                        href="{{ \App\Filament\Pages\McpBusinessHub::chatUrl($novaServer) }}"
                        tag="a"
                        icon="heroicon-m-chat-bubble-left-right"
                        size="sm"
                        color="primary"
                    >
                        Abrir chat
                    </x-filament::button>
                    <x-filament::button
                        href="{{ route('filament.admin.resources.servers.edit', ['record' => $novaServer->id]) }}"
                        tag="a"
                        icon="heroicon-m-pencil-square"
                        size="sm"
                        color="gray"
                        outlined
                    >
                        Editar
                    </x-filament::button>
                </div>
            </div>

            {{-- Nova prompts (reglas del agente) --}}
            @if($novaServer->prompts->isNotEmpty())
                <div class="mt-4">
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-primary-600 dark:text-primary-400">Prompts del agente (editables)</p>
                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($novaServer->prompts as $prompt)
                            <a
                                href="{{ route('filament.admin.resources.prompts.edit', ['record' => $prompt->id]) }}"
                                class="flex items-start gap-2 rounded-lg border border-primary-200 bg-white p-3 text-sm hover:border-primary-400 dark:border-primary-800 dark:bg-gray-900 dark:hover:border-primary-600"
                            >
                                <x-filament::icon icon="heroicon-m-document-text" class="mt-0.5 h-4 w-4 shrink-0 text-warning-500" />
                                <div class="min-w-0">
                                    <div class="truncate font-medium text-gray-900 dark:text-white">{{ $prompt->title }}</div>
                                    <code class="truncate text-xs text-gray-400">{{ $prompt->name }}</code>
                                    @if($prompt->description)
                                        <p class="mt-0.5 truncate text-xs text-gray-500">{{ $prompt->description }}</p>
                                    @endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-400">
                        <a href="{{ route('filament.admin.resources.prompts.index') }}" class="hover:underline">Ver todos los prompts →</a>
                    </p>
                </div>
            @endif
        </div>

        {{-- Connection lines (visual) --}}
        @if($agentServers->isNotEmpty())
            <div class="relative my-0 flex justify-center">
                <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
            </div>
            <div class="relative flex justify-center">
                <div class="h-px w-full max-w-4xl bg-gray-300 dark:bg-gray-600"></div>
            </div>
            <div class="flex justify-around">
                @foreach($agentServers as $s)
                    <div class="h-8 w-px bg-gray-300 dark:bg-gray-600"></div>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- Agent server cards --}}
    @if($agentServers->isNotEmpty())
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($agentServers as $server)
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    {{-- Card header --}}
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold text-gray-950 dark:text-white">{{ $server->name }}</h3>
                            <code class="block truncate text-xs text-gray-400">{{ $server->endpoint }}</code>
                        </div>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-400">
                            activo
                        </span>
                    </div>

                    {{-- Badges --}}
                    <div class="flex flex-wrap gap-2 px-4 pt-3">
                        <x-filament::badge color="success" icon="heroicon-m-wrench-screwdriver" size="sm">
                            {{ $server->tools->count() }} tools
                        </x-filament::badge>
                        <x-filament::badge color="warning" icon="heroicon-m-document-text" size="sm">
                            {{ $server->prompts->count() }} prompts
                        </x-filament::badge>
                        @if($server->transport)
                            <x-filament::badge color="gray" size="sm">{{ $server->transport }}</x-filament::badge>
                        @endif
                    </div>

                    {{-- Tools list --}}
                    @if($server->tools->isNotEmpty())
                        <div class="mt-3 px-4">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">Tools</p>
                            <div class="space-y-1">
                                @foreach($server->tools->take(5) as $tool)
                                    <div class="flex items-center justify-between gap-2 rounded-md bg-gray-50 px-2 py-1.5 dark:bg-gray-800">
                                        <div class="min-w-0">
                                            <span class="block truncate text-xs font-medium text-gray-700 dark:text-gray-300">{{ $tool->title }}</span>
                                            <code class="block truncate text-xs text-gray-400">{{ $tool->name }}</code>
                                        </div>
                                        <a
                                            href="{{ route('filament.admin.pages.tool-tester', ['tool' => $tool->id]) }}"
                                            class="shrink-0 rounded px-1.5 py-0.5 text-xs text-gray-400 hover:bg-gray-200 hover:text-gray-700 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                                            title="Probar tool"
                                        >▶</a>
                                    </div>
                                @endforeach
                                @if($server->tools->count() > 5)
                                    <p class="pt-0.5 text-xs text-gray-400">+{{ $server->tools->count() - 5 }} más</p>
                                @endif
                            </div>
                        </div>
                    @endif

                    {{-- Prompts list --}}
                    @if($server->prompts->isNotEmpty())
                        <div class="mt-3 px-4">
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400">Prompts</p>
                            <div class="space-y-1">
                                @foreach($server->prompts as $prompt)
                                    <a
                                        href="{{ route('filament.admin.resources.prompts.edit', ['record' => $prompt->id]) }}"
                                        class="flex items-center gap-2 rounded-md bg-warning-50 px-2 py-1.5 hover:bg-warning-100 dark:bg-warning-900/20 dark:hover:bg-warning-900/40"
                                    >
                                        <x-filament::icon icon="heroicon-m-document-text" class="h-3.5 w-3.5 shrink-0 text-warning-500" />
                                        <span class="truncate text-xs font-medium text-warning-700 dark:text-warning-400">{{ $prompt->title }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Card footer actions --}}
                    <div class="mt-auto flex gap-2 border-t border-gray-100 p-4 dark:border-gray-800">
                        <x-filament::button
                            href="{{ \App\Filament\Pages\McpBusinessHub::chatUrl($server) }}"
                            tag="a"
                            icon="heroicon-m-chat-bubble-left-right"
                            size="sm"
                            color="primary"
                            class="flex-1 justify-center"
                        >
                            Chat
                        </x-filament::button>
                        <x-filament::button
                            href="{{ route('filament.admin.pages.mcp-inspector', ['server' => $server->id]) }}"
                            tag="a"
                            icon="heroicon-m-magnifying-glass-circle"
                            size="sm"
                            color="gray"
                            outlined
                        >
                            Inspect
                        </x-filament::button>
                        <x-filament::button
                            href="{{ route('filament.admin.resources.servers.edit', ['record' => $server->id]) }}"
                            tag="a"
                            icon="heroicon-m-pencil-square"
                            size="sm"
                            color="gray"
                            outlined
                        >
                            Edit
                        </x-filament::button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    {{-- Mermaid diagram --}}
    <div class="mt-6 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Diagrama de conexiones</p>
            <span class="text-xs text-gray-400">Nova → Agentes → Tools → Prompts</span>
        </div>
        <div class="overflow-x-auto">
            <pre class="mermaid text-xs">{{ $mermaid }}</pre>
        </div>
    </div>
    <script type="module">
        let _mermaid = null;

        async function getMermaid() {
            if (_mermaid) return _mermaid;
            const { default: m } = await import('https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs');
            _mermaid = m;
            return m;
        }

        async function initMermaid() {
            const els = document.querySelectorAll('.mermaid:not([data-processed="true"])');
            if (!els.length) return;
            const mermaid = await getMermaid();
            const dark = document.documentElement.classList.contains('dark');
            mermaid.initialize({ startOnLoad: false, theme: dark ? 'dark' : 'default', securityLevel: 'loose', flowchart: { curve: 'basis', useMaxWidth: false } });
            els.forEach(el => { if (!el.dataset.original) el.dataset.original = el.textContent; });
            await mermaid.run({ querySelector: '.mermaid:not([data-processed="true"])' });
        }

        // Re-render on dark mode toggle
        const observer = new MutationObserver(async () => {
            const mermaid = await getMermaid();
            const dark = document.documentElement.classList.contains('dark');
            mermaid.initialize({ startOnLoad: false, theme: dark ? 'dark' : 'default', securityLevel: 'loose' });
            document.querySelectorAll('.mermaid[data-processed="true"]').forEach(el => {
                el.removeAttribute('data-processed');
                el.innerHTML = el.dataset.original || el.textContent;
            });
            mermaid.run();
        });
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        //initMermaid();
        document.addEventListener('livewire:navigated', initMermaid);
    </script>

</x-filament-panels::page>
