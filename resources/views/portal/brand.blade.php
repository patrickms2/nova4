@php
    $fallbackSrc = asset('logo-dark.png');

    try {
        $manifestPath = public_path('build/manifest.json');

        if (is_file($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $logoFile = data_get($manifest, 'resources/img/logo-dark.png.file');

            if (is_string($logoFile) && $logoFile !== '') {
                $fallbackSrc = asset('build/' . ltrim($logoFile, '/'));
            }
        }
    } catch (Throwable) {
    }
@endphp

<a
    href="{{ \App\Filament\Portal\Pages\Dashboard::getUrl(panel: 'portal') }}"
    class="inline-flex items-center"
    aria-label="Ir al inicio del portal"
>
<div class="text-center">

            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#E60000]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                        <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">Nova Community</span>
            </div>

    </div>

</a>
