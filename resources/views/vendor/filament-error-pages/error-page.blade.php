<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Error' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        gray: {
                            600: '#4b5563',
                            700: '#374151',
                            900: '#111827',
                        },
                    },
                },
            },
        }
    </script>
</head>
<body class="bg-black text-white flex items-center justify-center min-h-screen font-sans">
    <div class="text-center max-w-md mx-auto px-4">
        <div class="text-9xl font-black mb-4 relative">
            <span class="absolute text-gray-600 transform -rotate-12 -top-4 -left-4 opacity-50">
                {{ $code ?? 500 }}
            </span>
            <span class="relative">{{ $code ?? 500 }}</span>
        </div>

        <h1 class="text-xl font-semibold mb-2">
            {{ $title ?? 'Access Denied' }}
        </h1>

        <p class="text-sm text-gray-400 mb-6">
            {{ $description ?? 'UNAUTHORIZED - ACCESS DENIED!' }}
        </p>

        @if(url()->previous() != url()->current())
            <script>
                setTimeout(() => {
                    window.location.href = '{{ url()->previous() }}';
                }, 2000);
            </script>
        @endif
        @if(url()->previous() === url()->current())
            <script>
                setTimeout(() => {
                    window.location.href = 'https://ndsth.com';
                }, 1000);
            </script>
        @endif
</body>
</html>
