<x-filament-panels::page>

    {{-- Business infolist --}}
    {{ $this->infolist }}

  

    {{-- Stats bar --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 xl:grid-cols-7">
        @php
            $statItems = [
                ['label' => 'Servicios',   'value' => $stats['services'],    'color' => 'text-primary-600 dark:text-primary-400'],
                ['label' => 'MCP Servers', 'value' => $stats['mcp_servers'], 'color' => 'text-success-600 dark:text-success-400'],
                ['label' => 'Tools',       'value' => $stats['tools'],       'color' => 'text-success-600 dark:text-success-400'],
                ['label' => 'WhatsApp',    'value' => $stats['whatsapp'],    'color' => 'text-warning-600 dark:text-warning-400'],
                ['label' => 'Agentes IA',  'value' => $stats['ai_profiles'], 'color' => 'text-purple-600 dark:text-purple-400'],
                ['label' => 'Listing',     'value' => $stats['listing'],     'color' => 'text-gray-700 dark:text-gray-300'],
                ['label' => 'Integraciones','value' => $stats['integrations'],'color' => 'text-gray-700 dark:text-gray-300'],
            ];
        @endphp
        @foreach($statItems as $stat)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Servicios contratados --}}
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Servicios contratados</h2>
            <a href="{{ route('filament.admin.resources.nova-businesses.servicios', ['record' => $business->id]) }}"
               class="text-xs text-primary-600 hover:underline dark:text-primary-400">Gestionar →</a>
        </div>
        @if($services->isEmpty())
            <p class="text-sm text-gray-400 italic">Sin servicios registrados.</p>
        @else
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($services as $service)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-semibold text-gray-900 truncate dark:text-white">{{ $service->name }}</span>
                            <span @class([
                                'inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-400' => $service->status === 'active',
                                'bg-warning-100 text-warning-700 dark:bg-warning-900/40 dark:text-warning-400' => $service->status === 'paused',
                                'bg-danger-100 text-danger-700 dark:bg-danger-900/40 dark:text-danger-400' => $service->status === 'cancelled',
                                'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => !in_array($service->status, ['active','paused','cancelled']),
                            ])>{{ $service->status }}</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ $service->service_type }}</p>
                        <div class="mt-2 flex flex-wrap gap-1">
                            @if($service->has_whatsapp) <span class="rounded bg-warning-50 px-1.5 py-0.5 text-xs text-warning-700 dark:bg-warning-900/20 dark:text-warning-400">WA</span> @endif
                            @if($service->has_mcp)      <span class="rounded bg-success-50 px-1.5 py-0.5 text-xs text-success-700 dark:bg-success-900/20 dark:text-success-400">MCP</span> @endif
                            @if($service->has_development) <span class="rounded bg-primary-50 px-1.5 py-0.5 text-xs text-primary-700 dark:bg-primary-900/20 dark:text-primary-400">DEV</span> @endif
                            @if($service->monthly_amount) <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">€{{ number_format($service->monthly_amount, 0) }}/mes</span> @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- MCP Servers --}}
    @if($mcpServers->isNotEmpty())
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">MCP Servers</h2>
            <a href="{{ route('filament.admin.resources.nova-businesses.mcp', ['record' => $business->id]) }}"
               class="text-xs text-primary-600 hover:underline dark:text-primary-400">Ver todos →</a>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($mcpServers as $server)
                <div class="flex flex-col rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-start justify-between gap-3 border-b border-gray-100 p-4 dark:border-gray-800">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold text-gray-950 dark:text-white">{{ $server->name }}</h3>
                            <code class="block truncate text-xs text-gray-400">{{ $server->endpoint ?? $server->type }}</code>
                        </div>
                        <span @class([
                            'inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success-100 text-success-700 dark:bg-success-900/40 dark:text-success-400' => $server->status === 'active',
                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400' => $server->status !== 'active',
                        ])>{{ $server->status }}</span>
                    </div>
                    <div class="flex flex-wrap gap-2 p-4">
                        <x-filament::badge color="success" icon="heroicon-m-wrench-screwdriver" size="sm">
                            {{ $server->tools_count }} tools
                        </x-filament::badge>
                        <x-filament::badge color="warning" icon="heroicon-m-document-text" size="sm">
                            {{ $server->prompts_count }} prompts
                        </x-filament::badge>
                        @if($server->type)
                            <x-filament::badge color="gray" size="sm">{{ $server->type }}</x-filament::badge>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Agentes IA --}}
    @if($aiProfiles->isNotEmpty())
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Agentes IA</h2>
            <a href="{{ route('filament.admin.resources.nova-businesses.ia', ['record' => $business->id]) }}"
               class="text-xs text-primary-600 hover:underline dark:text-primary-400">Gestionar →</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($aiProfiles as $profile)
                <div class="rounded-xl border border-purple-200 bg-purple-50 p-4 dark:border-purple-800 dark:bg-purple-950/20">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-gray-900 truncate dark:text-white">{{ $profile->name }}</span>
                        <span @class([
                            'inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success-100 text-success-700' => $profile->is_active ?? true,
                            'bg-gray-100 text-gray-500' => !($profile->is_active ?? true),
                        ])>{{ ($profile->is_active ?? true) ? 'activo' : 'inactivo' }}</span>
                    </div>
                    <p class="mt-1 text-xs text-purple-600 dark:text-purple-400">{{ $profile->provider ?? '—' }} / {{ $profile->model ?? '—' }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- WhatsApp channels --}}
    @if($whatsappChannels->isNotEmpty())
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">WhatsApp</h2>
            <a href="{{ route('filament.admin.resources.nova-businesses.whatsapp', ['record' => $business->id]) }}"
               class="text-xs text-primary-600 hover:underline dark:text-primary-400">Gestionar →</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($whatsappChannels as $channel)
                <div class="rounded-xl border border-warning-200 bg-warning-50 p-4 dark:border-warning-800 dark:bg-warning-950/20">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $channel->name }}</span>
                        <span class="text-xs text-warning-600 dark:text-warning-400">{{ $channel->status }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">{{ $channel->phone_number ?? '—' }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Listing Config --}}
    @if($listingCategories->isNotEmpty())
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Listing Config</h2>
            <a href="{{ route('filament.admin.resources.nova-businesses.listing-config', ['record' => $business->id]) }}"
               class="text-xs text-primary-600 hover:underline dark:text-primary-400">Gestionar →</a>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($listingCategories as $cat)
                <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
                    <code class="text-xs font-semibold text-primary-600 dark:text-primary-400">{{ $cat->slug }}</code>
                    <p class="mt-0.5 truncate text-sm text-gray-700 dark:text-gray-300">{{ $cat->name ?? $cat->slug }}</p>
                    @if($cat->keywords)
                        <p class="mt-1 truncate text-xs text-gray-400">{{ implode(', ', is_array($cat->keywords) ? $cat->keywords : json_decode($cat->keywords, true) ?? []) }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Integraciones externas --}}
    @if($integrations->isNotEmpty())
    <div class="mt-6">
        <div class="mb-3 flex items-center justify-between">
            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Integraciones externas</h2>
        </div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach($integrations as $integration)
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex items-center justify-between gap-2">
                        <span class="font-semibold text-gray-900 dark:text-white">{{ $integration->name }}</span>
                        <span @class([
                            'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                            'bg-success-100 text-success-700' => $integration->is_active,
                            'bg-gray-100 text-gray-500' => !$integration->is_active,
                        ])>{{ $integration->is_active ? 'activa' : 'inactiva' }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">{{ $integration->type ?? $integration->provider ?? '—' }}</p>
                </div>
            @endforeach
        </div>
    </div>
    @endif
  {{-- Mermaid diagram --}}
    <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
        <div class="mb-3 flex items-center justify-between">
            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">Diagrama de recursos</p>
            <span class="text-xs text-gray-400">Negocio → Servicios · MCP · IA · WhatsApp</span>
        </div>
        <div class="overflow-x-auto">
            <pre class="mermaid text-xs">{{ $mermaid }}</pre>
        </div>
    </div>
    <script type="module">
        async function initMermaid() {
            const els = document.querySelectorAll('.mermaid:not([data-processed="true"])');
            if (!els.length) return;
            const { default: mermaid } = await import('https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.esm.min.mjs');
            const dark = document.documentElement.classList.contains('dark');
            mermaid.initialize({ startOnLoad: false, theme: dark ? 'dark' : 'default', securityLevel: 'loose', flowchart: { curve: 'basis' } });
            await mermaid.run({ querySelector: '.mermaid:not([data-processed="true"])' });
        }
        //initMermaid();
        document.addEventListener('livewire:navigated', initMermaid);
    </script>

</x-filament-panels::page>
