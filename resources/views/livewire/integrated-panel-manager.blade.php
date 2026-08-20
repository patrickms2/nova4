<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <!-- Header -->
    <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <svg class="fi-icon fi-size-lg text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Panel Manager Pro</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button wire:click="createPanel" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <svg class="fi-icon fi-size-sm mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nuevo Panel
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Tabs -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex space-x-8" aria-label="Tabs">
                <button wire:click="switchView('panels')"
                        class="{{ $currentView === 'panels' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}
                               whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <svg class="fi-icon fi-size-sm inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                    Paneles
                </button>
                <button wire:click="switchView('resources')"
                        class="{{ $currentView === 'resources' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}
                               whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <svg class="fi-icon fi-size-sm inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Recursos
                </button>
                <button wire:click="switchView('models')"
                        class="{{ $currentView === 'models' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}
                               whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-200">
                    <svg class="fi-icon fi-size-sm inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
                    </svg>
                    Modelos
                </button>
            </nav>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('message'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4">
                <div class="flex">
                    <svg class="fi-icon fi-size-sm text-green-500 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-green-700 dark:text-green-300">{{ session('message') }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Panels View -->
        @if($currentView === 'panels')
            @if($selectedPanel)
                <!-- Panel Detail View -->
                <div class="mb-6">
                    <button wire:click="switchView('panels')" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
                        <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Volver a Paneles
                    </button>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg overflow-hidden">
                    <!-- Panel Header -->
                    <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-800 dark:to-blue-900 p-6">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="bg-white/20 p-3 rounded-xl mr-4">
                                    <svg class="fi-icon fi-size-lg text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-white">{{ $selectedPanel->name }}</h2>
                                    <p class="text-blue-100">{{ $selectedPanel->description }}</p>
                                </div>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="editPanel({{ $selectedPanel->id }})" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Editar
                                </button>
                                <button wire:click="generateFilamentResource({{ $selectedPanel->id }})" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                    Generar Recurso
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Panel Content -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <!-- Fields Section -->
                            <div class="lg:col-span-1">
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-gray-900 dark:text-white">Campos</h3>
                                        <button wire:click="createField" class="text-blue-600 hover:text-blue-800">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        @if($selectedPanel->fields->count() > 0)
                                            @foreach($selectedPanel->fields as $field)
                                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $field->name }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $field->type }}</div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-gray-500 text-center py-4">No hay campos definidos</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Relations Section -->
                            <div class="lg:col-span-1">
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-gray-900 dark:text-white">Relaciones</h3>
                                        <button wire:click="createRelation" class="text-blue-600 hover:text-blue-800">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        @if($selectedPanel->relations->count() > 0)
                                            @foreach($selectedPanel->relations as $relation)
                                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $relation->related_model }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $relation->type }}</div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-gray-500 text-center py-4">No hay relaciones definidas</p>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Tables Section -->
                            <div class="lg:col-span-1">
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-xl p-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="font-bold text-gray-900 dark:text-white">Tablas</h3>
                                        <button wire:click="createTable" class="text-blue-600 hover:text-blue-800">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="space-y-2">
                                        @if($selectedPanel->tables->count() > 0)
                                            @foreach($selectedPanel->tables as $table)
                                                <div class="bg-white dark:bg-gray-800 p-3 rounded-lg border border-gray-200 dark:border-gray-700">
                                                    <div class="font-medium text-gray-900 dark:text-white">{{ $table->name }}</div>
                                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $table->title }}</div>
                                                </div>
                                            @endforeach
                                        @else
                                            <p class="text-gray-500 text-center py-4">No hay tablas definidas</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <!-- Panels List View -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($panels as $panel)
                        <div wire:click="selectPanel({{ $panel->id }})" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg hover:shadow-xl transition-all duration-200 cursor-pointer border border-gray-200 dark:border-gray-700 hover:border-blue-300 dark:hover:border-blue-600">
                            <div class="p-6">
                                <div class="flex items-center mb-4">
                                    <div class="bg-blue-100 dark:bg-blue-900 p-3 rounded-lg mr-3">
                                        <svg class="fi-icon fi-size-md text-blue-600 dark:text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="font-bold text-gray-900 dark:text-white">{{ $panel->name }}</h3>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $panel->navigation_group }}</p>
                                    </div>
                                </div>
                                <p class="text-gray-600 dark:text-gray-300 text-sm mb-4">{{ $panel->description }}</p>
                                <div class="flex items-center justify-between">
                                    <div class="flex space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                        <span>{{ $panel->fields->count() }} campos</span>
                                        <span>{{ $panel->relations->count() }} relaciones</span>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button wire:click.stop="editPanel({{ $panel->id }})" class="text-blue-600 hover:text-blue-800">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                        </button>
                                        <button wire:click.stop="deletePanel({{ $panel->id }})" class="text-red-600 hover:text-red-800">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($panels->count() === 0)
                    <div class="text-center py-12">
                        <svg class="fi-icon fi-size-xl text-gray-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay paneles creados</h3>
                        <p class="text-gray-500 dark:text-gray-400 mb-4">Comienza creando tu primer panel para gestionar tus recursos</p>
                        <button wire:click="createPanel" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
                            <svg class="fi-icon fi-size-sm mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            Crear Primer Panel
                        </button>
                    </div>
                @endif
            @endif
        @endif

        <!-- Resources View -->
        @if($currentView === 'resources')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Recursos Filament Generados</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($panels as $panel)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <h3 class="font-medium text-gray-900 dark:text-white">{{ Str::studly($panel->name) }}Resource</h3>
                                @if(file_exists(app_path("Filament/Resources/" . Str::studly($panel->name) . "Resource.php")))
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Activo
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                                        No generado
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                                <p>Modelo: {{ Str::studly($panel->name) }}</p>
                                <p>Grupo: {{ $panel->navigation_group }}</p>
                                <p>Campos: {{ $panel->fields->count() }}</p>
                            </div>
                            <div class="flex space-x-2">
                                <button wire:click="generateFilamentResource({{ $panel->id }})" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium">
                                    Generar
                                </button>
                                @if(file_exists(app_path("Filament/Resources/" . Str::studly($panel->name) . "Resource.php")))
                                    @php
                                        $resourceName = strtolower(Str::studly($panel->name));
                                        $routeName = 'filament.admin.resources.' . $resourceName . '.index';
                                    @endphp
                                    @if(Route::has($routeName))
                                        <a href="{{ route($routeName) }}"
                                           target="_blank" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-sm font-medium text-center">
                                            Ver
                                        </a>
                                    @else
                                        <span class="flex-1 bg-gray-400 text-gray-200 px-3 py-1.5 rounded text-sm font-medium text-center cursor-not-allowed">
                                            No disponible
                                        </span>
                                    @endif
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Models View -->
        @if($currentView === 'models')
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6">Modelos Generados</h2>

                <div class="space-y-4">
                    @foreach($panels as $panel)
                        <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="font-medium text-gray-900 dark:text-white">{{ Str::studly($panel->name) }}</h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $panel->name }}</p>
                                </div>
                                <div class="flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                                    <span>{{ $panel->fields->count() }} campos</span>
                                    <span>{{ $panel->relations->count() }} relaciones</span>
                                    @if(file_exists(app_path("Models/" . Str::studly($panel->name) . ".php")))
                                        <span class="text-green-600">✓ Generado</span>
                                    @else
                                        <span class="text-gray-400">No generado</span>
                                    @endif
                                </div>
                            </div>

                            @if($panel->fields->count() > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Campos:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($panel->fields as $field)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                                {{ $field->name }}: {{ $field->type }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if($panel->relations->count() > 0)
                                <div class="mt-4">
                                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Relaciones:</h4>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($panel->relations as $relation)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-medium rounded bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">
                                                {{ $relation->type }}: {{ $relation->related_model }}
                                            </span>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </main>

    <!-- Panel Form Modal -->
    @if($showPanelForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $panelId ? 'Editar Panel' : 'Crear Panel' }}
                        </h2>
                        <button wire:click="$set('showPanelForm', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="fi-icon fi-size-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="savePanel" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre <span class="text-red-500">*</span></label>
                                <input type="text" id="name" wire:model.live="name"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('name') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Slug <span class="text-red-500">*</span></label>
                                <input type="text" id="slug" wire:model="slug"
                                       class="text-gray-700 text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('slug') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                            <textarea id="description" wire:model="description" rows="3"
                                      class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Icono</label>
                                <input type="text" id="icon" wire:model="icon"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="navigationGroup" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Grupo de Navegación</label>
                                <input type="text" id="navigationGroup" wire:model="navigationGroup"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="navigationSort" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orden de Navegación</label>
                                <input type="number" id="navigationSort" wire:model="navigationSort"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="isActive" wire:model="isActive" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Activo</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showPanelForm', false)"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $panelId ? 'Actualizar' : 'Crear' }} Panel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Field Form Modal -->
    @if($showFieldForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $fieldId ? 'Editar Campo' : 'Crear Campo' }}
                        </h2>
                        <button wire:click="$set('showFieldForm', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="fi-icon fi-size-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveField" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="fieldName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Campo <span class="text-red-500">*</span></label>
                                <input type="text" id="fieldName" wire:model="fieldName"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('fieldName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="fieldLabel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Etiqueta del Campo <span class="text-red-500">*</span></label>
                                <input type="text" id="fieldLabel" wire:model="fieldLabel"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('fieldLabel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="fieldType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Migración</label>
                                <select id="fieldType" wire:model="fieldType"
                                        class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach($availableFieldTypes as $value => $label)
                                        <option value="{{ $value }}" {{ $fieldType == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="filamentFieldType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Campo de Formulario</label>
                                <select id="filamentFieldType" wire:model="filamentFieldType"
                                        class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach($availableFilamentFieldTypes as $value => $label)
                                        <option value="{{ $value }}" {{ $filamentFieldType == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="columnType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Columna de Tabla</label>
                                <select id="columnType" wire:model="columnType"
                                        class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach($availableColumnTypes as $value => $label)
                                        <option value="{{ $value }}" {{ $columnType == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="flex items-center">
                                    <input type="checkbox" id="fieldNullable" wire:model="fieldNullable" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                                    <span class="text-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Nulable</span>
                                </label>
                            </div>

                            <div>
                                <label for="fieldDefault" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Valor por Defecto</label>
                                <input type="text" id="fieldDefault" wire:model="fieldDefault"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showFieldForm', false)"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $fieldId ? 'Actualizar' : 'Crear' }} Campo
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Relation Form Modal -->
    @if($showRelationForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $relationId ? 'Editar Relación' : 'Crear Relación' }}
                        </h2>
                        <button wire:click="$set('showRelationForm', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="fi-icon fi-size-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveRelation" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="relationType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Relación</label>
                                <select id="relationType" wire:model="relationType"
                                        class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                    @foreach($availableRelationTypes as $value => $label)
                                        <option value="{{ $value }}" {{ $relationType == $value ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="relatedModel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Modelo Relacionado</label>
                                <input type="text" id="relatedModel" wire:model="relatedModel"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('relatedModel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="foreignKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Clave Foránea</label>
                                <input type="text" id="foreignKey" wire:model="foreignKey"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="relationMethodName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre del Método</label>
                                <input type="text" id="relationMethodName" wire:model="relationMethodName"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showRelationForm', false)"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $relationId ? 'Actualizar' : 'Crear' }} Relación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Table Form Modal -->
    @if($showTableForm)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-2xl w-full mx-4 max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                            {{ $tableId ? 'Editar Tabla' : 'Crear Tabla' }}
                        </h2>
                        <button wire:click="$set('showTableForm', false)" class="text-gray-400 hover:text-gray-600">
                            <svg class="fi-icon fi-size-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form wire:submit.prevent="saveTable" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="tableName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre de la Tabla <span class="text-red-500">*</span></label>
                                <input type="text" id="tableName" wire:model="tableName"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('tableName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label for="tableTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título de la Tabla <span class="text-red-500">*</span></label>
                                <input type="text" id="tableTitle" wire:model="tableTitle"
                                       class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                @error('tableTitle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="tableDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Descripción</label>
                            <textarea id="tableDescription" wire:model="tableDescription" rows="3"
                                      class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                        </div>

                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" id="isDefaultTable" wire:model="isDefaultTable" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tabla por Defecto</span>
                            </label>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" wire:click="$set('showTableForm', false)"
                                    class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                {{ $tableId ? 'Actualizar' : 'Crear' }} Tabla
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>
