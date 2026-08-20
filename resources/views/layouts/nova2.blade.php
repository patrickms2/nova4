<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('nova.name', 'Nova') }}</title>
    @vite('resources/css/nova.css')
    @livewireStyles
</head>
<body>
    <main class="nova-shell">
        {{ $slot }}
    </main>

    @livewireScripts
</body>
</html>
