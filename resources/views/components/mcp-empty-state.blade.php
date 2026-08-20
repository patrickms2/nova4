@props([
    'icon' => 'heroicon-o-document',
    'heading' => 'No items',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-8 text-center']) }}>
    <div class="mb-4 rounded-full bg-gray-100 p-3 dark:bg-gray-800">
        @svg($icon, 'h-8 w-8 text-gray-400 dark:text-gray-500')
    </div>
    <h3 class="text-base font-semibold text-gray-900 dark:text-white">{{ $heading }}</h3>
    @if($description)
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if(isset($actions))
        <div class="mt-4">
            {{ $actions }}
        </div>
    @endif
</div>
