<!DOCTYPE html>
<html lang="es" class="dark">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>NOVA Studio</title>

        @vite(['resources/css/app.css', 'resources/css/nova.css', 'resources/js/app.js'])

        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body class="min-h-screen bg-black text-white antialiased">
        {{ $slot }}

        @livewireScripts
    </body>
</html>
