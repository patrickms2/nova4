<!DOCTYPE html>
<html lang="es" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>NOVA Community — Acceso</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/comunigest-login.js'])
</head>
<body class="min-h-screen bg-[#07090d] font-['Montserrat'] text-white antialiased">
    <main class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10 pb-[max(2.5rem,env(safe-area-inset-bottom))]">
        <video autoplay loop muted playsinline preload="metadata" class="absolute inset-0 h-full w-full object-cover opacity-55">
            <source src="{{ asset('video/login.webm') }}" type="video/webm">
            <source src="{{ asset('video/login.mp4') }}" type="video/mp4">
        </video>
        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(9,12,18,.28),rgba(4,6,9,.82))]"></div>

        <section class="relative z-10 w-full max-w-sm overflow-hidden rounded-[1.75rem] border border-white/15 bg-[#11151c]/75 p-6 shadow-[0_28px_80px_rgba(0,0,0,.55),inset_0_1px_0_rgba(255,255,255,.08)] backdrop-blur-2xl sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-600 shadow-lg shadow-red-950/30">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/></svg>
                    </div>
                    <div><p class="text-[10px] font-bold uppercase tracking-[.22em] text-red-400">NOVA</p><p class="font-bold">Community</p></div>
                </div>
                <button type="button" data-theme-toggle class="flex h-10 w-10 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-white/60 transition duration-200 hover:-translate-y-0.5 hover:border-white/25 hover:bg-white/10 hover:text-white" aria-label="Cambiar tema">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z"/></svg>
                </button>
            </div>

            <header class="pb-6 pt-7 text-center">
                <h1 class="text-3xl font-extrabold tracking-tight">Portal Community</h1>
                <p class="mt-2 text-sm text-white/45">Accede con tu correo electrónico</p>
            </header>

            <form method="POST" action="{{ route('comunigest.login') }}" data-community-login class="grid gap-4">
                @csrf
                @if ($errors->any())
                    <div class="rounded-xl border border-rose-500/40 bg-rose-500/15 p-3 text-xs text-rose-100">{{ $errors->first() }}</div>
                @endif

                <label class="grid gap-2 text-sm font-medium" for="email">Email <span class="text-red-400">*</span>
                    <input id="email" type="email" name="email" required autocomplete="username" value="{{ old('email') }}" class="h-12 rounded-xl border border-white/15 bg-white/5 px-4 text-sm text-white outline-none transition placeholder:text-white/25 focus:border-red-500/70 focus:bg-white/[.07] focus:ring-4 focus:ring-red-500/10" placeholder="nombre@comunigest.test">
                </label>
                <label class="grid gap-2 text-sm font-medium" for="password">Contraseña <span class="text-red-400">*</span>
                    <input id="password" type="password" name="password" required autocomplete="current-password" class="h-12 rounded-xl border border-white/15 bg-white/5 px-4 text-sm text-white outline-none transition placeholder:text-white/25 focus:border-red-500/70 focus:bg-white/[.07] focus:ring-4 focus:ring-red-500/10" placeholder="Contraseña">
                </label>

                <label class="flex items-center gap-3 text-sm text-white/75"><input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-white/20 bg-white/5 text-red-600 focus:ring-red-500/20"> Recordarme</label>

                <div class="rounded-2xl border border-white/10 bg-white/[.045] p-2 shadow-inner">
                    <p class="px-2 pb-2 text-[9px] font-bold uppercase tracking-[.18em] text-white/35">Acceso rápido</p>
                    <div class="grid grid-cols-3 gap-2">
                        @foreach ([
                            ['ADM', 'Administrador', 'admin@comunigest.test', 'hover:border-red-400/40 hover:bg-red-500/10'],
                            ['EMP', 'Empleado', 'empleado@comunigest.test', 'hover:border-blue-400/40 hover:bg-blue-500/10'],
                            ['OWN', 'Propietario', 'propietario2@comunigest.test', 'hover:border-amber-400/40 hover:bg-amber-500/10'],
                        ] as [$code, $label, $email, $hoverClasses])
                            <button type="button" data-quick-login data-email="{{ $email }}" class="group flex min-w-0 flex-col items-center gap-1 rounded-xl border border-white/10 bg-white/[.055] px-2 py-2.5 transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $hoverClasses }}">
                                <span class="flex items-center gap-1.5 text-xs font-bold"><svg class="h-3.5 w-3.5 text-white/55 transition group-hover:text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/></svg>{{ $code }}</span>
                                <span class="truncate text-[9px] text-white/35">{{ $label }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>

                <button type="submit" data-login-submit class="mt-1 flex h-12 w-full items-center justify-center rounded-xl border border-red-400/25 bg-red-600 text-sm font-bold text-white shadow-lg shadow-red-950/30 transition duration-200 hover:-translate-y-0.5 hover:bg-red-500 disabled:cursor-wait disabled:opacity-60">
                    <span data-login-label>Acceder al Portal</span>
                </button>
            </form>

            <p class="mt-6 text-center text-[10px] text-white/25">© 2026 NOVA Community</p>
        </section>
    </main>
</body>
</html>
