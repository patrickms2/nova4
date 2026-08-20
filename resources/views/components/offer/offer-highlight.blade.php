@props([
    'label',
    'value',
])

<div class="metric-chip">
    <span class="text-sm text-slate-500">{{ $label }}</span>
    <strong class="text-slate-950">{{ $value }}</strong>
</div>
