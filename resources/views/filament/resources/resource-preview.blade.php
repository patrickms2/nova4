<div class="space-y-4 p-4">
    <div class="grid grid-cols-2 gap-4 text-sm">
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">URI:</span>
            <code class="ml-2 rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $resource->uri }}</code>
        </div>
        @if($resource->uri_template)
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Template:</span>
            <code class="ml-2 rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">{{ $resource->uri_template }}</code>
        </div>
        @endif
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">MIME Type:</span>
            <span class="ml-2 rounded bg-blue-100 px-2 py-1 text-xs text-blue-800 dark:bg-blue-900 dark:text-blue-200">{{ $resource->mime_type }}</span>
        </div>
        <div>
            <span class="font-medium text-gray-500 dark:text-gray-400">Type:</span>
            <span class="ml-2">
                @if($resource->isDynamic())
                    <span class="rounded bg-purple-100 px-2 py-1 text-xs text-purple-800 dark:bg-purple-900 dark:text-purple-200">Dynamic</span>
                @else
                    <span class="rounded bg-green-100 px-2 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-200">Static</span>
                @endif
            </span>
        </div>
    </div>

    <div class="border-t pt-4 dark:border-gray-700">
        <h4 class="mb-2 font-medium text-gray-700 dark:text-gray-300">Content Preview</h4>
        @if($resource->content)
            <div class="max-h-96 overflow-auto rounded-lg bg-gray-50 p-4 dark:bg-gray-900">
                @if(str_contains($resource->mime_type, 'markdown'))
                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {!! \Illuminate\Support\Str::markdown($resource->content) !!}
                    </div>
                @elseif(str_contains($resource->mime_type, 'json'))
                    <pre class="text-xs"><code>{{ $resource->content }}</code></pre>
                @else
                    <pre class="whitespace-pre-wrap text-sm">{{ $resource->content }}</pre>
                @endif
            </div>
        @elseif($resource->handler_code)
            <div class="rounded-lg bg-gray-900 p-4">
                <pre class="text-xs text-gray-300"><code>{{ $resource->handler_code }}</code></pre>
            </div>
        @else
            <p class="text-sm italic text-gray-500">No content defined</p>
        @endif
    </div>

    @if($resource->annotations)
    <div class="border-t pt-4 dark:border-gray-700">
        <h4 class="mb-2 font-medium text-gray-700 dark:text-gray-300">Annotations</h4>
        <div class="flex flex-wrap gap-2">
            @foreach($resource->annotations as $key => $value)
                @if($value)
                <span class="rounded bg-gray-100 px-2 py-1 text-xs dark:bg-gray-800">
                    {{ $key }}: {{ is_bool($value) ? ($value ? 'true' : 'false') : $value }}
                </span>
                @endif
            @endforeach
        </div>
    </div>
    @endif
</div>
