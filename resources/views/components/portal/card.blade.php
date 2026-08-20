@props([
    'padding' => 'p-4',
])

<div {{ $attributes->merge(['class' => "tl-s1 tl-interactive {$padding}"]) }}>
    {{ $slot }}
</div>
