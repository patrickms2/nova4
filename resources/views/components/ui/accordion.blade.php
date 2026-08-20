@props(['visible' => false])

@if ($visible)
    <div {{ $attributes->class(['accordion-copy']) }}>
        {{ $slot }}
    </div>
@endif
