<div class="space-y-6">
    {{-- Server Selector --}}
    <x-filament::section :compact="true">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        wire:model.live="serverId"
                        wire:change="loadServer"
                    >
                        <option value="">Choose a server...</option>
                        @foreach($servers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->endpoint }})</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
       
        </div>
    </x-filament::section>

    {{-- Connection Status --}}
    @if($server)
        <x-filament::section>
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-xl {{ $isConnected ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30' }}">
                        @if($isConnected)
                            <x-mcp-status :connected="true" size="lg" />
                        @else
                            <x-filament::icon icon="heroicon-m-x-circle" class="h-8 w-8 text-red-600 dark:text-red-400" />
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $server->name }}</h3>
                            <x-filament::badge :color="$isConnected ? 'success' : 'danger'" size="sm">
                                {{ $isConnected ? 'Connected' : 'Disconnected' }}
                            </x-filament::badge>
                        </div>
                        <div class="mt-1 flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-cube" class="h-4 w-4" />
                                v{{ $server->version }}
                            </span>
                            <span class="flex items-center gap-1">
                                <x-filament::icon icon="heroicon-m-signal" class="h-4 w-4" />
                                {{ ucfirst($server->transport) }}
                            </span>
                        </div>
                    </div>
                </div>
                <code class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 text-sm dark:bg-gray-800">
                    <x-filament::icon icon="heroicon-m-link" class="h-4 w-4 text-gray-400" />
                    {{ $server->endpoint }}
                </code>
            </div>

            @if($error)
                <div class="mt-4 flex items-start gap-3 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                    <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-5 w-5 flex-shrink-0 text-red-500" />
                    <div>
                        <h4 class="font-medium text-red-800 dark:text-red-300">Connection Error</h4>
                        <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
                    </div>
                </div>
            @endif
        </x-filament::section>

        {{-- Tabs --}}
        <x-filament::tabs>
            <x-filament::tabs.item
                :active="$activeTab === 'overview'"
                wire:click="$set('activeTab', 'overview')"
                icon="heroicon-m-information-circle"
            >
                Overview
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'tools'"
                wire:click="$set('activeTab', 'tools')"
                icon="heroicon-m-wrench-screwdriver"
                :badge="count($toolsList)"
            >
                Tools
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'resources'"
                wire:click="$set('activeTab', 'resources')"
                icon="heroicon-m-document-text"
                :badge="count($resourcesList)"
            >
                Resources
            </x-filament::tabs.item>

            <x-filament::tabs.item
                :active="$activeTab === 'prompts'"
                wire:click="$set('activeTab', 'prompts')"
                icon="heroicon-m-chat-bubble-left-right"
                :badge="count($promptsList)"
            >
                Prompts
            </x-filament::tabs.item>
        </x-filament::tabs>

        <div class="mcp-tab-content">
            {{-- Overview Tab --}}
            @if($activeTab === 'overview')
                <x-filament::section>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                    <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Tools</dt>
                                    <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($toolsList) }}</dd>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-100 dark:bg-info-900/30">
                                    <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-info-600 dark:text-info-400" />
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Resources</dt>
                                    <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($resourcesList) }}</dd>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-100 dark:bg-warning-900/30">
                                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Prompts</dt>
                                    <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ count($promptsList) }}</dd>
                                </div>
                            </div>
                        </div>
                        <div class="rounded-xl bg-gray-50 p-4 dark:bg-gray-800/50">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-success-100 dark:bg-success-900/30">
                                    <x-filament::icon icon="heroicon-o-signal" class="h-5 w-5 text-success-600 dark:text-success-400" />
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Transport</dt>
                                    <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ ucfirst($server->transport) }}</dd>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($server->instructions)
                        <div class="mt-6">
                            <h4 class="mb-3 flex items-center gap-2 font-medium text-gray-900 dark:text-white">
                                <x-filament::icon icon="heroicon-m-document-text" class="h-5 w-5 text-gray-400" />
                                Server Instructions
                            </h4>
                            <div class="prose prose-sm max-w-none rounded-lg bg-gray-50 p-4 dark:prose-invert dark:bg-gray-800/50">
                                {!! \Illuminate\Support\Str::markdown($server->instructions) !!}
                            </div>
                        </div>
                    @endif

                    @if(!empty($capabilities))
                        <div class="mt-6">
                            <h4 class="mb-3 flex items-center gap-2 font-medium text-gray-900 dark:text-white">
                               <x-filament::icon icon="heroicon-m-cog-6-tooth" class="h-5 w-5 text-gray-400" />
                                Capabilities
                            </h4>
                            <x-mcp-code-block :code="$capabilities" language="json" max-height="400px" />
                        </div>
                    @endif
                </x-filament::section>
            @endif

            {{-- Tools Tab --}}
            @if($activeTab === 'tools')
                <x-filament::section>
                    @forelse($toolsList as $tool)
                        <div class="mcp-tool-card mb-4 last:mb-0 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                        <x-filament::icon icon="heroicon-o-wrench-screwdriver" class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $tool['title'] }}</h4>
                                        <code class="text-sm text-gray-500 dark:text-gray-400">{{ $tool['name'] }}</code>
                                    </div>
                                </div>
                                <x-filament::button
                                    href="{{ route('filament.admin.pages.tool-tester', ['tool' => $tool['id']]) }}"
                                    tag="a"
                                    size="sm"
                                    color="primary"
                                    outlined
                                    icon="heroicon-m-play"
                                >
                                    Test
                                </x-filament::button>
                            </div>
                            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ $tool['description'] }}</p>
                            @if(!empty($tool['input_schema']))
                                <div class="mt-4">
                                    <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Parameters</span>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        @foreach($tool['input_schema'] as $name => $config)
                                            <x-filament::badge
                                                :color="($config['required'] ?? false) ? 'danger' : 'gray'"
                                                size="sm"
                                            >
                                                {{ $name }}: {{ $config['type'] ?? 'string' }}
                                                @if($config['required'] ?? false)
                                                    <span class="ml-1">*</span>
                                                @endif
                                            </x-filament::badge>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <x-mcp-empty-state
                            icon="heroicon-o-wrench-screwdriver"
                            heading="No tools defined"
                            description="This server has no tools registered."
                        />
                    @endforelse
                </x-filament::section>
            @endif

            {{-- Resources Tab --}}
            @if($activeTab === 'resources')
                <x-filament::section>
                    @forelse($resourcesList as $resource)
                        <div class="mcp-tool-card mb-4 last:mb-0 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start gap-3">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-info-100 dark:bg-info-900/30">
                                        <x-filament::icon icon="heroicon-o-document-text" class="h-5 w-5 text-info-600 dark:text-info-400" />
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-gray-900 dark:text-white">{{ $resource['title'] }}</h4>
                                        <code class="text-sm text-gray-500 dark:text-gray-400">{{ $resource['uri'] }}</code>
                                    </div>
                                </div>
                                <x-filament::badge color="info" size="sm">
                                    {{ $resource['mime_type'] }}
                                </x-filament::badge>
                            </div>
                            <p class="mt-3 text-sm text-gray-600 dark:text-gray-400">{{ $resource['description'] }}</p>
                        </div>
                    @empty
                        <x-mcp-empty-state
                            icon="heroicon-o-document-text"
                            heading="No resources defined"
                            description="This server has no resources registered."
                        />
                    @endforelse
                </x-filament::section>
            @endif

            {{-- Prompts Tab --}}
            @if($activeTab === 'prompts')
                <x-filament::section>
                    @forelse($promptsList as $prompt)
                        <div class="mcp-tool-card mb-4 last:mb-0 rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-warning-100 dark:bg-warning-900/30">
                                    <x-filament::icon icon="heroicon-o-chat-bubble-left-right" class="h-5 w-5 text-warning-600 dark:text-warning-400" />
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $prompt['title'] }}</h4>
                                    <code class="text-sm text-gray-500 dark:text-gray-400">{{ $prompt['name'] }}</code>
                                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $prompt['description'] }}</p>
                                    @if(!empty($prompt['arguments']))
                                        <div class="mt-3">
                                            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Arguments</span>
                                            <div class="mt-2 flex flex-wrap gap-2">
                                                @foreach($prompt['arguments'] as $arg)
                                                    <x-filament::badge
                                                        :color="($arg['required'] ?? false) ? 'danger' : 'gray'"
                                                        size="sm"
                                                    >
                                                        {{ $arg['name'] }}
                                                        @if($arg['required'] ?? false)
                                                            <span class="ml-1">*</span>
                                                        @endif
                                                    </x-filament::badge>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-mcp-empty-state
                            icon="heroicon-o-chat-bubble-left-right"
                            heading="No prompts defined"
                            description="This server has no prompts registered."
                        />
                    @endforelse
                </x-filament::section>
            @endif
        </div>
    @else
        {{-- Empty State --}}
        <x-filament::section>
            <x-mcp-empty-state
                icon="heroicon-m-server-stack"
                heading="Select a Server"
                description="Choose an MCP server from the dropdown above to inspect its capabilities, tools, resources, and prompts."
            />
        </x-filament::section>
    @endif
</div>
