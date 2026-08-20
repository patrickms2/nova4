@props([
    'lines' => 3,
])

<div {{ $attributes->class('grid gap-4') }} aria-hidden="true">
    @for ($line = 0; $line < (int) $lines; $line++)
        <div
            @class([
                'h-3 animate-pulse rounded-2xl bg-neutral-800',
                'w-full' => $line % 3 !== 2,
                'w-2/3' => $line % 3 === 2,
            ])
        ></div>
    @endfor
</div>
