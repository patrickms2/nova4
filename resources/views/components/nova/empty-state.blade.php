@props([
    'title',
    'description',
])

<div {{ $attributes->class('border-t border-neutral-800 py-8 text-center md:col-span-full') }}>
    <h3 class="font-semibold text-white">{{ $title }}</h3>
    <p class="mt-2 text-sm text-neutral-400">{{ $description }}</p>
</div>
