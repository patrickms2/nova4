<div class="space-y-6">
    {{-- Filters --}}
    <x-filament::section :compact="true">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Server Filter --}}
            <div>
                <label for="server-filter" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Server</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        id="server-filter"
                        wire:model.live="serverId"
                    >
                        <option value="">All Servers</option>
                        @foreach(\App\Models\Server::all() as $server)
                            <option value="{{ $server->id }}">{{ $server->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            {{-- Type Filter --}}
            <div>
                <label for="type-filter" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Type</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        id="type-filter"
                        wire:model.live="typeFilter"
                    >
                        <option value="">All Types</option>
                        <option value="request">Request</option>
                        <option value="response">Response</option>
                        <option value="error">Error</option>
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            {{-- Search --}}
            <div>
                <label for="search" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Search</label>
                <x-filament::input.wrapper suffix-icon="heroicon-m-magnifying-glass">
                    <x-filament::input
                        type="text"
                        id="search"
                        wire:model.live.debounce.300ms="searchQuery"
                        placeholder="Search logs..."
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- Auto Refresh Toggle --}}
            <div class="flex items-end">
                <label class="flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2.5 dark:border-gray-700">
                    <x-filament::input.checkbox
                        wire:model.live="autoRefresh"
                    />
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Auto-refresh</span>
                    @if($autoRefresh)
                        <x-filament::loading-indicator class="h-4 w-4 text-primary-500" wire:loading.delay />
                    @endif
                </label>
            </div>

            {{-- Refresh Button --}}
            <div class="flex items-end">
                <x-filament::button
                    wire:click="$refresh"
                    wire:loading.attr="disabled"
                    icon="heroicon-m-arrow-path"
                    color="gray"
                    class="w-full"
                >
                    <span wire:loading.remove>Refresh</span>
                    <span wire:loading>Refreshing...</span>
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- Logs Table --}}
    <x-filament::section :compact="true">
        <div class="overflow-x-auto">
            <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-white/5">
                    <tr>
                        <th scope="col" class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Time
                        </th>
                        <th scope="col" class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Type
                        </th>
                        <th scope="col" class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Server / Tool
                        </th>
                        <th scope="col" class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Method
                        </th>
                        <th scope="col" class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Duration
                        </th>
                        <th scope="col" class="fi-ta-header-cell px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($logs as $log)
                        <tr class="mcp-log-row" wire:key="log-{{ $log->id }}">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400" title="{{ $log->created_at }}">
                                <span class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-m-clock" class="h-4 w-4 text-gray-400" />
                                    {{ $log->created_at->diffForHumans() }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @switch($log->type)
                                    @case('request')
                                        <x-filament::badge color="info" size="sm" icon="heroicon-m-arrow-up-circle">
                                            Request
                                        </x-filament::badge>
                                        @break
                                    @case('response')
                                        <x-filament::badge color="success" size="sm" icon="heroicon-m-arrow-down-circle">
                                            Response
                                        </x-filament::badge>
                                        @break
                                    @case('error')
                                        <x-filament::badge color="danger" size="sm" icon="heroicon-m-x-circle">
                                            Error
                                        </x-filament::badge>
                                        @break
                                @endswitch
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <div class="flex items-center gap-2">
                                    <x-filament::icon icon="heroicon-m-server-stack" class="h-4 w-4 text-gray-400" />
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ $log->server?->name ?? 'Unknown' }}
                                        </div>
                                        @if($log->tool)
                                            <div class="flex items-center gap-1 text-gray-500 dark:text-gray-400">
                                               <x-filament::icon icon="heroicon-m-wrench-screwdriver" class="h-3 w-3" />
                                                <code class="text-xs">{{ $log->tool->name }}</code>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <code class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $log->method ?? '-' }}</code>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                @if($log->duration_ms)
                                    @if($log->duration_ms > 1000)
                                        <span class="mcp-slow-request flex items-center gap-1">
                                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4" />
                                            {{ number_format($log->duration_ms) }}ms
                                        </span>
                                    @else
                                        <span class="text-gray-500 dark:text-gray-400">
                                            {{ number_format($log->duration_ms) }}ms
                                        </span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm">
                                <x-filament::button
                                    size="xs"
                                    color="gray"
                                    outlined
                                    icon="heroicon-m-eye"
                                    x-on:click="$dispatch('open-modal', { id: 'log-detail-{{ $log->id }}' })"
                                >
                                    View
                                </x-filament::button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <x-mcp-empty-state
                                    icon="heroicon-o-document-magnifying-glass"
                                    heading="No logs found"
                                    description="Logs will appear here when MCP tools are called."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                {{ $logs->links() }}
            </div>
        @endif
    </x-filament::section>

    {{-- Log Detail Modals --}}
    @foreach($logs as $log)
        <x-filament::modal id="log-detail-{{ $log->id }}" width="3xl" slide-over>
            <x-slot name="heading">
                <div class="flex items-center gap-3">
                    @switch($log->type)
                        @case('request')
                            <x-filament::badge color="info" size="lg" icon="heroicon-m-arrow-up-circle">
                                Request
                            </x-filament::badge>
                            @break
                        @case('response')
                            <x-filament::badge color="success" size="lg" icon="heroicon-m-arrow-down-circle">
                                Response
                            </x-filament::badge>
                            @break
                        @case('error')
                            <x-filament::badge color="danger" size="lg" icon="heroicon-m-x-circle">
                                Error
                            </x-filament::badge>
                            @break
                    @endswitch
                    <span class="text-gray-500 dark:text-gray-400">Log Details</span>
                </div>
            </x-slot>

            <div class="space-y-6">
                {{-- Metadata Grid --}}
                <div class="grid grid-cols-2 gap-4 rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Timestamp</span>
                        <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $log->created_at }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Server</span>
                        <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $log->server?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Tool</span>
                        <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $log->tool?->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">IP Address</span>
                        <p class="mt-1 font-medium text-gray-900 dark:text-white">{{ $log->ip_address ?? 'N/A' }}</p>
                    </div>
                    @if($log->duration_ms)
                        <div>
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Duration</span>
                            <p class="mt-1 font-medium {{ $log->duration_ms > 1000 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">
                                {{ number_format($log->duration_ms) }}ms
                            </p>
                        </div>
                    @endif
                    <div>
                        <span class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Method</span>
                        <p class="mt-1">
                            <code class="rounded bg-gray-200 px-2 py-1 text-sm dark:bg-gray-700">{{ $log->method ?? 'N/A' }}</code>
                        </p>
                    </div>
                </div>

                {{-- Error Message --}}
                @if($log->error_message)
                    <div>
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-medium text-red-600 dark:text-red-400">
                            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="h-4 w-4" />
                            Error Message
                        </h4>
                        <div class="rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                            <pre class="whitespace-pre-wrap text-sm text-red-800 dark:text-red-300">{{ $log->error_message }}</pre>
                        </div>
                    </div>
                @endif

                {{-- Request Data --}}
                @if($log->request_data)
                    <div>
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <x-filament::icon icon="heroicon-m-arrow-up-circle" class="h-4 w-4" />
                            Request Data
                        </h4>
                        <x-mcp-code-block :code="$log->request_data" language="json" max-height="250px" />
                    </div>
                @endif

                {{-- Response Data --}}
                @if($log->response_data)
                    <div>
                        <h4 class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                            <x-filament::icon icon="heroicon-m-arrow-down-circle" class="h-4 w-4" />
                            Response Data
                        </h4>
                        <x-mcp-code-block :code="$log->response_data" language="json" max-height="250px" />
                    </div>
                @endif
            </div>

            <x-slot name="footerActions">
                <x-filament::button
                    color="gray"
                    x-on:click="close"
                >
                    Close
                </x-filament::button>
            </x-slot>
        </x-filament::modal>
    @endforeach

    {{-- Auto Refresh Script --}}
    @if($autoRefresh)
        <script>
            setTimeout(function() {
                @this.call('$refresh');
            }, 5000);
        </script>
    @endif
</div>
