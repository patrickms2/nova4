<x-filament-panels::page>
    @php
        $links = [
            [
                'label' => 'Perfil',
                'description' => 'Actualizar nombre, correo y preferencias de cuenta.',
                'url' => route('profile.edit'),
            ],
            [
                'label' => 'Password',
                'description' => 'Cambiar la contraseña y reforzar acceso.',
                'url' => route('user-password.edit'),
            ],
            [
                'label' => 'Apariencia',
                'description' => 'Configurar tema visual y presentación.',
                'url' => route('appearance.edit'),
            ],
            [
                'label' => 'Portal taxista',
                'description' => 'Ir al resumen operativo del taxista.',
                'url' => \App\Filament\Portal\Pages\TaxistaPortal::getUrl(panel: 'portal'),
            ],
            [
                'label' => 'Perfil empleado',
                'description' => 'Abrir ficha con tabs de turnos, vacaciones y documentos.',
                'url' => \App\Filament\App\Pages\EmployeeProfile::getUrl(panel: 'app'),
            ],
        ];

        if (Route::has('two-factor.show')) {
            $links[] = [
                'label' => 'Doble factor',
                'description' => 'Activar y administrar verificación en dos pasos.',
                'url' => route('two-factor.show'),
            ];
        }
    @endphp

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach ($links as $link)
            <a
                href="{{ $link['url'] }}"
                class="rounded-xl border border-gray-200 bg-white p-4 transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600 dark:hover:bg-primary-950/20"
            >
                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $link['label'] }}</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $link['description'] }}</p>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
