<div>
    <!-- Header -->
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Panel Builder</h1>
                <div class="flex space-x-3">
                    <button wire:click="createPanel"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <svg class="-ml-1 mr-2 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        New Panel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if(session()->has('message'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
            <div class="rounded-md bg-green-50 p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('message') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Panels Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($panels as $panel)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-200">
                    <!-- Panel Header -->
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center">
                                @if($panel->icon)
                                    <span class="text-2xl mr-3">{{ $panel->icon }}</span>
                                @endif
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $panel->name }}</h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ $panel->slug }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                @if($panel->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Active
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                        Inactive
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if($panel->description)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ Str::limit($panel->description, 120) }}</p>
                        @endif

                        <!-- Stats -->
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $panel->fields->count() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Fields</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-purple-600">{{ $panel->relations->count() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Relations</div>
                            </div>
                            <div class="text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ $panel->tables->count() }}</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">Tables</div>
                            </div>
                        </div>

                        <!-- Navigation Info -->
                        @if($panel->navigation_group)
                            <div class="mb-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $panel->navigation_group }}
                                </span>
                                @if($panel->navigation_sort)
                                    <span class="ml-2 text-xs text-gray-500">Sort: {{ $panel->navigation_sort }}</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="bg-gray-50 dark:bg-gray-700 px-6 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex space-x-2">
                                <button wire:click="editPanel({{ $panel->id }})"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                    Edit
                                </button>

                                <button wire:click="createField({{ $panel->id }})"
                                        class="text-green-600 hover:text-green-800 text-sm font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Field
                                </button>

                                <button wire:click="createRelation({{ $panel->id }})"
                                        class="text-purple-600 hover:text-purple-800 text-sm font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                    </svg>
                                    Relation
                                </button>

                                <button wire:click="createTable({{ $panel->id }})"
                                        class="text-orange-600 hover:text-orange-800 text-sm font-medium flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    Table
                                </button>
                            </div>

                            <button wire:click="generateCode({{ $panel->id }})"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                                </svg>
                                Generate
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach

            <!-- Add New Panel Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6 flex items-center justify-center hover:shadow-xl transition-shadow duration-200 cursor-pointer"
                 wire:click="createPanel">
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">Create New Panel</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start building your panel</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Panel Form Modal -->
    <x-filament::modal wire:model="showPanelForm" heading="{{ $panelId ? 'Edit Panel' : 'Create Panel' }}">
        <form wire:submit.prevent="savePanel" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name <span class="text-red-500">*</span></label>
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
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea id="description" wire:model="description" rows="3"
                          class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Icon</label>
                    <input type="text" id="icon" wire:model="icon"
                           class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="navigationGroup" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Navigation Group</label>
                    <input type="text" id="navigationGroup" wire:model="navigationGroup"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="navigationSort" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Navigation Sort</label>
                    <input type="number" id="navigationSort" wire:model="navigationSort"
                           class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" id="isActive" wire:model="isActive" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Active</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="$set('showPanelForm', false)"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ $panelId ? 'Update' : 'Create' }} Panel
                </button>
            </div>
        </form>
    </x-filament::modal>

    <!-- Field Form Modal -->
    <x-filament::modal wire:model="showFieldForm" heading="{{ $fieldId ? 'Edit Field' : 'Create Field' }}">
        <form wire:submit.prevent="saveField" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="fieldName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Field Name <span class="text-red-500">*</span></label>
                    <input type="text" id="fieldName" wire:model="fieldName"
                           class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    @error('fieldName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="fieldLabel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Field Label <span class="text-red-500">*</span></label>
                    <input type="text" id="fieldLabel" wire:model="fieldLabel"
                           class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    @error('fieldLabel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="fieldType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Migration Type</label>
                    <select id="fieldType" wire:model="fieldType"
                            class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @foreach($availableFieldTypes as $value => $label)
                            <option value="{{ $value }}" {{ $fieldType == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filamentFieldType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Form Field Type</label>
                    <select id="filamentFieldType" wire:model="filamentFieldType"
                            class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @foreach($availableFilamentFieldTypes as $value => $label)
                            <option value="{{ $value }}" {{ $filamentFieldType == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="columnType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Table Column Type</label>
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
                        <span class="text-gray-700 text-sm font-medium text-gray-700 dark:text-gray-300">Nullable</span>
                    </label>
                </div>

                <div>
                    <label for="fieldDefault" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Default Value</label>
                    <input type="text" id="fieldDefault" wire:model="fieldDefault"
                           class="text-gray-700 mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="$set('showFieldForm', false)"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ $fieldId ? 'Update' : 'Create' }} Field
                </button>
            </div>
        </form>
    </x-filament::modal>

    <!-- Relation Form Modal -->
    <x-filament::modal wire:model="showRelationForm" heading="{{ $relationId ? 'Edit Relation' : 'Create Relation' }}">
        <form wire:submit.prevent="saveRelation" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="relationType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Relation Type</label>
                    <select id="relationType" wire:model="relationType"
                            class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        @foreach($availableRelationTypes as $value => $label)
                            <option value="{{ $value }}" {{ $relationType == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="relatedModel" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Related Model</label>
                    <input type="text" id="relatedModel" wire:model="relatedModel"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    @error('relatedModel') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="foreignKey" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Foreign Key</label>
                    <input type="text" id="foreignKey" wire:model="foreignKey"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>

                <div>
                    <label for="relationMethodName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Method Name</label>
                    <input type="text" id="relationMethodName" wire:model="relationMethodName"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="$set('showRelationForm', false)"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ $relationId ? 'Update' : 'Create' }} Relation
                </button>
            </div>
        </form>
    </x-filament::modal>

    <!-- Table Form Modal -->
    <x-filament::modal wire:model="showTableForm" heading="{{ $tableId ? 'Edit Table' : 'Create Table' }}">
        <form wire:submit.prevent="saveTable" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="tableName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Table Name <span class="text-red-500">*</span></label>
                    <input type="text" id="tableName" wire:model="tableName"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    @error('tableName') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="tableTitle" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Table Title <span class="text-red-500">*</span></label>
                    <input type="text" id="tableTitle" wire:model="tableTitle"
                           class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                    @error('tableTitle') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div>
                <label for="tableDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                <textarea id="tableDescription" wire:model="tableDescription" rows="3"
                          class="mt-1 w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" id="isDefaultTable" wire:model="isDefaultTable" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500 mr-2">
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Default Table</span>
                </label>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" wire:click="$set('showTableForm', false)"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    {{ $tableId ? 'Update' : 'Create' }} Table
                </button>
            </div>
        </form>
    </x-filament::modal>
</div>
