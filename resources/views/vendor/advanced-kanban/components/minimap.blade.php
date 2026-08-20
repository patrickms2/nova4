@props([ "columns" => 0 ])
<template x-if="minimapMaxScroll">
    <div class="minimap-wrapper" x-init="calculateMinimapThumbWidth" x-ref="minimapRef" :style="{'--minimap-thumb-width': minimapThumbWidth}">
        <label class="minimap-slider">
            <div class="minimap-columns" :style="{'--columns': '{{ $columns }}'}">
                @foreach (range(0, $columns - 1) as $i)
                    <i class="minimap-column"></i>
                @endforeach
            </div>
            <input class="minimap-input minimap-grid-bg" @input="onMinimapSliderInput" type="range" id="volume" name="volume" :min="0" x-model="minimapScrollLeft" :max="minimapMaxScroll" />
        </label>                
    </div>
</template>