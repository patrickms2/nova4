@php
    $prompt = $getRecord();
@endphp

@if($prompt)
<div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
    <h4 class="mb-3 font-medium text-gray-700 dark:text-gray-300">Prompt Preview</h4>

    @if(count($prompt->arguments ?? []) > 0)
    <div class="mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Arguments:</span>
        <div class="mt-1 flex flex-wrap gap-2">
            @foreach($prompt->arguments as $arg)
                <span class="rounded-full px-3 py-1 text-xs {{ $arg['required'] ?? false ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' }}">
                    {{ $arg['name'] }}{{ ($arg['required'] ?? false) ? '*' : '' }}
                </span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="space-y-3">
        @foreach($prompt->messages ?? [] as $message)
            <div class="rounded-lg p-3 {{ ($message['role'] ?? 'user') === 'assistant' ? 'bg-blue-50 dark:bg-blue-900/20' : 'bg-gray-50 dark:bg-gray-800' }}">
                <span class="mb-1 block text-xs font-medium uppercase {{ ($message['role'] ?? 'user') === 'assistant' ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-gray-400' }}">
                    {{ $message['role'] ?? 'user' }}
                </span>
                <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $message['content'] ?? '' }}</p>
            </div>
        @endforeach
    </div>
</div>
@endif
