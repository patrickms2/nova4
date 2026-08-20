<div class="space-y-6">
    {{-- Tool Selector --}}
    <x-filament::section :compact="true">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        wire:model.live="toolId"
                        wire:change="loadTool"
                    >
                        <option value="">Choose a tool...</option>
                        @php
                            $groupedTools = $tools->groupBy(fn($t) => $t->server->name);
                        @endphp
                        @foreach($groupedTools as $serverName => $serverTools)
                            <optgroup label="{{ $serverName }}">
                                @foreach($serverTools as $t)
                                    <option value="{{ $t->id }}">{{ $t->title }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
        </div>
    </x-filament::section>

    @if($tool)
        <div class="grid gap-6 lg:grid-cols-2">
            {{-- Tool Info & Input Form --}}
            <div class="space-y-6">
                {{-- Tool Details --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-primary-100 dark:bg-primary-900/30">
                                <x-heroicon-o-wrench-screwdriver class="h-5 w-5 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <span class="text-lg font-semibold">{{ $tool->title }}</span>
                                <code class="ml-2 text-sm text-gray-500 dark:text-gray-400">{{ $tool->name }}</code>
                            </div>
                        </div>
                    </x-slot>
                    <x-slot name="headerEnd">
                        <x-filament::badge color="gray" size="sm" icon="heroicon-m-server-stack">
                            {{ $tool->server->name }}
                        </x-filament::badge>
                    </x-slot>

                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $tool->description }}</p>

                    @if($tool->annotations)
                        <div class="mt-4 flex flex-wrap gap-2">
                            @if($tool->annotations['isReadOnly'] ?? false)
                                <x-filament::badge color="success" size="sm" icon="heroicon-m-eye">
                                    Read Only
                                </x-filament::badge>
                            @endif
                            @if($tool->annotations['isIdempotent'] ?? false)
                                <x-filament::badge color="info" size="sm" icon="heroicon-m-arrow-path">
                                    Idempotent
                                </x-filament::badge>
                            @endif
                            @if($tool->annotations['isDestructive'] ?? false)
                                <x-filament::badge color="danger" size="sm" icon="heroicon-m-exclamation-triangle">
                                    Destructive
                                </x-filament::badge>
                            @endif
                            @if($tool->annotations['isOpenWorld'] ?? false)
                                <x-filament::badge color="warning" size="sm" icon="heroicon-m-globe-alt">
                                    Open World
                                </x-filament::badge>
                            @endif
                        </div>
                    @endif
                </x-filament::section>

                {{-- Input Form --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon='heroicon-m-adjustments-horizontal' class="h-5 w-5 text-gray-400" />
                            <span>Input Parameters</span>
                        </div>
                    </x-slot>

                    @if(empty($inputSchema))
                        <div class="flex items-center gap-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800/50">
                            <x-filament::icon icon='heroicon-o-information-circle' class="h-5 w-5 text-gray-400" />
                            <p class="text-sm italic text-gray-500 dark:text-gray-400">This tool has no input parameters.</p>
                        </div>
                    @else
                        <form wire:submit="execute" class="space-y-4">
                            @foreach($inputSchema as $name => $config)
                                <div>
                                    <label for="input-{{ $name }}" class="mb-2 flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $name }}
                                        @if($config['required'] ?? false)
                                            <span class="text-red-500">*</span>
                                        @endif
                                        <x-filament::badge color="gray" size="sm">
                                            {{ $config['type'] ?? 'string' }}
                                        </x-filament::badge>
                                    </label>

                                    <x-filament::input.wrapper>
                                        @if(!empty($config['enum']))
                                            <x-filament::input.select
                                                id="input-{{ $name }}"
                                                wire:model="inputValues.{{ $name }}"
                                            >
                                                <option value="">Select...</option>
                                                @foreach($config['enum'] as $option)
                                                    <option value="{{ $option }}">{{ $option }}</option>
                                                @endforeach
                                            </x-filament::input.select>
                                        @elseif(($config['type'] ?? 'string') === 'boolean')
                                            <x-filament::input.select
                                                id="input-{{ $name }}"
                                                wire:model="inputValues.{{ $name }}"
                                            >
                                                <option value="">Select...</option>
                                                <option value="true">true</option>
                                                <option value="false">false</option>
                                            </x-filament::input.select>
                                        @elseif(in_array($config['type'] ?? 'string', ['object', 'array']))
                                            <textarea
                                                id="input-{{ $name }}"
                                                wire:model="inputValues.{{ $name }}"
                                                rows="4"
                                                placeholder='{{ $config["type"] === "array" ? "[\n  \"item1\",\n  \"item2\"\n]" : "{\n  \"key\": \"value\"\n}" }}'
                                                class="fi-input block w-full rounded-lg border-none bg-transparent py-1.5 font-mono text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500"
                                            ></textarea>
                                        @else
                                            <x-filament::input
                                                type="{{ in_array($config['type'] ?? 'string', ['integer', 'number']) ? 'number' : 'text' }}"
                                                id="input-{{ $name }}"
                                                wire:model="inputValues.{{ $name }}"
                                                :placeholder="$config['default'] ?? ''"
                                                :step="($config['type'] ?? 'string') === 'number' ? 'any' : '1'"
                                            />
                                        @endif
                                    </x-filament::input.wrapper>

                                    @if(!empty($config['description']))
                                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $config['description'] }}</p>
                                    @endif
                                </div>
                            @endforeach

                            <x-filament::button
                                type="submit"
                                wire:loading.attr="disabled"
                                wire:target="execute"
                                icon="heroicon-m-play"
                                class="w-full"
                                size="lg"
                            >
                                <span wire:loading.remove wire:target="execute">Execute Tool</span>
                                <span wire:loading wire:target="execute">Executing...</span>
                            </x-filament::button>
                        </form>
                    @endif
                </x-filament::section>
            </div>

            {{-- Results Panel --}}
            <div class="space-y-6">
                {{-- Result --}}
                <x-filament::section>
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon='heroicon-m-clipboard-document-list' class="h-5 w-5 text-gray-400" />
                            <span>Result</span>
                        </div>
                    </x-slot>
                    <x-slot name="headerEnd">
                        @if($executionTime !== null)
                            <x-filament::badge
                                :color="$executionTime > 1000 ? 'warning' : 'success'"
                                size="sm"
                                icon="heroicon-m-clock"
                            >
                                {{ number_format($executionTime, 2) }}ms
                            </x-filament::badge>
                        @endif
                    </x-slot>

                    {{-- Loading State --}}
                    <div wire:loading wire:target="execute" class="flex items-center justify-center py-12">
                        <div class="text-center">
                            <x-filament::loading-indicator class="mx-auto h-8 w-8 text-primary-500" />
                            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Executing tool...</p>
                        </div>
                    </div>

                    <div wire:loading.remove wire:target="execute">
                        @if($error)
                            <div class="flex items-start gap-3 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                                <x-filament::icon icon='heroicon-m-x-circle' class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-500" />
                                <div>
                                    <h5 class="font-medium text-red-800 dark:text-red-300">Execution Error</h5>
                                    <p class="mt-1 text-sm text-red-700 dark:text-red-400">{{ $error }}</p>
                                </div>
                            </div>
                        @elseif($result !== null)
                            @if(is_array($result))
                                <x-mcp-code-block :code="$result" language="json" max-height="400px" />
                            @else
                                <div class="prose prose-sm max-w-none whitespace-pre-wrap rounded-lg bg-gray-50 p-4 dark:prose-invert dark:bg-gray-800/50">{{ $result }}</div>
                            @endif
                        @else
                            <x-mcp-empty-state
                                icon="heroicon-o-play-circle"
                                heading="No results yet"
                                description="Execute the tool to see the output here."
                            />
                        @endif
                    </div>
                </x-filament::section>

                {{-- Request Preview --}}
                <x-filament::section :collapsible="true" :collapsed="true">
                    <x-slot name="heading">
                        <div class="flex items-center gap-2">
                            <x-filament::icon icon='heroicon-m-code-bracket' class="h-5 w-5 text-gray-400" />
                            <span>Request Preview</span>
                        </div>
                    </x-slot>

                    <x-mcp-code-block
                        :code="[
                            'jsonrpc' => '2.0',
                            'method' => 'tools/call',
                            'params' => [
                                'name' => $tool->name,
                                'arguments' => (object)$inputValues
                            ],
                            'id' => 1
                        ]"
                        language="json"
                        max-height="300px"
                    />
                </x-filament::section>
            </div>
        </div>
    @else
        {{-- Empty State --}}
        <x-filament::section>
            <x-mcp-empty-state
                icon="heroicon-o-wrench-screwdriver"
                heading="Select a Tool"
                description="Choose a tool from the dropdown above to test it. Tools are grouped by their server."
            />
        </x-filament::section>
    @endif
</div>
