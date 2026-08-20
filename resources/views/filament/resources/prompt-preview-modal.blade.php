<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Name:</span>
            <code class="ml-2 rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $prompt->name }}</code>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Server:</span>
            <span class="ml-2">{{ $prompt->server->name ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="border-t pt-4 dark:border-gray-700">
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $prompt->description }}</p>
    </div>

    @if(count($prompt->arguments ?? []) > 0)
    <div class="border-t pt-4 dark:border-gray-700">
        <h4 class="mb-2 font-medium text-gray-700 dark:text-gray-300">Arguments</h4>
        <div class="overflow-hidden rounded-lg border dark:border-gray-700">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Name</th>
                        <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Description</th>
                        <th class="px-4 py-2 text-center text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Required</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                    @foreach($prompt->arguments as $arg)
                    <tr>
                        <td class="whitespace-nowrap px-4 py-2">
                            <code class="text-xs">{{ $arg['name'] }}</code>
                        </td>
                        <td class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400">
                            {{ $arg['description'] ?? '-' }}
                        </td>
                        <td class="px-4 py-2 text-center">
                            @if($arg['required'] ?? false)
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-100 text-gray-400 dark:bg-gray-800">
                                    <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="border-t pt-4 dark:border-gray-700">
        <h4 class="mb-3 font-medium text-gray-700 dark:text-gray-300">Messages</h4>
        <div class="space-y-3">
            @forelse($prompt->messages ?? [] as $index => $message)
                <div class="rounded-lg border {{ ($message['role'] ?? 'user') === 'assistant' ? 'border-blue-200 bg-blue-50 dark:border-blue-800 dark:bg-blue-900/20' : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800' }} p-4">
                    <div class="mb-2 flex items-center gap-2">
                        <span class="rounded px-2 py-0.5 text-xs font-medium uppercase {{ ($message['role'] ?? 'user') === 'assistant' ? 'bg-blue-200 text-blue-800 dark:bg-blue-800 dark:text-blue-200' : 'bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200' }}">
                            {{ $message['role'] ?? 'user' }}
                        </span>
                        <span class="text-xs text-gray-400">Message {{ $index + 1 }}</span>
                    </div>
                    <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $message['content'] ?? '' }}</p>
                </div>
            @empty
                <p class="text-sm italic text-gray-500">No messages defined</p>
            @endforelse
        </div>
    </div>

    @if($prompt->metadata && count($prompt->metadata) > 0)
    <div class="border-t pt-4 dark:border-gray-700">
        <h4 class="mb-2 font-medium text-gray-700 dark:text-gray-300">Metadata</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($prompt->metadata as $key => $value)
                <span class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">
                    {{ $key }}: {{ $value }}
                </span>
            @endforeach
        </div>
    </div>
    @endif
</div>
