@props([])

<div data-slot="callout-heading" {{ $attributes->twMerge('font-medium') }}>
    {{ $slot }}
</div>
