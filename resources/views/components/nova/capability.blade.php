@props([
    'section',
    'enabledSections' => [],
])

@if(in_array($section, $enabledSections, true))
    {{ $slot }}
@endif
