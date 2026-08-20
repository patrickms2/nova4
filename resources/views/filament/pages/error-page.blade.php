<x-filament-panels::page>
    <div class="flex flex-col items-center justify-center min-h-[60vh] space-y-6">
        {{-- Icono de error grande --}}
        <div class="text-6xl text-red-500">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-24 h-24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
        </div>

        {{-- Mensaje de error --}}
        <div class="text-center space-y-2">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100">
                Página No Encontrada
            </h1>
            <p class="text-lg text-gray-600 dark:text-gray-400 max-w-md">
                Lo sentimos, la página que buscas no existe o ha sido movida.
            </p>
        </div>

        {{-- Acciones rápidas --}}
        <div class="flex flex-col sm:flex-row gap-4 items-center">
            <a href="{{ route('filament.app.pages.dashboard') }}" 
               class="inline-flex items-center gap-2 px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.954A2.25 2.25 0 0 1 12.544 2.25H15a2.25 2.25 0 0 1 2.25 2.25v2.456c0 .597-.237 1.17-.66 1.59L9.75 17.25m0 0-3 3m0-3 3 3m-3-3h3" />
                </svg>
                Volver al Dashboard
            </a>
            
            <button onclick="history.back()" 
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6m-6 6h12a6 6 0 0 1 0 12h-3" />
                </svg>
                Página Anterior
            </button>
        </div>

        {{-- Sugerencias --}}
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 max-w-lg">
            <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100 mb-2">
                ¿Qué puedes hacer?
            </h3>
            <ul class="text-sm text-blue-800 dark:text-blue-200 space-y-1">
                <li>• Verifica la URL escrita correctamente</li>
                <li>• Usa el menú de navegación para encontrar lo que buscas</li>
                <li>• Contacta con soporte si crees que esto es un error</li>
                <li>• Haz clic en el botón de ayuda (❓) para más información</li>
            </ul>
        </div>
    </div>
</x-filament-panels::page>
