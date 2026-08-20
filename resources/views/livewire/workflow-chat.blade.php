<div>
<x-filament::section :compact="true">
        <div class="grid gap-4 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Bot</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="botId" wire:change="loadBot">
                        <option value="">Choose a Bot...</option>
                        @foreach(\Heiner\FilamentAgenticChatbot\Models\RagBot::class::pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Workflow</label>
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="workflowId" wire:change="loadWorkflow" :disabled="! $bot">
                        <option value="">Choose a Workflow...</option>
                        @foreach(\Heiner\FilamentAgenticChatbot\Models\AgentWorkflow::class::where('is_active', true)->pluck('name', 'id') as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                                       </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>


            <x-filament::button color="gray" icon="heroicon-m-trash" wire:click="clearChat" wire:loading.attr="disabled">
                Clear
            </x-filament::button>
        </div>
    </x-filament::section>
    <div class="bg-white rounded-lg shadow p-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-gray-900">
                    @if($workflow)
                        {{ $workflow->name }}
                    @else
                        Workflow Chat
                    @endif
                </h2>
                @if($bot)
                    <p class="text-sm text-gray-500">Bot: {{ $bot->name }}</p>
                @endif
            </div>
            <button wire:click="resetChat" class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded">
                Reset Chat
            </button>
        </div>

        <div class="mb-4">
            <div class="border rounded-lg bg-gray-50 h-96 overflow-y-auto p-4 space-y-4">
                @if(empty($messages))
                    <div class="text-center text-gray-400 py-8">
                        Start a conversation with this workflow
                    </div>
                @else
                    @foreach($messages as $msg)
                        <div class="{{ $msg['role'] === 'user' ? 'text-right' : 'text-left' }}">
                            <div class="inline-block max-w-[80%] px-4 py-2 rounded-lg
                                {{ $msg['role'] === 'user' ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-900' }}">
                                {{ $msg['content'] }}
                                @if(isset($msg['halted']) && $msg['halted'])
                                    <div class="text-xs mt-1 opacity-75">
                                        ⏸️ Halted: {{ $msg['halt_reason'] ?? 'Waiting for input' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        @if($error)
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                {{ $error }}
            </div>
        @endif

        <div class="flex gap-2">
            <input
                type="text"
                wire:model="message"
                wire:keydown.enter="send"
                placeholder="Type your message..."
                class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                {{ $isLoading ? 'disabled' : '' }}
            >
            <button
                wire:click="send"
                {{ $isLoading ? 'disabled' : '' }}
                class="px-6 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed"
            >
                {{ $isLoading ? 'Sending...' : 'Send' }}
            </button>
        </div>

        @if(!empty($executionSteps))
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-semibold text-sm text-gray-700 mb-2">Execution Details</h3>
                <pre class="text-xs text-gray-600 overflow-x-auto">{{ json_encode($executionSteps, JSON_PRETTY_PRINT) }}</pre>
            </div>
        @endif
    </div>
</div>
