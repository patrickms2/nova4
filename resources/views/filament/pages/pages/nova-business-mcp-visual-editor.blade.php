<x-filament-panels::page>
    <div
        x-data="mcpVisualEditor()"
        x-init="initEditor()"
        class="mcp-visual-editor"
    >
        {{-- Header with filters --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Editor Visual MCP</h1>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $business->name }}</p>
            </div>
            <div class="flex items-center gap-2">
                <div class="relative">
                    <input
                        type="text"
                        x-model="searchQuery"
                        placeholder="Buscar servers, tools, resources..."
                        class="w-64 rounded-lg border border-gray-300 bg-white px-4 py-2 pl-10 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    >
                    <svg class="absolute left-3 top-2.5 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <select
                    x-model="filterType"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                >
                    <option value="all">Todos</option>
                    <option value="tools">Tools</option>
                    <option value="resources">Resources</option>
                    <option value="prompts">Prompts</option>
                </select>
                <button
                    @click="expandAll()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
                >
                    Expandir todo
                </button>
                <button
                    @click="collapseAll()"
                    class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-900 dark:hover:bg-gray-800"
                >
                    Colapsar todo
                </button>
            </div>
        </div>

        {{-- Stats bar --}}
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Servers</p>
                <p class="mt-1 text-2xl font-bold text-primary-600 dark:text-primary-400">{{ $servers->count() }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tools</p>
                <p class="mt-1 text-2xl font-bold text-success-600 dark:text-success-400">{{ $servers->sum(fn($s) => $s->tools->count()) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Resources</p>
                <p class="mt-1 text-2xl font-bold text-info-600 dark:text-info-400">{{ $servers->sum(fn($s) => $s->resources->count()) }}</p>
            </div>
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Prompts</p>
                <p class="mt-1 text-2xl font-bold text-warning-600 dark:text-warning-400">{{ $servers->sum(fn($s) => $s->prompts->count()) }}</p>
            </div>
        </div>

        {{-- Servers tree --}}
        <div class="space-y-4">
            @foreach($servers as $server)
                <div
                    x-data="serverNode({{ json_encode($server) }})"
                    class="mcp-server-node rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900"
                >
                    {{-- Server header --}}
                    <div
                        @click="toggle()"
                        class="flex cursor-pointer items-center justify-between gap-3 border-b border-gray-100 p-4 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-800"
                    >
                        <div class="flex items-center gap-3">
                            <svg
                                x-show="!expanded"
                                x-transition
                                class="h-5 w-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                            <svg
                                x-show="expanded"
                                x-transition
                                class="h-5 w-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <div class="h-10 w-10 rounded-lg bg-primary-100 flex items-center justify-center dark:bg-primary-900">
                                <svg class="h-6 w-6 text-primary-600 dark:text-primary-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">{{ $server->name }}</h3>
                                <code class="text-xs text-gray-400">{{ $server->endpoint ?? $server->type }}</code>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-400">
                                {{ $server->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            <div class="flex gap-1">
                                <span class="rounded bg-success-50 px-1.5 py-0.5 text-xs text-success-700 dark:bg-success-900/20 dark:text-success-400">
                                    {{ $server->tools->count() }} tools
                                </span>
                                <span class="rounded bg-info-50 px-1.5 py-0.5 text-xs text-info-700 dark:bg-info-900/20 dark:text-info-400">
                                    {{ $server->resources->count() }} resources
                                </span>
                                <span class="rounded bg-warning-50 px-1.5 py-0.5 text-xs text-warning-700 dark:bg-warning-900/20 dark:text-warning-400">
                                    {{ $server->prompts->count() }} prompts
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Server content (expandable) --}}
                    <div
                        x-show="expanded"
                        x-collapse
                        class="border-t border-gray-100 p-4 dark:border-gray-800"
                    >
                        {{-- Description --}}
                        @if($server->description)
                            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ $server->description }}</p>
                        @endif

                        {{-- Tabs for Tools/Resources/Prompts --}}
                        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
                            <nav class="-mb-px flex gap-4">
                                <button
                                    @click="activeTab = 'tools'"
                                    :class="activeTab === 'tools' ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors"
                                >
                                    Tools ({{ $server->tools->count() }})
                                </button>
                                <button
                                    @click="activeTab = 'resources'"
                                    :class="activeTab === 'resources' ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors"
                                >
                                    Resources ({{ $server->resources->count() }})
                                </button>
                                <button
                                    @click="activeTab = 'prompts'"
                                    :class="activeTab === 'prompts' ? 'border-primary-500 text-primary-600 dark:border-primary-400 dark:text-primary-400' : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                    class="border-b-2 px-1 py-2 text-sm font-medium transition-colors"
                                >
                                    Prompts ({{ $server->prompts->count() }})
                                </button>
                            </nav>
                        </div>

                        {{-- Tools tab --}}
                        <div x-show="activeTab === 'tools'" class="space-y-3">
                            @if($server->tools->isEmpty())
                                <p class="text-sm text-gray-400 italic">No tools configured.</p>
                            @else
                                @foreach($server->tools as $tool)
                                    <div
                                        x-data="toolNode({{ json_encode($tool) }})"
                                        class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $tool->title ?? $tool->name }}</h4>
                                                    @if($tool->is_active)
                                                        <span class="rounded-full bg-success-100 px-1.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-400">Active</span>
                                                    @endif
                                                </div>
                                                <code class="text-xs text-gray-400">{{ $tool->name }}</code>
                                                @if($tool->description)
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $tool->description }}</p>
                                                @endif
                                            </div>
                                            <button
                                                @click="showDetails = !showDetails"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div x-show="showDetails" x-collapse class="mt-3 space-y-3">
                                            @if($tool->input_schema)
                                                <div>
                                                    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Input Schema</p>
                                                    <pre class="overflow-x-auto rounded bg-gray-900 p-2 text-xs text-gray-100">{{ json_encode($tool->input_schema, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if($tool->output_schema)
                                                <div>
                                                    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Output Schema</p>
                                                    <pre class="overflow-x-auto rounded bg-gray-900 p-2 text-xs text-gray-100">{{ json_encode($tool->output_schema, JSON_PRETTY_PRINT) }}</pre>
                                                </div>
                                            @endif
                                            @if($tool->annotations)
                                                <div>
                                                    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Annotations</p>
                                                    <div class="flex flex-wrap gap-1">
                                                        @foreach($tool->annotations as $key => $value)
                                                            @if($value)
                                                                <span class="rounded bg-primary-50 px-1.5 py-0.5 text-xs text-primary-700 dark:bg-primary-900/20 dark:text-primary-400">{{ $key }}</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- Resources tab --}}
                        <div x-show="activeTab === 'resources'" class="space-y-3">
                            @if($server->resources->isEmpty())
                                <p class="text-sm text-gray-400 italic">No resources configured.</p>
                            @else
                                @foreach($server->resources as $resource)
                                    <div
                                        x-data="resourceNode({{ json_encode($resource) }})"
                                        class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $resource->title ?? $resource->name }}</h4>
                                                    @if($resource->is_active)
                                                        <span class="rounded-full bg-success-100 px-1.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-400">Active</span>
                                                    @endif
                                                </div>
                                                <code class="text-xs text-gray-400">{{ $resource->name }}</code>
                                                @if($resource->description)
                                                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $resource->description }}</p>
                                                @endif
                                            </div>
                                            <button
                                                @click="showDetails = !showDetails"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div x-show="showDetails" x-collapse class="mt-3 space-y-3">
                                            <div>
                                                <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">URI</p>
                                                <code class="block rounded bg-gray-900 p-2 text-xs text-gray-100">{{ $resource->uri }}</code>
                                            </div>
                                            @if($resource->uri_template)
                                                <div>
                                                    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">URI Template</p>
                                                    <code class="block rounded bg-gray-900 p-2 text-xs text-gray-100">{{ $resource->uri_template }}</code>
                                                </div>
                                            @endif
                                            @if($resource->mime_type)
                                                <div>
                                                    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">MIME Type</p>
                                                    <span class="rounded bg-info-50 px-1.5 py-0.5 text-xs text-info-700 dark:bg-info-900/20 dark:text-info-400">{{ $resource->mime_type }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>

                        {{-- Prompts tab --}}
                        <div x-show="activeTab === 'prompts'" class="space-y-3">
                            @if($server->prompts->isEmpty())
                                <p class="text-sm text-gray-400 italic">No prompts configured.</p>
                            @else
                                @foreach($server->prompts as $prompt)
                                    <div
                                        x-data="promptNode({{ json_encode($prompt) }})"
                                        class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-gray-700 dark:bg-gray-800"
                                    >
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <h4 class="font-medium text-gray-900 dark:text-white">{{ $prompt->title ?? $prompt->name }}</h4>
                                                    @if($prompt->is_active)
                                                        <span class="rounded-full bg-success-100 px-1.5 py-0.5 text-xs font-medium text-success-700 dark:bg-success-900/40 dark:text-success-400">Active</span>
                                                    @endif
                                                </div>
                                                <code class="text-xs text-gray-400">{{ $prompt->name }}</code>
                                            </div>
                                            <button
                                                @click="showDetails = !showDetails"
                                                class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                                            >
                                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div x-show="showDetails" x-collapse class="mt-3">
                                            @if($prompt->instructions)
                                                <div>
                                                    <p class="mb-1 text-xs font-medium text-gray-500 dark:text-gray-400">Instructions</p>
                                                    <p class="rounded bg-gray-900 p-2 text-sm text-gray-100">{{ $prompt->instructions }}</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        @if($servers->isEmpty())
            <div class="flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-gray-300 py-12 dark:border-gray-700">
                <svg class="h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No MCP servers configured</p>
                <a
                    href="{{ route('filament.admin.resources.nova-businesses.mcp', ['record' => $business->id]) }}"
                    class="mt-2 text-sm text-primary-600 hover:underline dark:text-primary-400"
                >
                    Create your first server →
                </a>
            </div>
        @endif
    </div>

    <script>
        function mcpVisualEditor() {
            return {
                searchQuery: '',
                filterType: 'all',
                initEditor() {
                    // Initialize any global state
                },
                expandAll() {
                    document.querySelectorAll('[x-data*="serverNode"]').forEach(el => {
                        el._x_dataStack[0].expanded = true;
                    });
                },
                collapseAll() {
                    document.querySelectorAll('[x-data*="serverNode"]').forEach(el => {
                        el._x_dataStack[0].expanded = false;
                    });
                }
            }
        }

        function serverNode(server) {
            return {
                expanded: false,
                activeTab: 'tools',
                toggle() {
                    this.expanded = !this.expanded;
                }
            }
        }

        function toolNode(tool) {
            return {
                showDetails: false
            }
        }

        function resourceNode(resource) {
            return {
                showDetails: false
            }
        }

        function promptNode(prompt) {
            return {
                showDetails: false
            }
        }
    </script>
</x-filament-panels::page>
