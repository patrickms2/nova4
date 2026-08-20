<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" x-data x-bind:class="{ dark: $store.theme?.isDark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NovaFact | {{ config('app.name', 'NovaFact') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/front.js'])
    @stack('styles')
</head>
<body class="min-h-screen bg-background">
    @livewireScripts

    @livewire('double-sidebar')

        @yield('content')

</body>
</html>
