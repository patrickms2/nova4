<x-filament-panels::page>
    <div class="space-y-8">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-800 dark:to-blue-900 rounded-xl p-6 mb-8 shadow-lg">
            <div class="flex items-center justify-between">
                <div class="text-white">
                    <h1 class="text-3xl font-bold mb-2">Panel Builder Dashboard</h1>
                    <p class="text-blue-100 text-lg">Manage your application panels and their configurations</p>
                </div>
                <button wire:click="createPanel" class="inline-flex items-center px-6 py-3 bg-white text-blue-600 border-2 border-white shadow-lg text-sm font-semibold rounded-lg hover:bg-blue-50 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-200 transform hover:scale-105">
                    <svg class="-ml-1 mr-2 fi-icon fi-size-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Panel
                </button>
            </div>
        </div>

        <!-- Stats Overview -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 rounded-xl shadow-xl p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                        <svg class="fi-icon fi-size-md text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-blue-100 truncate">Total Panels</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->getStats()['total_panels'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-green-500 to-green-600 dark:from-green-600 dark:to-green-700 rounded-xl shadow-xl p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                        <svg class="fi-icon fi-size-md text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-green-100 truncate">Active Panels</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->getStats()['active_panels'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-purple-500 to-purple-600 dark:from-purple-600 dark:to-purple-700 rounded-xl shadow-xl p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                        <svg class="fi-icon fi-size-md text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-purple-100 truncate">Total Fields</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->getStats()['total_fields'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-orange-500 to-orange-600 dark:from-orange-600 dark:to-orange-700 rounded-xl shadow-xl p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                        <svg class="fi-icon fi-size-md text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-orange-100 truncate">Relations</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->getStats()['total_relations'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-br from-red-500 to-red-600 dark:from-red-600 dark:to-red-700 rounded-xl shadow-xl p-6 transform transition-all duration-300 hover:scale-105 hover:shadow-2xl">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-white/20 backdrop-blur-sm rounded-xl p-4 border border-white/30">
                        <svg class="fi-icon fi-size-md text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-red-100 truncate">Tables</dt>
                            <dd class="text-2xl font-bold text-white">{{ $this->getStats()['total_tables'] }}</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panels List -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white flex items-center">
                        <svg class="fi-icon fi-size-md mr-3 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        Panels
                    </h3>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $this->panels->count() }} total panels
                    </div>
                </div>
            </div>

            <div class="p-6">
                @if($this->panels->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($this->panels as $panel)
                            <div class="relative group {{ $selectedPanel?->id === $panel->id ? 'ring-4 ring-blue-500 bg-gradient-to-br from-blue-50 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20' : 'bg-white dark:bg-gray-800' }} border-2 {{ $selectedPanel?->id === $panel->id ? 'border-blue-500' : 'border-gray-200 dark:border-gray-700' }} rounded-xl p-6 hover:shadow-2xl transition-all duration-300 cursor-pointer transform hover:scale-105 hover:border-blue-300 dark:hover:border-blue-600"
                                 wire:click="selectPanel({{ $panel->id }})">

                                <!-- Selection Indicator -->
                                @if($selectedPanel?->id === $panel->id)
                                    <div class="absolute top-3 right-3">
                                        <div class="bg-blue-500 text-white rounded-full p-1">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                    </div>
                                @endif

                                <!-- Panel Header -->
                                <div class="flex items-start justify-between mb-5">
                                    <div class="flex items-center">
                                        @if($panel->icon)
                                            <div class="text-4xl mr-4 p-3 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-xl shadow-inner">
                                                {{ $panel->icon }}
                                            </div>
                                        @endif
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-white text-xl mb-1">{{ $panel->name }}</h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400 font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">{{ $panel->slug }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if($panel->is_active)
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-green-500 to-green-600 text-white shadow-lg">
                                                <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-3 py-1.5 rounded-full text-xs font-bold bg-gradient-to-r from-gray-500 to-gray-600 text-white shadow-lg">
                                                Inactive
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Description -->
                                @if($panel->description)
                                    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-3 mb-5">
                                        <p class="text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ Str::limit($panel->description, 120) }}</p>
                                    </div>
                                @endif

                                <!-- Stats -->
                                <div class="grid grid-cols-3 gap-3 mb-5">
                                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/30 dark:to-blue-800/30 rounded-lg p-3 text-center">
                                        <div class="flex items-center justify-center text-blue-600 dark:text-blue-400 mb-1">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                            </svg>
                                        </div>
                                        <div class="text-lg font-bold text-blue-700 dark:text-blue-300">{{ $panel->fields->count() }}</div>
                                        <div class="text-xs text-blue-600 dark:text-blue-400">Fields</div>
                                    </div>
                                    <div class="bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/30 dark:to-purple-800/30 rounded-lg p-3 text-center">
                                        <div class="flex items-center justify-center text-purple-600 dark:text-purple-400 mb-1">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                            </svg>
                                        </div>
                                        <div class="text-lg font-bold text-purple-700 dark:text-purple-300">{{ $panel->relations->count() }}</div>
                                        <div class="text-xs text-purple-600 dark:text-purple-400">Relations</div>
                                    </div>
                                    <div class="bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/30 dark:to-orange-800/30 rounded-lg p-3 text-center">
                                        <div class="flex items-center justify-center text-orange-600 dark:text-orange-400 mb-1">
                                            <svg class="fi-icon fi-size-sm" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="text-lg font-bold text-orange-700 dark:text-orange-300">{{ $panel->tables->count() }}</div>
                                        <div class="text-xs text-orange-600 dark:text-orange-400">Tables</div>
                                    </div>
                                </div>

                                <!-- Navigation Group -->
                                @if($panel->navigation_group)
                                    <div class="mb-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 border border-gray-200 dark:border-gray-600">
                                            <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                            </svg>
                                            {{ $panel->navigation_group }}
                                        </span>
                                    </div>
                                @endif

                                <!-- Action Buttons -->
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="openFieldBuilder({{ $panel->id }})"
                                            class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white hover:from-blue-600 hover:to-blue-700 shadow-lg transform transition-all duration-200 hover:scale-105">
                                        <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add Field
                                    </button>
                                    <button wire:click="openRelationBuilder({{ $panel->id }})"
                                            class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 text-white hover:from-purple-600 hover:to-purple-700 shadow-lg transform transition-all duration-200 hover:scale-105">
                                        <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        Add Relation
                                    </button>
                                    <button wire:click="openTableBuilder({{ $panel->id }})"
                                            class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 text-white hover:from-orange-600 hover:to-orange-700 shadow-lg transform transition-all duration-200 hover:scale-105">
                                        <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                        Add Table
                                    </button>
                                    <button wire:click="generatePanelCode({{ $panel->id }})"
                                            class="inline-flex items-center px-4 py-2 text-xs font-bold rounded-lg bg-gradient-to-r from-green-500 to-green-600 text-white hover:from-green-600 hover:to-green-700 shadow-lg transform transition-all duration-200 hover:scale-105">
                                        <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                        </svg>
                                        Generate Code
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-16">
                        <div class="bg-gradient-to-br from-gray-100 to-gray-200 dark:from-gray-700 dark:to-gray-800 rounded-2xl p-8 max-w-md mx-auto">
                            <div class="bg-white dark:bg-gray-900 rounded-xl p-6 mb-6 shadow-inner">
                                <svg class="mx-auto fi-icon fi-size-2xl text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">No panels yet</h3>
                                <p class="text-gray-600 dark:text-gray-400 mb-6">Get started by creating your first panel to begin building your application.</p>
                            </div>
                            <button wire:click="createPanel" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold rounded-xl shadow-xl hover:from-blue-700 hover:to-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all duration-200 transform hover:scale-105">
                                <svg class="-ml-1 mr-3 fi-icon fi-size-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                Create Your First Panel
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Selected Panel Details -->
        @if($selectedPanel)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 dark:from-blue-700 dark:to-indigo-700 px-6 py-5 border-b border-blue-200 dark:border-blue-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            @if($selectedPanel->icon)
                                <div class="text-3xl mr-4 p-2 bg-white/20 backdrop-blur-sm rounded-xl border border-white/30">
                                    {{ $selectedPanel->icon }}
                                </div>
                            @endif
                            <div>
                                <h3 class="text-2xl font-bold text-white">{{ $selectedPanel->name }}</h3>
                                <p class="text-blue-100 text-sm">Panel Details & Configuration</p>
                            </div>
                        </div>
                        <button wire:click="selectPanel(null)" class="text-white/80 hover:text-white hover:bg-white/20 p-2 rounded-lg transition-all duration-200">
                            <svg class="fi-icon fi-size-md" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Fields -->
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/20 rounded-xl p-6 border border-blue-200 dark:border-blue-800">
                            <div class="flex items-center mb-6">
                                <div class="bg-blue-600 text-white p-3 rounded-xl mr-4 shadow-lg">
                                    <svg class="fi-icon fi-size-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white text-lg">Fields</h4>
                                    <p class="text-blue-600 dark:text-blue-400 text-sm">{{ $selectedPanel->fields->count() }} total fields</p>
                                </div>
                            </div>
                            @if($selectedPanel->fields->count() > 0)
                                <div class="space-y-3">
                                    @foreach($selectedPanel->fields as $field)
                                        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl border border-blue-200 dark:border-blue-700 shadow-sm hover:shadow-md transition-all duration-200">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-900 dark:text-white text-base">{{ $field->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $field->type }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 text-white shadow">{{ $field->filament_type }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-xs mx-auto">
                                        <svg class="fi-icon fi-size-xl mx-auto mb-4 text-blue-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                        <p class="font-medium">No fields defined</p>
                                        <p class="text-sm mt-1">Add fields to get started</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Relations -->
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 dark:from-purple-900/20 dark:to-pink-900/20 rounded-xl p-6 border border-purple-200 dark:border-purple-800">
                            <div class="flex items-center mb-6">
                                <div class="bg-purple-600 text-white p-3 rounded-xl mr-4 shadow-lg">
                                    <svg class="fi-icon fi-size-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-bold text-gray-900 dark:text-white text-lg">Relations</h4>
                                    <p class="text-purple-600 dark:text-purple-400 text-sm">{{ $selectedPanel->relations->count() }} total relations</p>
                                </div>
                            </div>
                            @if($selectedPanel->relations->count() > 0)
                                <div class="space-y-3">
                                    @foreach($selectedPanel->relations as $relation)
                                        <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl border border-purple-200 dark:border-purple-700 shadow-sm hover:shadow-md transition-all duration-200">
                                            <div class="flex-1">
                                                <div class="font-semibold text-gray-900 dark:text-white text-base">{{ $relation->related_model }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ $relation->type }}</div>
                                            </div>
                                            <div class="text-right">
                                                <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-gradient-to-r from-purple-500 to-purple-600 text-white shadow">{{ $relation->method_name }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                    <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-xs mx-auto">
                                        <svg class="fi-icon fi-size-xl mx-auto mb-4 text-purple-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        <p class="font-medium">No relations defined</p>
                                        <p class="text-sm mt-1">Add relations to connect models</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Tables -->
                    <div class="mt-8 bg-gradient-to-br from-orange-50 to-amber-50 dark:from-orange-900/20 dark:to-amber-900/20 rounded-xl p-6 border border-orange-200 dark:border-orange-800">
                        <div class="flex items-center mb-6">
                            <div class="bg-orange-600 text-white p-3 rounded-xl mr-4 shadow-lg">
                                <svg class="fi-icon fi-size-lg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <div>
                                <h4 class="font-bold text-gray-900 dark:text-white text-lg">Tables</h4>
                                <p class="text-orange-600 dark:text-orange-400 text-sm">{{ $selectedPanel->tables->count() }} total tables</p>
                            </div>
                        </div>
                        @if($selectedPanel->tables->count() > 0)
                            <div class="space-y-3">
                                @foreach($selectedPanel->tables as $table)
                                    <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-xl border border-orange-200 dark:border-orange-700 shadow-sm hover:shadow-md transition-all duration-200">
                                        <div class="flex-1">
                                            <div class="font-semibold text-gray-900 dark:text-white text-base">{{ $table->name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $table->title }}</div>
                                        </div>
                                        @if($table->is_default)
                                            <span class="inline-flex items-center px-3 py-1.5 text-xs font-bold rounded-lg bg-gradient-to-r from-orange-500 to-orange-600 text-white shadow">
                                                <svg class="fi-icon fi-size-sm mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                Default
                                            </span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-12 text-gray-500 dark:text-gray-400">
                                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-xs mx-auto">
                                    <svg class="fi-icon fi-size-xl mx-auto mb-4 text-orange-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <p class="font-medium">No tables defined</p>
                                    <p class="text-sm mt-1">Add tables to organize data</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modals -->
    @if($showCreateForm)
        <x-filament::modal wire:model="showCreateForm" heading="Create New Panel">
            <livewire:workflow-steps.panel-setup-step />
        </x-filament::modal>
    @endif

    @if($showFieldBuilder)
        <x-filament::modal wire:model="showFieldBuilder" heading="Field Builder">
            <livewire:workflow-steps.panel-setup-step  :panel-id="$selectedPanel?->id" />
        </x-filament::modal>
    @endif

    @if($showRelationBuilder)
        <x-filament::modal wire:model="showRelationBuilder" heading="Relation Builder">
            <livewire:relation-builder :panel-id="$selectedPanel?->id" />
        </x-filament::modal>
    @endif

    @if($showTableBuilder)
        <x-filament::modal wire:model="showTableBuilder" heading="Table Builder">
            <livewire:table-builder :panel-id="$selectedPanel?->id" />
        </x-filament::modal>
    @endif

    <script>
        // Listen for code generation events
        window.addEventListener('code-generated', function(event) {
            filament.notifications({
                title: 'Code Generated Successfully!',
                type: 'success',
                icon: 'heroicon-o-check-circle',
            });
        });
    </script>
</x-filament-panels::page>
