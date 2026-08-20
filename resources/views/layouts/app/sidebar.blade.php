<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" >
<head>
    @include('partials.head')
</head>
<body class="relative">
{{-- Sidebar integrado en header --}}

<div class="min-h-screen flex ">

    <!-- Mobile User Menu -->
    {{-- Content --}}
    <main class="flex-1 p-10">
        <div class="max-w-7xl mx-auto">
            {{ $slot }}

            @fluxScripts
        </div>
    </main>

</div>
</body>
</html>
