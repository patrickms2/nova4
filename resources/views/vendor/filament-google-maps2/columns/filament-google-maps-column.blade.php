<div {{ $attributes->merge($getExtraAttributes())->class([
    'filament-google-maps-column',
    'px-4 py-3' => ! $isInline(),
]) }}>
    @php
        $height = $getHeight();
        $width = $getWidth();
    @endphp

    <div
        style="
            {!! $height !== null ? "height: {$height}px;" : null !!}
            {!! $width !== null ? "width: {$width}px;" : null !!}
        "
    >

    </div>
</div>
