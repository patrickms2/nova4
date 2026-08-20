<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<script>
    if (window.innerWidth >= 1024) {
        let lastX = 0;
        let lastY = 0;
        let lastTime = Date.now();

        document.addEventListener("mousemove", (e) => {
            const now = Date.now();
            const deltaTime = now - lastTime;

            const deltaX = e.clientX - lastX;
            const deltaY = e.clientY - lastY;

            const velocity = Math.min(Math.sqrt(deltaX * deltaX + deltaY * deltaY) / deltaTime, 1.5);

            document.querySelectorAll(".tl-cursor-react").forEach(el => {
                el.style.transform = `translateY(${velocity * -4}px)`;
            });

            lastX = e.clientX;
            lastY = e.clientY;
            lastTime = now;
        });
    }
</script>
{{ $slot }}
</body>
</html>
