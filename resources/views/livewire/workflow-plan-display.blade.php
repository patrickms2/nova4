<div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
    <div class="flex items-start justify-between gap-4">
        <div class="flex min-w-0 flex-1 items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                <x-filament::icon icon="heroicon-m-document-text" class="h-5 w-5" />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <div class="font-semibold text-gray-950 dark:text-white">Workflow Plan Generated</div>
                    <x-filament::badge color="purple" size="sm">{{ ucfirst(str_replace('_', ' ', $this->planType)) }}</x-filament::badge>
                </div>

                <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Strategy</div>
                        <div class="font-medium text-gray-950 dark:text-white">{{ ucfirst(str_replace('_', ' ', $this->strategyType)) }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Stages</div>
                        <div class="font-medium text-gray-950 dark:text-white">{{ $this->totalStages }}</div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Est. Duration</div>
                        <div class="font-medium text-gray-950 dark:text-white">{{ $this->estimatedDuration }}s</div>
                    </div>

                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Generated</div>
                        <div class="font-medium text-gray-950 dark:text-white">{{ $workflowPlan['generated_at'] ?? 'now' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <x-filament::button color="gray" size="sm" wire:click="toggleExpanded">
            {{ $expanded ? 'Hide' : 'Show' }} Details
        </x-filament::button>
    </div>

    @if($expanded)
        <div class="mt-4 space-y-3 border-t border-gray-200 pt-4 dark:border-white/10">
            @if(isset($workflowPlan['original_query']))
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-gray-950">
                    <div class="text-xs text-gray-500 dark:text-gray-400">Original order</div>
                    <div class="mt-1 text-sm font-medium text-gray-950 dark:text-white">{{ $workflowPlan['original_query'] }}</div>
                </div>
            @endif

            @foreach($workflowPlan['stages'] ?? [] as $stageIndex => $stage)
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-gray-950">
                    <div class="mb-3 flex flex-wrap items-center gap-2">
                        <x-filament::badge color="purple" size="sm">Stage {{ $stageIndex + 1 }}</x-filament::badge>
                        <x-filament::badge color="{{ ($stage['type'] ?? 'single') === 'parallel' ? 'info' : 'success' }}" size="sm">
                            {{ ucfirst($stage['type'] ?? 'single') }} Execution
                        </x-filament::badge>
                    </div>

                    <div class="space-y-2">
                        @foreach($stage['nodes'] ?? [] as $nodeIndex => $node)
                            <div class="rounded-lg border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-gray-900">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $node['tool_title'] ?? $node['agent_name'] ?? 'Tool' }}</div>
                                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $node['input'] ?? '' }}</div>
                                    </div>

                                    @if(isset($node['tool_name']))
                                        <x-filament::badge color="gray" size="sm">{{ $node['tool_name'] }}</x-filament::badge>
                                    @endif
                                </div>

                                @if(isset($node['rationale']) && $node['rationale'])
                                    <div class="mt-2 border-t border-gray-200 pt-2 text-xs text-gray-600 dark:border-white/10 dark:text-gray-300">
                                        <span class="font-medium">Rationale:</span> {{ $node['rationale'] }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
