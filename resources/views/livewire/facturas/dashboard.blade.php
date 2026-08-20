<div class="min-h-screen bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Welcome Section --}}
        <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white mb-8">
            <h1 class="text-2xl font-bold mb-2">Dashboard General</h1>
            <p class="opacity-90">Visión general de tu gestión de proyectos, tareas, notas y facturación</p>
        </div>

        {{-- Quick Stats Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- Projects Stats --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Proyectos</h3>
                    <span class="text-2xl">📁</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total</span>
                        <span class="font-bold text-primary">{{ $projectsStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Activos</span>
                        <span class="font-bold text-green-600">{{ $projectsStats['active'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">En Desarrollo</span>
                        <span class="font-bold text-yellow-600">{{ $projectsStats['inDevelopment'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Completados</span>
                        <span class="font-bold text-blue-600">{{ $projectsStats['completed'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Tasks Stats --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Tareas</h3>
                    <span class="text-2xl">✅</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total</span>
                        <span class="font-bold text-primary">{{ $tasksStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Pendientes</span>
                        <span class="font-bold text-yellow-600">{{ $tasksStats['pending'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">En Progreso</span>
                        <span class="font-bold text-blue-600">{{ $tasksStats['inProgress'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Completadas</span>
                        <span class="font-bold text-green-600">{{ $tasksStats['completed'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Alta Prioridad</span>
                        <span class="font-bold text-red-600">{{ $tasksStats['highPriority'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Notes Stats --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Notas</h3>
                    <span class="text-2xl">📝</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total</span>
                        <span class="font-bold text-primary">{{ $notesStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Fijadas</span>
                        <span class="font-bold text-yellow-600">{{ $notesStats['pinned'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Esta Semana</span>
                        <span class="font-bold text-blue-600">{{ $notesStats['thisWeek'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Con Etiquetas</span>
                        <span class="font-bold text-green-600">{{ $notesStats['withTags'] }}</span>
                    </div>
                </div>
            </div>

            {{-- Invoices Stats --}}
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Facturas</h3>
                    <span class="text-2xl">📄</span>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Total</span>
                        <span class="font-bold text-primary">{{ $invoicesStats['total'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Este Mes</span>
                        <span class="font-bold text-blue-600">{{ $invoicesStats['thisMonth'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Pagadas</span>
                        <span class="font-bold text-green-600">{{ $invoicesStats['paid'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Pendientes</span>
                        <span class="font-bold text-yellow-600">{{ $invoicesStats['unpaid'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Importe Total</span>
                        <span class="font-bold text-primary">{{ number_format($invoicesStats['totalAmount'], 2) }}€</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200 mb-8">
            <h2 class="text-lg font-semibold mb-4 text-gray-900">Acciones Rápidas</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <a href="{{ route('projects.index') }}"
                   class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition-colors">
                    <span class="text-2xl mb-2">📁</span>
                    <span class="text-sm font-medium text-gray-700">Nuevo Proyecto</span>
                </a>
                <a href="{{ route('tasks.index') }}"
                   class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors">
                    <span class="text-2xl mb-2">✅</span>
                    <span class="text-sm font-medium text-gray-700">Nueva Tarea</span>
                </a>
                <a href="{{ route('notes.index') }}"
                   class="flex flex-col items-center p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors">
                    <span class="text-2xl mb-2">📝</span>
                    <span class="text-sm font-medium text-gray-700">Nueva Nota</span>
                </a>
                <a href="{{ route('facturacion.facturas2') }}"
                   class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors">
                    <span class="text-2xl mb-2">📄</span>
                    <span class="text-sm font-medium text-gray-700">Nueva Factura</span>
                </a>
            </div>
        </div>

        {{-- Recent Activity --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h2 class="text-lg font-semibold mb-4 text-gray-900">Proyectos Recientes</h2>
                <div class="space-y-3">
                    @if($recentProjects->count() > 0)
                        @foreach($recentProjects as $project)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $project->name }}</p>
                                    <p class="text-sm text-gray-500">{{ $project->phase }}</p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full
                                    @if($project->status === 'active') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800 @endif">
                                    {{ $project->status }}
                                </span>
                            </div>
                        @endforeach
                    @else
                        <p class="text-gray-500 text-center py-4">No hay proyectos recientes</p>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm p-6 border border-gray-200">
                <h2 class="text-lg font-semibold mb-4 text-gray-900">Tareas Pendientes</h2>
                <div class="space-y-3">
                    @if($pendingTasks->count() > 0)
                        @foreach($pendingTasks as $task)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-900">{{ $task->title }}</p>
                                    <p class="text-sm text-gray-500">{{ $task->priority }}</p>
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
                        <p class="text-gray-500 text-center py-4">No hay tareas pendientes</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
