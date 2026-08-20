<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nova Community</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite('resources/css/app.css')
    @stack('styles')
    <style>
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom); }
                .bg-orange-600 {
            background-color: #c82323 !important;
        }
        .text-[#E60000] {
            color: #e9e9e9 !important;
        }
            .grid-cols-5 {
           grid-template-columns: repeat(5,minmax(0,1fr));
    }
    .grid-cols-7 {
           grid-template-columns: repeat(7,minmax(0,1fr));
    }
    .text-red-500 {
            color: #ff6467 !important;
        }
    
    </style>
    @livewireStyles
</head>
<body class="font-['Montserrat'] bg-[#111111] text-white antialiased min-h-screen">

    <main class="pb-20" style=" max-width: 680px; margin: auto;">
        {{ $slot }}
    </main>

    @php
        $portalType = \App\Support\CommunityPortalContext::portalType();
        $activeItem = request()->query('section', request()->routeIs('comunigest.inicio') ? 'home' : (request()->routeIs('comunigest.work-order') ? 'work' : 'incidents'));
        $navigation = $portalType === 'owner'
            ? [['home', 'Inicio', '⌂'], ['properties', 'Propiedades', '▱'], ['documents', 'Documentos', '▤'], ['fees', 'Cuotas', '€'], ['tickets', 'Tickets', '!']]
            : [['home', 'Inicio', '⌂'],  ['communities', 'Comunidades', '≡'],['plans', 'Planes', '≡'], ['work', 'Órdenes', '▤'], ['shifts', 'Turnos', '□'], ['attendance', 'Asistencia', '◷']];
    @endphp

    <nav class="fixed bottom-0 left-0 right-0 z-50 safe-bottom bg-[#111111] border-t border-[#2A2A2A]">
        <div class="flex h-16 items-center justify-around max-w-md mx-auto">
            @foreach ($navigation as [$key, $label, $icon])
                <a wire:key="mobile-nav-{{ $key }}" href="{{ route('comunigest.inicio', ['section' => $key]) }}" wire:navigate class="flex min-w-0 flex-1 flex-col items-center gap-1 text-[10px] font-medium {{ $activeItem === $key ? 'text-red-500' : 'text-[#666666] hover:text-white' }}">
                    <span class="text-lg leading-none">{{ $icon }}</span><span class="max-w-full truncate">{{ $label }}</span>
                </a>
            @endforeach
        </div>
    </nav>

    @livewireScripts
</body>
</html>
