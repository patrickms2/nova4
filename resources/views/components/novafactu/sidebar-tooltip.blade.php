@props(['key' => null, 'label' => ''])

<div
    x-show="hover === '{{ $key }}'"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 translate-x-2"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-2"
    {{ $attributes->merge(['class' => 'absolute left-full top-1/2 z-50 ml-3 -translate-y-1/2 whitespace-nowrap rounded-lg bg-neutral-900 px-3 py-1.5 text-xs font-medium text-neutral-50 shadow-xl ring-1 ring-neutral-800']) }}
>
    @if ($slot->isEmpty())
        {{ $label }}
    @else
        {{ $slot }}
    @endif
    <div class="absolute -left-1 top-1/2 h-2 w-2 -translate-y-1/2 rotate-45 bg-neutral-900 ring-1 ring-neutral-800"></div>
</div>
