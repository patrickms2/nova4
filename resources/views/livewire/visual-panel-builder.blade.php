<div x-data="visualPanelBuilder()" x-init="init()">
    <!-- Header Toolbar -->
    <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Visual Panel Builder</h2>
                <div class="flex items-center space-x-2">
                    <button @click="togglePreview()"
                            class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600">
                        <span x-show="!showPreview">👁️ Preview</span>
                        <span x-show="showPreview">✏️ Edit</span>
                    </button>
                    <button @click="savePanel()"
                            class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        💾 Save
                    </button>
                    <button @click="generateCode()"
                            class="px-3 py-1 text-sm bg-green-600 text-white rounded-md hover:bg-green-700">
                        ⚡ Generate Code
                    </button>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <select x-model="currentPanel" @change="loadPanel()"
                        class="text-sm border border-gray-300 rounded-md px-3 py-1">
                    <option value="">Select Panel</option>
                    @foreach($panels as $panel)
                        <option value="{{ $panel->id }}">{{ $panel->name }}</option>
                    @endforeach
                </select>
                <button @click="createNewPanel()"
                        class="px-3 py-1 text-sm bg-purple-600 text-white rounded-md hover:bg-purple-700">
                    ➕ New Panel
                </button>
            </div>
        </div>
    </div>

    <!-- Main Builder Area -->
    <div class="flex h-screen pt-16">
        <!-- Left Sidebar - Field Types Palette -->
        <div class="w-64 bg-gray-50 dark:bg-gray-900 border-r border-gray-200 dark:border-gray-700 p-4 overflow-y-auto">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Field Types</h3>

            <!-- Basic Fields -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Basic</h4>
                <div class="space-y-2">
                    <div draggable="true"
                         @dragstart="startDrag($event, 'text')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📝</span>
                            <span class="text-sm">Text Input</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'textarea')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📄</span>
                            <span class="text-sm">Textarea</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'number')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🔢</span>
                            <span class="text-sm">Number</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'email')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📧</span>
                            <span class="text-sm">Email</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'password')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🔐</span>
                            <span class="text-sm">Password</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Choice Fields -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Choice</h4>
                <div class="space-y-2">
                    <div draggable="true"
                         @dragstart="startDrag($event, 'select')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📋</span>
                            <span class="text-sm">Select</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'checkbox')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">☑️</span>
                            <span class="text-sm">Checkbox</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'radio')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🔘</span>
                            <span class="text-sm">Radio</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'toggle')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🔄</span>
                            <span class="text-sm">Toggle</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Date/Time Fields -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Date & Time</h4>
                <div class="space-y-2">
                    <div draggable="true"
                         @dragstart="startDrag($event, 'date')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📅</span>
                            <span class="text-sm">Date</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'datetime')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📆</span>
                            <span class="text-sm">Date Time</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'time')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">⏰</span>
                            <span class="text-sm">Time</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Media Fields -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Media</h4>
                <div class="space-y-2">
                    <div draggable="true"
                         @dragstart="startDrag($event, 'file')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📎</span>
                            <span class="text-sm">File Upload</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'image')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🖼️</span>
                            <span class="text-sm">Image</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'richeditor')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📝</span>
                            <span class="text-sm">Rich Editor</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Relation Fields -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Relations</h4>
                <div class="space-y-2">
                    <div draggable="true"
                         @dragstart="startDrag($event, 'belongsTo')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🔗</span>
                            <span class="text-sm">Belongs To</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'hasMany')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">📚</span>
                            <span class="text-sm">Has Many</span>
                        </div>
                    </div>
                    <div draggable="true"
                         @dragstart="startDrag($event, 'belongsToMany')"
                         class="field-type-item bg-white dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-600 cursor-move hover:shadow-md">
                        <div class="flex items-center">
                            <span class="text-lg mr-2">🔀</span>
                            <span class="text-sm">Belongs To Many</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Canvas Area -->
        <div class="flex-1 bg-gray-100 dark:bg-gray-800 overflow-hidden">
            <!-- Edit Mode -->
            <div x-show="!showPreview" class="h-full">
                <!-- Form Builder -->
                <div class="h-full overflow-auto p-8">
                    <div class="max-w-4xl mx-auto">
                        <!-- Panel Header -->
                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Panel Name</label>
                                    <input type="text" x-model="panelData.name"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Model Name</label>
                                    <input type="text" x-model="panelData.modelName"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea x-model="panelData.description" rows="2"
                                          class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800"></textarea>
                            </div>
                        </div>

                        <!-- Form Fields Drop Zone -->
                        <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6 min-h-96">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Form Fields</h3>

                            <div class="drop-zone border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-8 text-center"
                                 @dragover.prevent="dragOver($event)"
                                 @dragleave.prevent="dragLeave($event)"
                                 @drop.prevent="dropField($event)">

                                <div x-show="fields.length === 0" class="text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                    </svg>
                                    <p class="mt-2">Drag field types here to build your form</p>
                                </div>

                                <!-- Draggable Fields -->
                                <div x-show="fields.length > 0" class="space-y-4">
                                    <template x-for="(field, index) in fields" :key="field.id">
                                        <div draggable="true"
                                             @dragstart="startDragField($event, index)"
                                             @dragover.prevent="dragOverField($event, index)"
                                             @drop.prevent="dropFieldOnField($event, index)"
                                             class="field-item bg-gray-50 dark:bg-gray-800 p-4 rounded-lg border border-gray-200 dark:border-gray-600 cursor-move">

                                            <div class="flex items-center justify-between">
                                                <div class="flex items-center space-x-3">
                                                    <div class="cursor-move">
                                                        <svg class="w-5 h-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <input type="text" x-model="field.name"
                                                               class="font-medium border-b border-gray-300 dark:border-gray-600 bg-transparent focus:border-blue-500 outline-none"
                                                               placeholder="Field Name">
                                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                                            <span x-text="field.type"></span>
                                                            <span x-show="field.required" class="text-red-500 ml-1">*</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <button @click="editField(index)"
                                                            class="text-blue-600 hover:text-blue-800">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                        </svg>
                                                    </button>
                                                    <button @click="removeField(index)"
                                                            class="text-red-600 hover:text-red-800">
                                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                        </svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Field Configuration -->
                                            <div x-show="field.showConfig" class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-600">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                                                        <input type="text" x-model="field.label"
                                                               class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-700">
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                                                        <input type="text" x-model="field.placeholder"
                                                               class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-700">
                                                    </div>
                                                </div>
                                                <div class="mt-3 flex items-center space-x-4">
                                                    <label class="flex items-center">
                                                        <input type="checkbox" x-model="field.required" class="rounded border-gray-300 text-blue-600">
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Required</span>
                                                    </label>
                                                    <label class="flex items-center">
                                                        <input type="checkbox" x-model="field.nullable" class="rounded border-gray-300 text-blue-600">
                                                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nullable</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Mode -->
            <div x-show="showPreview" class="h-full overflow-auto p-8">
                <div class="max-w-4xl mx-auto">
                    <div class="bg-white dark:bg-gray-900 rounded-lg shadow-lg p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Preview</h3>

                        <!-- Preview Form -->
                        <form class="space-y-6">
                            <template x-for="field in fields" :key="field.id">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        <span x-text="field.label || field.name"></span>
                                        <span x-show="field.required" class="text-red-500">*</span>
                                    </label>

                                    <!-- Different field types preview -->
                                    <div x-show="field.type === 'text' || field.type === 'email' || field.type === 'password'">
                                        <input :type="field.type || 'text'"
                                               :placeholder="field.placeholder"
                                               :required="field.required"
                                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                    </div>

                                    <div x-show="field.type === 'textarea'">
                                        <textarea :placeholder="field.placeholder"
                                                  :required="field.required"
                                                  rows="3"
                                                  class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800"></textarea>
                                    </div>

                                    <div x-show="field.type === 'select'">
                                        <select :required="field.required"
                                                class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                            <option value="">Select an option</option>
                                            <option value="option1">Option 1</option>
                                            <option value="option2">Option 2</option>
                                        </select>
                                    </div>

                                    <div x-show="field.type === 'checkbox'">
                                        <div class="mt-2">
                                            <label class="flex items-center">
                                                <input type="checkbox" class="rounded border-gray-300 text-blue-600">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Check this option</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="field.type === 'date'">
                                        <input type="date"
                                               :required="field.required"
                                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                    </div>

                                    <div x-show="field.type === 'file'">
                                        <input type="file"
                                               :required="field.required"
                                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                    </div>
                                </div>
                            </template>

                            <div class="pt-4">
                                <button type="submit"
                                        class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - Properties Panel -->
        <div class="w-80 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 p-4 overflow-y-auto">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-4">Properties</h3>

            <!-- Panel Properties -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3">Panel Settings</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Navigation Group</label>
                        <input type="text" x-model="panelData.navigationGroup"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Navigation Sort</label>
                        <input type="number" x-model="panelData.navigationSort"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Icon</label>
                        <input type="text" x-model="panelData.icon"
                               placeholder="heroicon-o-cube"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800">
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" x-model="panelData.isActive" class="rounded border-gray-300 text-blue-600">
                        <label class="ml-2 text-xs text-gray-700 dark:text-gray-300">Active</label>
                    </div>
                </div>
            </div>

            <!-- Table Configuration -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3">Table Configuration</h4>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Table Name</label>
                        <input type="text" x-model="tableConfig.name"
                               class="w-full border border-gray-300 dark:border-gray-600 rounded px-2 py-1 text-sm bg-white dark:bg-gray-800">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Default Columns</label>
                        <div class="space-y-1">
                            <template x-for="(column, index) in tableConfig.columns" :key="index">
                                <div class="flex items-center space-x-2">
                                    <input type="checkbox" x-model="column.enabled" class="rounded border-gray-300 text-blue-600">
                                    <span class="text-xs text-gray-700 dark:text-gray-300" x-text="column.name"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Generated Code Preview -->
            <div class="mb-6">
                <h4 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-3">Generated Code</h4>
                <div class="bg-gray-900 text-gray-100 p-3 rounded text-xs font-mono overflow-x-auto">
                    <pre x-text="generateCodePreview()"></pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Field Edit Modal -->
    <div x-show="showFieldModal"
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.away="showFieldModal = false">
        <div class="bg-white dark:bg-gray-900 rounded-lg p-6 w-full max-w-md">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Edit Field</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Field Name</label>
                    <input type="text" x-model="editingField.name"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Field Type</label>
                    <select x-model="editingField.type"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                        <option value="text">Text</option>
                        <option value="textarea">Textarea</option>
                        <option value="number">Number</option>
                        <option value="email">Email</option>
                        <option value="password">Password</option>
                        <option value="select">Select</option>
                        <option value="checkbox">Checkbox</option>
                        <option value="radio">Radio</option>
                        <option value="toggle">Toggle</option>
                        <option value="date">Date</option>
                        <option value="datetime">Date Time</option>
                        <option value="time">Time</option>
                        <option value="file">File</option>
                        <option value="image">Image</option>
                        <option value="richeditor">Rich Editor</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                    <input type="text" x-model="editingField.label"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                    <input type="text" x-model="editingField.placeholder"
                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                </div>

                <div class="flex items-center space-x-4">
                    <label class="flex items-center">
                        <input type="checkbox" x-model="editingField.required" class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Required</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" x-model="editingField.nullable" class="rounded border-gray-300 text-blue-600">
                        <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nullable</span>
                    </label>
                </div>
            </div>

            <div class="mt-6 flex justify-end space-x-3">
                <button @click="showFieldModal = false"
                        class="px-4 py-2 text-sm border border-gray-300 rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    Cancel
                </button>
                <button @click="saveFieldEdit()"
                        class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function visualPanelBuilder() {
    return {
        showPreview: false,
        currentPanel: '',
        showFieldModal: false,
        editingField: null,
        editingFieldIndex: null,

        panelData: {
            name: '',
            modelName: '',
            description: '',
            navigationGroup: '',
            navigationSort: 0,
            icon: 'heroicon-o-cube',
            isActive: true
        },

        fields: [],

        tableConfig: {
            name: 'default',
            columns: [
                { name: 'id', enabled: true },
                { name: 'name', enabled: true },
                { name: 'created_at', enabled: false },
                { name: 'updated_at', enabled: false }
            ]
        },

        draggedFieldType: null,
        draggedFieldIndex: null,

        init() {
            // Initialize with empty panel
            this.resetPanel();
        },

        resetPanel() {
            this.panelData = {
                name: '',
                modelName: '',
                description: '',
                navigationGroup: '',
                navigationSort: 0,
                icon: 'heroicon-o-cube',
                isActive: true
            };
            this.fields = [];
        },

        togglePreview() {
            this.showPreview = !this.showPreview;
        },

        startDrag(event, fieldType) {
            this.draggedFieldType = fieldType;
            event.dataTransfer.effectAllowed = 'copy';
        },

        startDragField(event, index) {
            this.draggedFieldIndex = index;
            event.dataTransfer.effectAllowed = 'move';
        },

        dragOver(event) {
            event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
        },

        dragLeave(event) {
            event.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');
        },

        dropField(event) {
            event.currentTarget.classList.remove('border-blue-500', 'bg-blue-50');

            if (this.draggedFieldType) {
                this.addField(this.draggedFieldType);
                this.draggedFieldType = null;
            }
        },

        dragOverField(event, index) {
            // Handle field reordering
        },

        dropFieldOnField(event, index) {
            // Handle field reordering
        },

        addField(type) {
            const field = {
                id: Date.now(),
                type: type,
                name: this.generateFieldName(type),
                label: this.generateFieldLabel(type),
                placeholder: '',
                required: false,
                nullable: false,
                showConfig: false
            };

            this.fields.push(field);
        },

        generateFieldName(type) {
            const baseNames = {
                text: 'text',
                textarea: 'description',
                number: 'number',
                email: 'email',
                password: 'password',
                select: 'option',
                checkbox: 'checked',
                radio: 'choice',
                toggle: 'enabled',
                date: 'date',
                datetime: 'datetime',
                time: 'time',
                file: 'file',
                image: 'image',
                richeditor: 'content'
            };

            let baseName = baseNames[type] || 'field';
            let counter = 1;
            let fieldName = baseName;

            while (this.fields.some(f => f.name === fieldName)) {
                fieldName = `${baseName}_${counter}`;
                counter++;
            }

            return fieldName;
        },

        generateFieldLabel(type) {
            const labels = {
                text: 'Text',
                textarea: 'Description',
                number: 'Number',
                email: 'Email',
                password: 'Password',
                select: 'Option',
                checkbox: 'Checked',
                radio: 'Choice',
                toggle: 'Enabled',
                date: 'Date',
                datetime: 'Date Time',
                time: 'Time',
                file: 'File',
                image: 'Image',
                richeditor: 'Content'
            };

            return labels[type] || 'Field';
        },

        editField(index) {
            this.editingField = { ...this.fields[index] };
            this.editingFieldIndex = index;
            this.showFieldModal = true;
        },

        saveFieldEdit() {
            if (this.editingFieldIndex !== null) {
                this.fields[this.editingFieldIndex] = { ...this.editingField };
            }
            this.showFieldModal = false;
            this.editingField = null;
            this.editingFieldIndex = null;
        },

        removeField(index) {
            this.fields.splice(index, 1);
        },

        savePanel() {
            // Save panel data via Livewire
            this.$wire.call('saveVisualPanel', {
                panelData: this.panelData,
                fields: this.fields,
                tableConfig: this.tableConfig
            });
        },

        generateCode() {
            // Generate code via Livewire
            this.$wire.call('generateVisualPanelCode', {
                panelData: this.panelData,
                fields: this.fields,
                tableConfig: this.tableConfig
            });
        },

        generateCodePreview() {
            if (this.fields.length === 0) return '// No fields defined';

            let code = `// Generated Form Schema\nForm::make()->schema([\n`;

            this.fields.forEach(field => {
                const filamentType = this.getFilamentFieldType(field.type);
                code += `    ${filamentType}::make('${field.name}')\n`;
                if (field.label) code += `        ->label('${field.label}')\n`;
                if (field.required) code += `        ->required()\n`;
                code += `        ->placeholder('${field.placeholder || ''}'),\n`;
            });

            code += `]);\n\n// Generated Table Schema\nTable::make()->columns([\n`;

            this.fields.forEach(field => {
                const columnType = this.getFilamentColumnType(field.type);
                code += `    ${columnType}::make('${field.name}')\n`;
                if (field.label) code += `        ->label('${field.label}')\n`;
                code += `        ->searchable(),\n`;
            });

            code += `]);`;

            return code;
        },

        getFilamentFieldType(type) {
            const types = {
                text: 'TextInput',
                textarea: 'Textarea',
                number: 'TextInput',
                email: 'TextInput',
                password: 'TextInput',
                select: 'Select',
                checkbox: 'Checkbox',
                radio: 'Radio',
                toggle: 'Toggle',
                date: 'DatePicker',
                datetime: 'DateTimePicker',
                time: 'TimePicker',
                file: 'FileUpload',
                image: 'FileUpload',
                richeditor: 'RichEditor'
            };

            return types[type] || 'TextInput';
        },

        getFilamentColumnType(type) {
            const types = {
                text: 'TextColumn',
                textarea: 'TextColumn',
                number: 'TextColumn',
                email: 'TextColumn',
                password: 'TextColumn',
                select: 'TextColumn',
                checkbox: 'IconColumn',
                radio: 'TextColumn',
                toggle: 'IconColumn',
                date: 'DateColumn',
                datetime: 'DateTimeColumn',
                time: 'TimeColumn',
                file: 'TextColumn',
                image: 'ImageColumn',
                richeditor: 'TextColumn'
            };

            return types[type] || 'TextColumn';
        },

        createNewPanel() {
            this.resetPanel();
            this.currentPanel = '';
        },

        loadPanel() {
            if (this.currentPanel) {
                // Load panel data via Livewire
                this.$wire.call('loadVisualPanel', this.currentPanel)
                    .then(result => {
                        this.panelData = result.panelData;
                        this.fields = result.fields;
                        this.tableConfig = result.tableConfig;
                    });
            }
        }
    };
}
</script>
