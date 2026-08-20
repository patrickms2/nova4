@extends('layouts.auth')

@section('content')
    @php
    old('email', 'patrickms@gmail.com');
    @endphp
    <div class="grid min-h-screen lg:grid-cols-2">
    {{-- LEFT: Brand Panel --}}
    <div class="bg-gradient-to-br from-orange-600 to-orange-800 text-white relative hidden flex-col justify-between overflow-hidden p-12 lg:flex">
        <div class="pointer-events-none absolute -right-20 -top-20 size-80 rounded-full bg-white/10 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-24 -left-16 size-80 rounded-full bg-white/10 blur-3xl"></div>

        <a href="/" class="relative flex items-center gap-3 font-semibold text-xl">
            <span class="flex size-10 items-center justify-center rounded-lg bg-white/15">
                <x-lucide-building-2 class="size-5" />
            </span>
            NovaFactu
        </a>

        <figure class="relative max-w-md">
            <figcaption class="mt-6 flex items-center gap-3">
                <div class="size-12 rounded-full bg-white/20 flex items-center justify-center ring-2 ring-white/20">
                    <x-lucide-file-text class="size-5" />
                </div>
                <div class="text-sm">
                    <div class="font-semibold">Facturas</div>
                    <div class="opacity-70">Gestión fácil de facturas</div>
                </div>
            </figcaption>
            <figcaption class="mt-6 flex items-center gap-3">
                <div class="size-12 rounded-full bg-white/20 flex items-center justify-center ring-2 ring-white/20">
                    <x-lucide-users class="size-5" />
                </div>
                <div class="text-sm">
                    <div class="font-semibold">Clientes</div>
                    <div class="opacity-70">Gestión de clientes y conceptos</div>
                </div>
            </figcaption>
            <figcaption class="mt-6 flex items-center gap-3">
                <div class="size-12 rounded-full bg-white/20 flex items-center justify-center ring-2 ring-white/20">
                    <x-lucide-building-2 class="size-5" />
                </div>
                <div class="text-sm">
                    <div class="font-semibold">Empresas</div>
                    <div class="opacity-70">Gestión de empresas</div>
                </div>
            </figcaption>
            <figcaption class="mt-6 flex items-center gap-3">
                <div class="size-12 rounded-full bg-white/20 flex items-center justify-center ring-2 ring-white/20">
                    <x-lucide-calendar class="size-5" />
                </div>
                <div class="text-sm">
                    <div class="font-semibold">Remesas</div>
                    <div class="opacity-70">Gestión de remesas</div>
                </div>
            </figcaption>
<figcaption class="mt-6 flex items-center gap-3">
                <div class="size-12 rounded-full bg-white/20 flex items-center justify-center ring-2 ring-white/20">
                    <x-lucide-pdf class="size-5" />
                </div>
                <div class="text-sm">
                    <div class="font-semibold">Pdf</div>
                    <div class="opacity-70">Generación individual y masiva pdf</div>
                </div>
            </figcaption>
            <figcaption class="mt-6 flex items-center gap-3">
                <div class="size-12 rounded-full bg-white/20 flex items-center justify-center ring-2 ring-white/20">
                    <x-lucide-settings class="size-5" />
                </div>
                <div class="text-sm">
                    <div class="font-semibold">Verifactu</div>
                    <div class="opacity-70">Exportación a Verifactu</div>
                </div>
            </figcaption>
        </figure>

        <div class="relative flex gap-10 text-sm">
            <div>
                <div class="text-3xl font-bold"><x-lucide-check class="size-5" /></div>
                <div class="opacity-70">Multiempresa</div>
            </div>
            <div>
                <div class="text-3xl font-bold"><x-lucide-check class="size-5" /></div>
                <div class="opacity-70">Clientes / Conceptos</div>
            </div>
            <div>
                <div class="text-3xl font-bold"><x-lucide-check class="size-5" /></div>
                <div class="opacity-70">Remesas</div>
            </div>
            <div>
                <div class="text-3xl font-bold"><x-lucide-check class="size-5" /></div>
                <div class="opacity-70">Importación</div>
            </div>
            <div>
                <div class="text-3xl font-bold"><x-lucide-check class="size-5" /></div>
                <div class="opacity-70">Verifactu Ready</div>
            </div>
        </div>
    </div>

    {{-- RIGHT: Login Form --}}
    <div class="relative flex items-center justify-center p-6 sm:p-12 bg-white dark:bg-neutral-950">
        <div class="w-full max-w-sm">
            {{-- Mobile logo --}}
            <a href="/" class="mb-10 flex items-center gap-3 font-semibold lg:hidden">
                <span class="bg-blue-600 text-white flex size-10 items-center justify-center rounded-lg">
                    <x-lucide-building-2 class="size-5" />
                </span>
                NovaFactu
            </a>

            <div class="mb-8">
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">Acceso Novafactu</h1>
                <p class="text-muted-foreground mt-2 text-sm">Inicie sesión para acceso a facturación</p>
            </div>

            @if(session('success'))
                <x-ui.alert variant="success" class="mb-6">
                    <x-ui.alert-description>{{ session('success') }}</x-ui.alert-description>
                </x-ui.alert>
            @endif

            @if($errors->any())
                <x-ui.alert variant="destructive" class="mb-6">
                    <x-ui.alert-title>Login Gagal</x-ui.alert-title>
                    <x-ui.alert-description>
                        @foreach($errors->all() as $error)
                        {{ $error }}
                        @endforeach
                    </x-ui.alert-description>
                </x-ui.alert>
            @endif

            <form method="POST" action="{{ route('login') }}" class="grid gap-5">
                @csrf
                <x-ui.field>
                    <x-ui.field-label for="email">Email</x-ui.field-label>
                    <x-ui.input id="email" type="email" name="email" placeholder="Email" value="{{ old('email','patrickms@gmail.com') }}" required />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.field-label for="password">Contraseña</x-ui.field-label>
                    <x-ui.input id="password" type="password" name="password" placeholder="Contraseña" value="{{ old('password','password') }}" required />
                </x-ui.field>

                <x-ui.button type="submit" class="w-full" size="lg">
                    <x-lucide-log-in class="size-4" />
                    Acceder
                </x-ui.button>
            </form>

            <x-ui.separator class="my-8">
                <span class="text-xs text-muted-foreground uppercase tracking-wider">sólo administradores</span>
            </x-ui.separator>

            <p class="text-muted-foreground text-center text-xs">
                Novagestión &copy; {{ date('Y') }}
            </p>
        </div>
    </div>
</div>
@endsection
