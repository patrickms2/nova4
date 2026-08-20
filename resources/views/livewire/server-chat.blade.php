<div class="space-y-6">
    <x-filament::section :compact="true">
        <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Server</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="serverId" wire:change="loadServer">
                        <option value="">Choose a server...</option>
                        @foreach($servers as $availableServer)
                            <option value="{{ $availableServer->id }}">{{ $availableServer->name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Prompt</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="promptId" :disabled="! $server">
                        <option value="">Server instructions only</option>
                        @foreach($prompts as $prompt)
                            <option value="{{ $prompt->id }}">{{ $prompt->title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tool mode</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="toolId" :disabled="! $server">
                        <option value="">Auto: deduce from prompt + order</option>
                        @foreach($tools as $tool)
                            <option value="{{ $tool->id }}">Force: {{ $tool->title }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <x-filament::button color="gray" icon="heroicon-m-trash" wire:click="clearChat" wire:loading.attr="disabled">
                Clear
            </x-filament::button>
        </div>
    </x-filament::section>

    @if($server)
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <x-filament::section>
                <x-slot name="heading">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-m-chat-bubble-left-right" class="h-5 w-5 text-gray-400" />
                        <span>{{ $server->name }}</span>
                    </div>
                </x-slot>

                <x-slot name="headerEnd">
                    <x-filament::badge color="info" size="sm" icon="heroicon-m-sparkles">
                        {{ $activePrompt?->title ?? 'Server instructions' }}
                    </x-filament::badge>
                </x-slot>

                <div class="mb-4 rounded-xl border border-gray-200 bg-gray-50 p-3 text-sm text-gray-600 dark:border-white/10 dark:bg-gray-900/50 dark:text-gray-300">
                    <div class="font-medium text-gray-950 dark:text-white">Agent context</div>
                    <div class="mt-1">
                        The chat reads the selected MCP prompt and the server instructions, then selects the best active tool for each order.
                    </div>
                    @if($activePrompt)
                        <div class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Prompt #{{ $activePrompt->id }}: {{ $activePrompt->description }}
                        </div>
                    @endif
                </div>

                <div class="flex h-[32rem] flex-col gap-4">
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto rounded-xl bg-gray-50 p-4 dark:bg-gray-900/50">
                        @forelse($messages as $index => $chatMessage)
                            <div wire:key="server-chat-message-{{ $index }}" class="flex {{ $chatMessage['role'] === 'user' ? 'justify-end' : 'justify-start' }}">
                                <div class="max-w-3xl rounded-2xl px-4 py-3 {{ $chatMessage['role'] === 'user' ? 'bg-primary-600 text-white' : 'bg-white text-gray-950 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:text-white dark:ring-white/10' }}">
                                    <div class="mb-1 text-xs opacity-70">{{ $chatMessage['role'] === 'user' ? 'You' : $chatMessage['tool'] }}</div>
                                    <pre class="whitespace-pre-wrap break-words font-sans text-sm leading-6">{{ $chatMessage['content'] }}</pre>
                                </div>
                            </div>
                        @empty
                            <div class="flex h-full items-center justify-center">
                                <x-mcp-empty-state
                                    icon="heroicon-o-chat-bubble-left-right"
                                    heading="No messages yet"
                                    description="Choose a tool and send a message to test this server. The message is mapped to common parameters like message, query, prompt, or search."
                                />
                            </div>
                        @endforelse

                        <div wire:loading wire:target="send" class="flex justify-start">
                            <div class="rounded-2xl bg-white px-4 py-3 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-800 dark:ring-white/10">
                                <div class="flex items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                    <x-filament::loading-indicator class="h-5 w-5" />
                                    Running tool...
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($error)
                        <div class="rounded-xl bg-danger-50 p-3 text-sm text-danger-700 dark:bg-danger-900/20 dark:text-danger-300">
                            {{ $error }}
                        </div>
                    @endif

                    <form wire:submit="send" class="flex gap-3">
                        <x-filament::input.wrapper class="flex-1 relative">
                            <x-filament::input
                                id="serverChatInput"
                                type="voice"
                                wire:model="message"
                                placeholder="Write an order; the agent will choose the tool..."
                                :disabled="! $server"
                            />
                            <button
                                type="button"
                                id="voiceButton"
                                class="absolute right-2 top-1/2 -translate-y-1/2 p-2 rounded-full text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                :disabled="! $server"
                                title="Dictar mensaje"
                            >
                                <svg id="micIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11a7 7 0 01-7 7m0 0a7 7 0 01-7-7m7 7v4m0 0H8m4 0h4m-4-8a3 3 0 01-3-3V5a3 3 0 116 0v6a3 3 0 01-3 3z" />
                                </svg>
                                <svg id="micOffIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2" />
                                </svg>
                            </button>
                        </x-filament::input.wrapper>

                        <x-filament::button type="submit" icon="heroicon-m-paper-airplane" wire:loading.attr="disabled" wire:target="send" :disabled="! $server">
                            Send
                        </x-filament::button>
                    </form>

                    <script>
                        @if($server)
                        document.addEventListener('DOMContentLoaded', function() {
                            const voiceButton = document.getElementById('voiceButton');
                            const chatInput = document.getElementById('serverChatInput');
                            const micIcon = document.getElementById('micIcon');
                            const micOffIcon = document.getElementById('micOffIcon');
                            
                            let recognition = null;
                            let isListening = false;

                            // Check for browser support
                            const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

                            if (SpeechRecognition) {
                                recognition = new SpeechRecognition();
                                recognition.continuous = false;
                                recognition.interimResults = false;
                                recognition.lang = 'es-ES';

                                recognition.onstart = function() {
                                    isListening = true;
                                    micIcon.classList.add('hidden');
                                    micOffIcon.classList.remove('hidden');
                                    voiceButton.classList.add('text-red-500', 'bg-red-50');
                                    voiceButton.classList.remove('text-gray-400');
                                };

                                recognition.onend = function() {
                                    isListening = false;
                                    micIcon.classList.remove('hidden');
                                    micOffIcon.classList.add('hidden');
                                    voiceButton.classList.remove('text-red-500', 'bg-red-50');
                                    voiceButton.classList.add('text-gray-400');
                                };

                                recognition.onresult = function(event) {
                                    const transcript = event.results[0][0].transcript;
                                    chatInput.value = transcript;
                                    chatInput.dispatchEvent(new Event('input'));
                                };

                                recognition.onerror = function(event) {
                                    console.error('Speech recognition error:', event.error);
                                    isListening = false;
                                    micIcon.classList.remove('hidden');
                                    micOffIcon.classList.add('hidden');
                                    voiceButton.classList.remove('text-red-500', 'bg-red-50');
                                    voiceButton.classList.add('text-gray-400');
                                };

                                voiceButton.addEventListener('click', function() {
                                    if (isListening) {
                                        recognition.stop();
                                    } else {
                                        recognition.start();
                                    }
                                });
                            } else {
                                voiceButton.style.display = 'none';
                                console.log('Speech recognition not supported in this browser');
                            }
                        });
                        @endif
                    </script>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Agent tools</x-slot>

                <div class="space-y-6">
                    @if(! empty($workflowPlan))
                        <livewire:workflow-plan-display :workflow-plan="$workflowPlan" :key="'server-chat-plan-'.md5(json_encode($workflowPlan))" />
                    @endif

                    @if(count($executionSteps) > 0)
                        <div class="space-y-3">
                            <div class="text-sm font-medium text-gray-950 dark:text-white">Last decision</div>
                            @foreach($executionSteps as $index => $step)
                                <div wire:key="server-chat-step-{{ $index }}" class="rounded-xl border border-gray-200 p-3 dark:border-white/10">
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $step['description'] }}</div>
                                        <x-filament::badge color="gray" size="sm">{{ $step['time'] }}</x-filament::badge>
                                    </div>

                                    @if(! empty($step['data']))
                                        <pre class="mt-2 max-h-32 overflow-auto whitespace-pre-wrap rounded-lg bg-gray-50 p-2 text-xs text-gray-600 dark:bg-gray-900 dark:text-gray-300">{{ json_encode($step['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="space-y-3">
                        <div class="text-sm font-medium text-gray-950 dark:text-white">Available tools</div>
                    @forelse($tools as $tool)
                        <button
                            type="button"
                            wire:key="server-chat-tool-{{ $tool->id }}"
                            wire:click="$set('toolId', {{ $tool->id }})"
                            class="w-full rounded-xl border p-3 text-left transition hover:bg-gray-50 dark:hover:bg-white/5 {{ (int) $toolId === $tool->id ? 'border-primary-500 bg-primary-50 dark:bg-primary-500/10' : 'border-gray-200 dark:border-white/10' }}"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="font-medium text-gray-950 dark:text-white">{{ $tool->title }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $tool->name }}</div>
                                </div>

                                @if($tool->annotations['isReadOnly'] ?? false)
                                    <x-filament::badge color="success" size="sm">Read</x-filament::badge>
                                @endif
                            </div>

                            <p class="mt-2 line-clamp-3 text-sm text-gray-600 dark:text-gray-400">{{ $tool->description }}</p>
                        </button>
                    @empty
                        <x-mcp-empty-state
                            icon="heroicon-o-wrench-screwdriver"
                            heading="No active tools"
                            description="Add or install tools for this server before using chat test mode."
                        />
                    @endforelse
                    </div>
                </div>
            </x-filament::section>
        </div>
    @else
        <x-filament::section>
            <x-mcp-empty-state
                icon="heroicon-o-server-stack"
                heading="Select a server"
                description="Choose a server to open its chat testing view."
            />
        </x-filament::section>
    @endif
</div>
