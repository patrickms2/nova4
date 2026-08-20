<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Commands Quick Reference -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">📋 Comandos Rápidos</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <h3 class="font-medium text-green-600">✅ Backup y Restore</h3>
                    <div class="space-y-1 text-sm">
                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded font-mono text-xs">
                            ./scripts/portal-master.sh backup interactive
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded font-mono text-xs">
                            php artisan portal:async backup --categories=portal
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded font-mono text-xs">
                            ./scripts/portal-master.sh backup restore
                        </div>
                    </div>
                </div>
                <div class="space-y-2">
                    <h3 class="font-medium text-blue-600">⚡ Sincronización</h3>
                    <div class="space-y-1 text-sm">
                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded font-mono text-xs">
                            ./scripts/portal-master.sh sync zip
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded font-mono text-xs">
                            php artisan portal:async sync --profile=portal
                        </div>
                        <div class="bg-gray-100 dark:bg-gray-700 p-2 rounded font-mono text-xs">
                            php artisan portal:async status
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profiles and Categories -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">🎯 Perfiles y Categorías</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h3 class="font-medium text-purple-600 mb-2">📦 Perfiles de Sincronización ZIP</h3>
                    <div class="space-y-1 text-sm">
                        <div><strong>🚪 Portal Taxista:</strong> 13 archivos, 41KB</div>
                        <div><strong>🎨 Frontend Completo:</strong> 812 archivos, 13.7MB</div>
                        <div><strong>🔧 Backend Completo:</strong> 931 archivos, 4.1MB</div>
                        <div><strong>📦 Proyecto Completo:</strong> 3,418 archivos, 238MB</div>
                    </div>
                </div>
                <div>
                    <h3 class="font-medium text-orange-600 mb-2">🗂️ Categorías de Backup</h3>
                    <div class="grid grid-cols-2 gap-1 text-sm">
                        <div>🚪 Portal Taxista</div>
                        <div>⚡ Componentes Livewire</div>
                        <div>🎛️ Panel Filament</div>
                        <div>📦 Modelos de Datos</div>
                        <div>⚙️ Configuración</div>
                        <div>🗄️ Base de Datos</div>
                        <div>🎨 Recursos Frontend</div>
                        <div>🔧 Comandos Console</div>
                        <div>📚 Documentación</div>
                        <div>🧪 Tests</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Workflows -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">🔄 Flujos de Trabajo</h2>
            <div class="space-y-4">
                <div class="border-l-4 border-green-500 pl-4">
                    <h3 class="font-medium text-green-600">Desarrollo Diario (~10 segundos)</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        1. ./scripts/portal-master.sh backup interactive → portal<br>
                        2. ./scripts/portal-master.sh sync zip<br>
                        3. Total: ~10 segundos
                    </div>
                </div>
                <div class="border-l-4 border-yellow-500 pl-4">
                    <h3 class="font-medium text-yellow-600">Cambios Importantes (~1 minuto)</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        1. ./scripts/portal-master.sh backup interactive → portal,models,config<br>
                        2. ./scripts/portal-master.sh sync zip-frontend<br>
                        3. Total: ~1 minuto
                    </div>
                </div>
                <div class="border-l-4 border-red-500 pl-4">
                    <h3 class="font-medium text-red-600">Deploy Completo</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        1. php artisan portal:async backup --categories=all<br>
                        2. php artisan portal:async sync --profile=full<br>
                        3. php artisan portal:async monitor --user=user_12345
                    </div>
                </div>
            </div>
        </div>

        <!-- Troubleshooting -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold mb-4">🛠️ Solución de Problemas</h2>
            <div class="space-y-3">
                <div class="border border-red-200 rounded p-3">
                    <h3 class="font-medium text-red-600">SSH Connection Failed</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        1. Verificar configuración SSH en .env<br>
                        2. Probar conexión manual con sshpass<br>
                        3. Revisar que el servidor esté accesible
                    </div>
                </div>
                <div class="border border-yellow-200 rounded p-3">
                    <h3 class="font-medium text-yellow-600">Backup Too Large</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        1. Usar backup interactivo<br>
                        2. Seleccionar categorías específicas<br>
                        3. Evitar "all" para backups frecuentes
                    </div>
                </div>
                <div class="border border-blue-200 rounded p-3">
                    <h3 class="font-medium text-blue-600">Sync Slow</h3>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        1. Usar perfil específico en lugar de full<br>
                        2. Preferir "sync zip" para cambios rápidos<br>
                        3. Verificar conexión de red
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
