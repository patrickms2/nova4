@php
    $brandName = filament()->getBrandName();
    $panelUrlName = filament()->getProfileUrl();
    $brandLogo = filament()->getBrandLogo();
    $brandLogoHeight = filament()->getBrandLogoHeight() ?? '1.5rem';
    $darkModeBrandLogo = filament()->getDarkModeBrandLogo();
    $hasDarkModeBrandLogo = filled($darkModeBrandLogo);

    $getLogoClasses = fn (bool $isDarkMode): string => \Illuminate\Support\Arr::toCssClasses([
        'fi-logo',
        'fi-logo-light' => $hasDarkModeBrandLogo && (! $isDarkMode),
        'fi-logo-dark' => $isDarkMode,
    ]);
    $logoIconOnly = false;
    $logoStyles = "height: {$brandLogoHeight}";
    $attributes = $attributes ?? new \Illuminate\View\ComponentAttributeBag();
@endphp
<style>
@font-face {
  font-family: "Astra";
  src: url("/fonts/filament/filament/astra/Astra.woff2") format("woff2"),
       url("/fonts/filament/filament/astra/Astra.woff") format("woff"),
       url("/fonts/filament/filament/astra/Astra.otf") format("opentype"); 
  font-weight: 400;
  font-style: normal;
font-display: block;
}
@font-face {
  font-family: "Lazytype";
  src: url("/fonts/filament/filament/lazy/Lazytype.woff2") format("woff2"),
       url("/fonts/filament/filament/lazy/Lazytype.woff") format("woff"),
       url("/fonts/filament/filament/lazy/Lazytype.otf") format("opentype"); 
  font-weight: 400;
  font-style: normal;
  font-display: block;
}

/* Subtle breathing glow + float animation */
@keyframes brandPulse {
	0% { transform: translateY(0) scale(1); filter: drop-shadow(0 0 0 rgba(255, 85, 0, 0)); }
	50% { transform: translateY(-1px) scale(1.02); filter: drop-shadow(0 0 6px rgba(255, 85, 0, 0.35)); }
	100% { transform: translateY(0) scale(1); filter: drop-shadow(0 0 0 rgba(255, 85, 0, 0)); }
}

.brand-logo {
	animation: brandPulse 2.8s ease-in-out infinite;
	will-change: transform, filter;
}

@media (prefers-reduced-motion: reduce) {
	.brand-logo { animation: none; }
}
</style>
@capture($content, $logo, $isDarkMode = false)
    @if ($logo instanceof \Illuminate\Contracts\Support\Htmlable)
        <div
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
        >
            {{ $logo }}
        </div>
    @elseif (filled($logo) && $logoIconOnly)
        <img
            alt="{{ __('filament-panels::layout.logo.alt', ['name' => $brandName]) }}"
            src="{{ $logo }}"
            {{
                $attributes
                    ->class([$getLogoClasses($isDarkMode)])
                    ->style([$logoStyles])
            }}
        />
    @else
        <div class="logo-text-after"
            {{
                $attributes->class([
                    $getLogoClasses($isDarkMode),

                ])
            }}
        >
        <a class="currentColor transition duration-[1000ms] ease-in" style="padding-left: 1rem; font-family: Lazytype, sans-serif;font-size: 1.5rem; font-weight: 500;" href="{{ $panelUrlName }}">
            {{ $brandName }}
        </a>
        </div>
    @endif
@endcapture

{{ $content($brandLogo) }}

@if ($hasDarkModeBrandLogo)
    {{ $content($darkModeBrandLogo, isDarkMode: true) }}
@endif

