@php
    $imagePath = $get('image_path');
    $imageUrl = $get('image_url');
    $value = $imagePath ?? $imageUrl ?? null;
    $src = null;
    if ($value) {
        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            $src = $value;
        } else {
            $src = asset('storage/' . ltrim($value, '/'));
        }
    }
@endphp
@if($src)
    <div class="rounded-xl border border-white/10 bg-white/5 p-3">
        <div class="text-xs font-medium text-slate-400 mb-2">Previsualización</div>
        <img src="{{ $src }}" alt="Imagen sugerida" class="w-full h-48 object-cover rounded-lg shadow" loading="lazy">
        <div class="mt-2 text-[11px] text-slate-500 break-all">{{ $value }}</div>
    </div>
@else
    <div class="text-xs text-slate-400">Sin imagen disponible todavía.</div>
@endif
