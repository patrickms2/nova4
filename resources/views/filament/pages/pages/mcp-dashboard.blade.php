<x-filament-panels::page>
    {{-- Stats Grid --}}
    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <a href="{{ route('filament.admin.resources.servers.index') }}" class="mcp-stat-card block">
            <x-filament::section :compact="true">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-primary-100 ring-4 ring-primary-50 dark:bg-primary-900 dark:ring-primary-900/50">
                        <x-heroicon-m-server-stack class="h-6 w-6 text-primary-600 dark:text-primary-400"/>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Servers</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['activeServers'] }} <span
                                class="text-base font-normal text-gray-400">/ {{ $stats['servers'] }}</span></p>
                    </div>
                </div>
            </x-filament::section>
        </a>

        <a href="{{ route('filament.admin.resources.tools.index') }}" class="mcp-stat-card block">
            <x-filament::section :compact="true">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-success-100 ring-4 ring-success-50 dark:bg-success-900 dark:ring-success-900/50">
                        <x-heroicon-o-wrench-screwdriver class="h-6 w-6 text-success-600 dark:text-success-400"/>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Tools</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['activeTools'] }} <span
                                class="text-base font-normal text-gray-400">/ {{ $stats['tools'] }}</span></p>
                    </div>
                </div>
            </x-filament::section>
        </a>

        <a href="{{ route('filament.admin.resources.resources.index') }}" class="mcp-stat-card block">
            <x-filament::section :compact="true">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-info-100 ring-4 ring-info-50 dark:bg-info-900 dark:ring-info-900/50">
                        <x-heroicon-o-document-text class="h-6 w-6 text-info-600 dark:text-info-400"/>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Resources</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['resources'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        </a>

        <a href="{{ route('filament.admin.resources.prompts.index') }}" class="mcp-stat-card block">
            <x-filament::section :compact="true">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-lg bg-warning-100 ring-4 ring-warning-50 dark:bg-warning-900 dark:ring-warning-900/50">
                        <x-heroicon-o-chat-bubble-left-right class="h-6 w-6 text-warning-600 dark:text-warning-400"/>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Prompts</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['prompts'] }}</p>
                    </div>
                </div>
            </x-filament::section>
        </a>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Active Servers --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-m-server-stack class="h-5 w-5 text-gray-400"/>
                    <span>Active Servers</span>
                </div>
            </x-slot>
            <x-slot name="headerEnd">
                <x-filament::link href="{{ route('filament.admin.resources.servers.index') }}" size="sm"
                                  icon="heroicon-m-arrow-right" icon-position="after">
                    View All
                </x-filament::link>
            </x-slot>

            @if($servers->isEmpty())
                <x-mcp-empty-state
                    icon="heroicon-m-server-stack"
                    heading="No active servers"
                    description="Get started by creating your first MCP server."
                >
                    <x-slot name="actions">
                        <x-filament::button
                            href="{{ route('filament.admin.resources.servers.create') }}"
                            tag="a"
                            icon="heroicon-m-plus"
                            size="sm"
                        >
                            Create Server
                        </x-filament::button>
                    </x-slot>
                </x-mcp-empty-state>
            @else
                <div class="space-y-3">
                    @foreach($servers as $server)
                        <div
                            class="mcp-server-card flex items-center justify-between rounded-lg border-2 border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-center gap-3">
                                <x-mcp-status :connected="true" size="md"/>
                                <div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">{{ $server->name }}</h4>
                                    <code
                                        class="text-xs text-gray-500 dark:text-gray-400">{{ $server->endpoint }}</code>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-filament::badge color="primary" icon="heroicon-m-wrench-screwdriver" size="sm">
                                    {{ $server->tools_count }}
                                </x-filament::badge>
                                <x-filament::badge color="info" icon="heroicon-m-document-text" size="sm">
                                    {{ $server->resources_count }}
                                </x-filament::badge>
                                <x-filament::badge color="warning" icon="heroicon-m-chat-bubble-left-right" size="sm">
                                    {{ $server->prompts_count }}
                                </x-filament::badge>
                                <x-filament::button
                                    href="{{ route('filament.admin.pages.mcp-inspector', ['server' => $server->id]) }}"
                                    tag="a"
                                    size="xs"
                                    color="gray"
                                    outlined
                                >
                                    Inspect
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- Top Tools --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-heroicon-o-wrench-screwdriver class="h-5 w-5 text-gray-400"/>
                    <span>Most Used Tools</span>
                </div>
            </x-slot>
            <x-slot name="headerEnd">
                <x-filament::link href="{{ route('filament.admin.resources.tools.index') }}" size="sm"
                                  icon="heroicon-m-arrow-right" icon-position="after">
                    View All
                </x-filament::link>
            </x-slot>

            @if($topTools->isEmpty())
                <x-mcp-empty-state
                    icon="heroicon-o-wrench-screwdriver"
                    heading="No tools yet"
                    description="Tools will appear here once you create them."
                >
                    <x-slot name="actions">
                        <x-filament::button
                            href="{{ route('filament.admin.resources.tools.create') }}"
                            tag="a"
                            icon="heroicon-m-plus"
                            size="sm"
                        >
                            Create Tool
                        </x-filament::button>
                    </x-slot>
                </x-mcp-empty-state>
            @else
                <div class="space-y-3">
                    @foreach($topTools as $tool)
                        <div
                            class="mcp-tool-card flex items-center justify-between rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                            <div>
                                <h4 class="font-medium text-gray-900 dark:text-white">{{ $tool->title }}</h4>
                                <code class="text-xs text-gray-500 dark:text-gray-400">{{ $tool->name }}</code>
                            </div>
                            <div class="flex items-center gap-3">
                                <x-filament::badge color="gray" size="sm">
                                    {{ $tool->logs_count }} calls
                                </x-filament::badge>
                                <x-filament::button
                                    href="{{ route('filament.admin.pages.tool-tester', ['tool' => $tool->id]) }}"
                                    tag="a"
                                    size="xs"
                                    color="gray"
                                    outlined
                                >
                                    Test
                                </x-filament::button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Recent Activity --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-clock class="h-5 w-5 text-gray-400"/>
                <span>Recent Activity</span>
            </div>
        </x-slot>
        <x-slot name="headerEnd">
            <x-filament::link href="{{ route('filament.admin.pages.mcp-log-viewer') }}" size="sm"
                              icon="heroicon-m-arrow-right" icon-position="after">
                View All Logs
            </x-filament::link>
        </x-slot>

        @if($recentLogs->isEmpty())
            <x-mcp-empty-state
                icon="heroicon-o-clock"
                heading="No activity yet"
                description="Tool calls will appear here when MCP tools are executed."
            />
        @else
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Time
                        </th>
                        <th class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Type
                        </th>
                        <th class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Server
                        </th>
                        <th class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Tool
                        </th>
                        <th class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Duration
                        </th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($recentLogs as $log)
                        <tr class="mcp-log-row">
                            <td class="fi-ta-cell whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400"
                                title="{{ $log->created_at }}">
                                {{ $log->created_at->diffForHumans() }}
                            </td>
                            <td class="fi-ta-cell whitespace-nowrap px-4 py-3">
                                @if($log->type === 'error')
                                    <x-filament::badge color="danger" icon="heroicon-m-x-circle" size="sm">
                                        Error
                                    </x-filament::badge>
                                @elseif($log->type === 'request')
                                    <x-filament::badge color="info" icon="heroicon-m-arrow-up-circle" size="sm">
                                        Request
                                    </x-filament::badge>
                                @else
                                    <x-filament::badge color="success" icon="heroicon-m-check-circle" size="sm">
                                        {{ ucfirst($log->type) }}
                                    </x-filament::badge>
                                @endif
                            </td>
                            <td class="fi-ta-cell whitespace-nowrap px-4 py-3 text-sm text-gray-900 dark:text-white">
                                {{ $log->server?->name ?? '-' }}
                            </td>
                            <td class="fi-ta-cell whitespace-nowrap px-4 py-3 text-sm">
                                <code
                                    class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $log->tool?->name ?? '-' }}</code>
                            </td>
                            <td class="fi-ta-cell whitespace-nowrap px-4 py-3 text-sm {{ $log->duration_ms > 1000 ? 'mcp-slow-request' : 'text-gray-500 dark:text-gray-400' }}">
                                @if($log->duration_ms)
                                    @if($log->duration_ms > 1000)
                                        <span class="flex items-center gap-1">
                                                   <x-filament::icon icon="heroicon-o-exclamation-triangle"  class="h-4 w-4"/>

                                                {{ number_format($log->duration_ms) }}ms
                                            </span>
                                    @else
                                        {{ number_format($log->duration_ms) }}ms
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Quick Actions --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-filament::icon icon="heroicon-o-bolt" class="h-5 w-5 text-gray-400"/>
                <span>Quick Actions</span>
            </div>
        </x-slot>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <x-filament::button
                href="{{ route('filament.admin.pages.mcp-business-hub') }}"
                tag="a"
                color="primary"
                outlined
                icon="heroicon-m-briefcase"
                class="mcp-action-card justify-start"
            >
                Business Hub
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.servers.create') }}"
                tag="a"
                color="primary"
                outlined
                icon="heroicon-m-plus"
                class="mcp-action-card justify-start"
            >
                New Server
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.tools.create') }}"
                tag="a"
                color="success"
                outlined
                icon="heroicon-m-plus"
                class="mcp-action-card justify-start"
            >
                New Tool
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.resources.create') }}"
                tag="a"
                color="info"
                outlined
                icon="heroicon-m-plus"
                class="mcp-action-card justify-start"
            >
                New Resource
            </x-filament::button>

            <x-filament::button
                href="{{ route('filament.admin.resources.prompts.create') }}"
                tag="a"
                color="warning"
                outlined
                icon="heroicon-m-plus"
                class="mcp-action-card justify-start"
            >
                New Prompt
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
