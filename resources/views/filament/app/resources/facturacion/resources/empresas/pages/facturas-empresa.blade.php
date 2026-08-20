@php
    use Filament\Support\Enums\Width;
    
    $maxContentWidth ??= (filament()->getMaxContentWidth() ?? Width::SevenExtraLarge);

    if (is_string($maxContentWidth)) {
        $maxContentWidth = Width::tryFrom($maxContentWidth) ?? $maxContentWidth;
    }
@endphp
<x-filament-panels::page>
    {{ $this->table }}
</x-filament-panels::page>
