<x-filament-panels::page>
    <x-slot name="heading">
        Dashboard General
    </x-slot>

    <div class="space-y-6">
        <!-- Welcome Section -->
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
            <h1 class="text-2xl font-bold mb-2">Bienvenido a NovaFact</h1>
            <p class="opacity-90">Visión general de tu gestión de proyectos, tareas, notas y facturación</p>
        </div>

        <!-- Quick Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($this->getHeaderWidgets() as $widget)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-4 border border-gray-200 dark:border-gray-700">
                    {{ $widget }}
                </div>
            @endforeach
        </div>

        <!-- Quick Actions -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Acciones Rápidas</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('filament.fact.facturacion.resources.projects.create') }}"
                   class="flex flex-col items-center p-4 bg-orange-50 dark:bg-orange-900/20 rounded-lg hover:bg-orange-100 dark:hover:bg-orange-900/30 transition-colors">
                    <span class="text-2xl mb-2">📁</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nuevo Proyecto</span>
                </a>
                <a href="{{ route('filament.fact.facturacion.resources.tasks.create') }}"
                   class="flex flex-col items-center p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                    <span class="text-2xl mb-2">✅</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Tarea</span>
                </a>
                <a href="{{ route('filament.fact.facturacion.resources.notes.create') }}"
                   class="flex flex-col items-center p-4 bg-green-50 dark:bg-green-900/20 rounded-lg hover:bg-green-100 dark:hover:bg-green-900/30 transition-colors">
                    <span class="text-2xl mb-2">📝</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Nota</span>
                </a>
                <a href="{{ route('filament.fact.facturacion.resources.facturas.create') }}"
                   class="flex flex-col items-center p-4 bg-purple-50 dark:bg-purple-900/20 rounded-lg hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                    <span class="text-2xl mb-2">📄</span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Nueva Factura</span>
                </a>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Proyectos Recientes</h2>
                <div class="space-y-3">
                    @php
                        $recentProjects = \App\Models\Project::latest()->take(5)->get();
                    @endphp
                    @if($recentProjects->count() > 0)
                        @foreach($recentProjects as $project)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $project->name }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $project->phase }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($project->status === 'active') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $project->status }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No hay proyectos recientes</p>
                    @endif
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-gray-700">
                <h2 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Tareas Pendientes</h2>
                <div class="space-y-3">
                    @php
                        $pendingTasks = \App\Models\Task::where('status', 'pending')
                            ->orWhere('status', 'in_progress')
                            ->latest()
                            ->take(5)
                            ->get();
                    @endphp
                    @if($pendingTasks->count() > 0)
                        @foreach($pendingTasks as $task)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $task->title }}</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $task->priority }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($task->priority === 'high') bg-red-100 text-red-800
                                    @elseif($task->priority === 'medium') bg-yellow-100 text-yellow-800
                                    @else bg-blue-100 text-blue-800 @endif">
                                    {{ $task->priority }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 dark:text-gray-400 text-center py-4">No hay tareas pendientes</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
