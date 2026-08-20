@props([
    'title',
    'description' => null,
    'time' => null,
    'context' => null,
    'url' => null,
])

<article
    x-transition:enter="transition ease-out duration-500"
    x-transition:enter-start="opacity-0 translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    {{ $attributes->class('border-t border-neutral-800 py-4') }}
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            @if ($url)
                <a href="{{ $url }}" class="text-sm font-semibold text-white transition-colors hover:text-orange-400">
                    {{ $title }}
                </a>
            @else
                <p class="text-sm font-semibold text-white">{{ $title }}</p>
            @endif

            @if ($description)
                <p class="mt-2 text-xs leading-5 text-neutral-400">{{ $description }}</p>
            @endif

            @if ($context)
                <p class="mt-2 text-xs uppercase tracking-widest text-neutral-500">{{ $context }}</p>
            @endif
        </div>

        @if ($time)
            <time class="shrink-0 text-xs text-neutral-500">{{ $time }}</time>
        @endif
    </div>
</article>
