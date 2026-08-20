<div x-data="fieldConfigurator()" x-init="init()">
    <!-- Field Types Sidebar -->
    <div class="w-80 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 h-full overflow-y-auto">
        <div class="p-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Field Types</h3>

            <!-- Search -->
            <div class="mb-4">
                <input type="text" x-model="searchTerm" @input="filterFieldTypes()"
                       placeholder="Search field types..."
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-sm bg-white dark:bg-gray-700">
            </div>

            <!-- Field Type Categories -->
            <div class="space-y-6">
                <!-- Basic Fields -->
                <div>
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Basic</h4>
                    <div class="space-y-2">
                        <template x-for="fieldType in filteredFieldTypes.basic" :key="fieldType.type">
                            <div draggable="true"
                                 @dragstart="startDrag($event, fieldType)"
                                 class="field-type-item bg-gray-50 dark:bg-gray-700 p-3 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-center">
                                    <span class="text-xl mr-3" x-text="fieldType.icon"></span>
                                    <div>
                                        <div class="font-medium text-sm" x-text="fieldType.name"></div>
                                        <div class="text-xs text-gray-500" x-text="fieldType.description"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Choice Fields -->
                <div>
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Choice</h4>
                    <div class="space-y-2">
                        <template x-for="fieldType in filteredFieldTypes.choice" :key="fieldType.type">
                            <div draggable="true"
                                 @dragstart="startDrag($event, fieldType)"
                                 class="field-type-item bg-gray-50 dark:bg-gray-700 p-3 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-center">
                                    <span class="text-xl mr-3" x-text="fieldType.icon"></span>
                                    <div>
                                        <div class="font-medium text-sm" x-text="fieldType.name"></div>
                                        <div class="text-xs text-gray-500" x-text="fieldType.description"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Date/Time Fields -->
                <div>
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Date & Time</h4>
                    <div class="space-y-2">
                        <template x-for="fieldType in filteredFieldTypes.datetime" :key="fieldType.type">
                            <div draggable="true"
                                 @dragstart="startDrag($event, fieldType)"
                                 class="field-type-item bg-gray-50 dark:bg-gray-700 p-3 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-center">
                                    <span class="text-xl mr-3" x-text="fieldType.icon"></span>
                                    <div>
                                        <div class="font-medium text-sm" x-text="fieldType.name"></div>
                                        <div class="text-xs text-gray-500" x-text="fieldType.description"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Media Fields -->
                <div>
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Media</h4>
                    <div class="space-y-2">
                        <template x-for="fieldType in filteredFieldTypes.media" :key="fieldType.type">
                            <div draggable="true"
                                 @dragstart="startDrag($event, fieldType)"
                                 class="field-type-item bg-gray-50 dark:bg-gray-700 p-3 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-center">
                                    <span class="text-xl mr-3" x-text="fieldType.icon"></span>
                                    <div>
                                        <div class="font-medium text-sm" x-text="fieldType.name"></div>
                                        <div class="text-xs text-gray-500" x-text="fieldType.description"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Relation Fields -->
                <div>
                    <h4 class="text-sm font-medium text-gray-600 dark:text-gray-400 mb-2">Relations</h4>
                    <div class="space-y-2">
                        <template x-for="fieldType in filteredFieldTypes.relation" :key="fieldType.type">
                            <div draggable="true"
                                 @dragstart="startDrag($event, fieldType)"
                                 class="field-type-item bg-gray-50 dark:bg-gray-700 p-3 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-center">
                                    <span class="text-xl mr-3" x-text="fieldType.icon"></span>
                                    <div>
                                        <div class="font-medium text-sm" x-text="fieldType.name"></div>
                                        <div class="text-xs text-gray-500" x-text="fieldType.description"></div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Configuration Panel -->
    <div class="flex-1 bg-gray-50 dark:bg-gray-900">
        <!-- Field Configuration -->
        <div class="h-full flex flex-col">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 p-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Field Configuration</h3>
                    <div class="flex items-center space-x-2">
                        <button @click="previewMode = !previewMode"
                                :class="previewMode ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700'"
                                class="px-3 py-1 rounded-md text-sm transition-colors">
                            <span x-show="!previewMode">👁️ Preview</span>
                            <span x-show="previewMode">✏️ Edit</span>
                        </button>
                        <button @click="saveField()"
                                class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                            💾 Save
                        </button>
                    </div>
                </div>
            </div>

            <!-- Configuration Content -->
            <div class="flex-1 overflow-auto p-6">
                <!-- Edit Mode -->
                <div x-show="!previewMode" class="max-w-2xl mx-auto">
                    <template x-if="selectedFieldType">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                            <!-- Field Type Info -->
                            <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900 rounded-lg">
                                <div class="flex items-center">
                                    <span class="text-2xl mr-3" x-text="selectedFieldType.icon"></span>
                                    <div>
                                        <h4 class="font-semibold text-lg" x-text="selectedFieldType.name"></h4>
                                        <p class="text-sm text-gray-600 dark:text-gray-400" x-text="selectedFieldType.description"></p>
                                    </div>
                                </div>
                            </div>

                            <!-- Basic Configuration -->
                            <div class="space-y-4">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Field Name</label>
                                        <input type="text" x-model="fieldConfig.name"
                                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                        <p class="text-xs text-gray-500 mt-1">Database field name (snake_case)</p>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Label</label>
                                        <input type="text" x-model="fieldConfig.label"
                                               class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                        <p class="text-xs text-gray-500 mt-1">Display label for users</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Placeholder</label>
                                    <input type="text" x-model="fieldConfig.placeholder"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Help Text</label>
                                    <textarea x-model="fieldConfig.helpText" rows="2"
                                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700"></textarea>
                                </div>

                                <!-- Validation Options -->
                                <div class="border-t pt-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Validation</h4>
                                    <div class="space-y-3">
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.required" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Required</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.nullable" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Nullable (Database)</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.unique" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Unique</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Type-specific Configuration -->
                                <div class="border-t pt-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Type Options</h4>
                                    <div x-html="renderTypeSpecificConfig()"></div>
                                </div>

                                <!-- Advanced Options -->
                                <div class="border-t pt-4">
                                    <h4 class="font-medium text-gray-900 dark:text-white mb-3">Advanced</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Default Value</label>
                                            <input type="text" x-model="fieldConfig.defaultValue"
                                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Column Order</label>
                                            <input type="number" x-model="fieldConfig.order"
                                                   class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>

                    <div x-show="!selectedFieldType" class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No field selected</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Drag a field type from the sidebar to configure it.</p>
                    </div>
                </div>

                <!-- Preview Mode -->
                <div x-show="previewMode" class="max-w-2xl mx-auto">
                    <template x-if="selectedFieldType && fieldConfig.name">
                        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-lg p-6">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-4">Preview</h4>

                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        <span x-text="fieldConfig.label || fieldConfig.name"></span>
                                        <span x-show="fieldConfig.required" class="text-red-500">*</span>
                                    </label>

                                    <!-- Render different field types -->
                                    <div x-show="selectedFieldType.type === 'text'">
                                        <input type="text"
                                               :placeholder="fieldConfig.placeholder"
                                               :required="fieldConfig.required"
                                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                    </div>

                                    <div x-show="selectedFieldType.type === 'textarea'">
                                        <textarea :placeholder="fieldConfig.placeholder"
                                                  :required="fieldConfig.required"
                                                  rows="3"
                                                  class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800"></textarea>
                                    </div>

                                    <div x-show="selectedFieldType.type === 'select'">
                                        <select :required="fieldConfig.required"
                                                class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                            <option value="">Select an option</option>
                                            <template x-for="option in fieldConfig.options || ['Option 1', 'Option 2']" :key="option">
                                                <option :value="option" x-text="option"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div x-show="selectedFieldType.type === 'checkbox'">
                                        <div class="mt-2">
                                            <label class="flex items-center">
                                                <input type="checkbox" class="rounded border-gray-300 text-blue-600">
                                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Check this option</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div x-show="selectedFieldType.type === 'date'">
                                        <input type="date"
                                               :required="fieldConfig.required"
                                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                    </div>

                                    <div x-show="selectedFieldType.type === 'file'">
                                        <input type="file"
                                               :required="fieldConfig.required"
                                               class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800">
                                    </div>

                                    <div x-show="selectedFieldType.type === 'richeditor'">
                                        <div contenteditable="true"
                                             class="mt-1 block w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-800 min-h-[100px] focus:outline-none focus:ring-2 focus:ring-blue-500">
                                            Type something...
                                        </div>
                                    </div>
                                </div>

                                <div x-show="fieldConfig.helpText" class="text-sm text-gray-500 dark:text-gray-400">
                                    <span x-text="fieldConfig.helpText"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <script>
    function fieldConfigurator() {
        return {
            searchTerm: '',
            previewMode: false,
            selectedFieldType: null,
            fieldConfig: {
                name: '',
                label: '',
                placeholder: '',
                helpText: '',
                required: false,
                nullable: false,
                unique: false,
                defaultValue: '',
                order: 0,
            },

            fieldTypes: {
                basic: [
                    { type: 'text', name: 'Text Input', icon: '📝', description: 'Single line text input' },
                    { type: 'textarea', name: 'Textarea', icon: '📄', description: 'Multi-line text input' },
                    { type: 'number', name: 'Number', icon: '🔢', description: 'Numeric input' },
                    { type: 'email', name: 'Email', icon: '📧', description: 'Email address input' },
                    { type: 'password', name: 'Password', icon: '🔐', description: 'Password input' },
                    { type: 'url', name: 'URL', icon: '🔗', description: 'URL input' },
                    { type: 'tel', name: 'Phone', icon: '📱', description: 'Phone number input' },
                ],
                choice: [
                    { type: 'select', name: 'Select', icon: '📋', description: 'Dropdown selection' },
                    { type: 'checkbox', name: 'Checkbox', icon: '☑️', description: 'Single checkbox' },
                    { type: 'radio', name: 'Radio', icon: '🔘', description: 'Radio button group' },
                    { type: 'toggle', name: 'Toggle', icon: '🔄', description: 'On/off toggle' },
                    { type: 'multiselect', name: 'Multi Select', icon: '📝', description: 'Multiple selection' },
                ],
                datetime: [
                    { type: 'date', name: 'Date', icon: '📅', description: 'Date picker' },
                    { type: 'datetime', name: 'Date Time', icon: '📆', description: 'Date and time picker' },
                    { type: 'time', name: 'Time', icon: '⏰', description: 'Time picker' },
                    { type: 'datetime-local', name: 'Local DateTime', icon: '🕐', description: 'Local date and time' },
                ],
                media: [
                    { type: 'file', name: 'File Upload', icon: '📎', description: 'File upload' },
                    { type: 'image', name: 'Image', icon: '🖼️', description: 'Image upload' },
                    { type: 'richeditor', name: 'Rich Editor', icon: '📝', description: 'WYSIWYG editor' },
                    { type: 'markdown', name: 'Markdown', icon: '📄', description: 'Markdown editor' },
                ],
                relation: [
                    { type: 'belongsTo', name: 'Belongs To', icon: '🔗', description: 'Belongs to relationship' },
                    { type: 'hasMany', name: 'Has Many', icon: '📚', description: 'Has many relationship' },
                    { type: 'belongsToMany', name: 'Belongs to Many', icon: '🔀', description: 'Many to many relationship' },
                    { type: 'hasOne', name: 'Has One', icon: '1️⃣', description: 'Has one relationship' },
                ],
            },

            filteredFieldTypes: {},

            init() {
                this.filteredFieldTypes = { ...this.fieldTypes };
                this.filterFieldTypes();
            },

            filterFieldTypes() {
                const term = this.searchTerm.toLowerCase();

                if (!term) {
                    this.filteredFieldTypes = { ...this.fieldTypes };
                    return;
                }

                this.filteredFieldTypes = {};

                Object.keys(this.fieldTypes).forEach(category => {
                    this.filteredFieldTypes[category] = this.fieldTypes[category].filter(fieldType =>
                        fieldType.name.toLowerCase().includes(term) ||
                        fieldType.description.toLowerCase().includes(term) ||
                        fieldType.type.toLowerCase().includes(term)
                    );
                });
            },

            startDrag(event, fieldType) {
                this.selectedFieldType = fieldType;
                this.resetFieldConfig();

                // Set default values based on field type
                this.fieldConfig.name = this.generateFieldName(fieldType.type);
                this.fieldConfig.label = fieldType.name;

                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData('text/plain', JSON.stringify(fieldType));
            },

            resetFieldConfig() {
                this.fieldConfig = {
                    name: '',
                    label: '',
                    placeholder: '',
                    helpText: '',
                    required: false,
                    nullable: false,
                    unique: false,
                    defaultValue: '',
                    order: 0,
                };
            },

            generateFieldName(type) {
                const baseNames = {
                    text: 'text',
                    textarea: 'description',
                    number: 'number',
                    email: 'email',
                    password: 'password',
                    url: 'url',
                    tel: 'phone',
                    select: 'option',
                    checkbox: 'checked',
                    radio: 'choice',
                    toggle: 'enabled',
                    multiselect: 'options',
                    date: 'date',
                    datetime: 'datetime',
                    time: 'time',
                    'datetime-local': 'local_datetime',
                    file: 'file',
                    image: 'image',
                    richeditor: 'content',
                    markdown: 'markdown',
                    belongsTo: 'user_id',
                    hasMany: 'items',
                    belongsToMany: 'categories',
                    hasOne: 'profile',
                };

                return baseNames[type] || 'field';
            },

            renderTypeSpecificConfig() {
                if (!this.selectedFieldType) return '';

                switch (this.selectedFieldType.type) {
                    case 'text':
                        return `
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Length</label>
                                    <input type="number" x-model="fieldConfig.maxLength"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="fieldConfig.password" class="rounded border-gray-300 text-blue-600">
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Password field</label>
                                </div>
                            </div>
                        `;

                    case 'select':
                    case 'multiselect':
                        return `
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Options (one per line)</label>
                                    <textarea x-model="fieldConfig.optionsText" rows="4"
                                              class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700"
                                              @input="fieldConfig.options = $event.target.value.split('\\n').filter(o => o.trim())"></textarea>
                                </div>
                                <div x-show="selectedFieldType.type === 'multiselect'" class="flex items-center">
                                    <input type="checkbox" x-model="fieldConfig.multiple" class="rounded border-gray-300 text-blue-600">
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Allow multiple selection</label>
                                </div>
                            </div>
                        `;

                    case 'file':
                    case 'image':
                        return `
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Upload Directory</label>
                                    <input type="text" x-model="fieldConfig.directory"
                                           placeholder="uploads"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" x-model="fieldConfig.multiple" class="rounded border-gray-300 text-blue-600">
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Allow multiple files</label>
                                </div>
                                <div x-show="selectedFieldType.type === 'image'" class="flex items-center">
                                    <input type="checkbox" x-model="fieldConfig.image" class="rounded border-gray-300 text-blue-600">
                                    <label class="ml-2 text-sm text-gray-700 dark:text-gray-300">Image optimization</label>
                                </div>
                            </div>
                        `;

                    case 'richeditor':
                        return `
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Toolbar Buttons</label>
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.bold" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Bold</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.italic" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Italic</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.lists" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Lists</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="checkbox" x-model="fieldConfig.links" class="rounded border-gray-300 text-blue-600">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Links</span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        `;

                    case 'belongsTo':
                        return `
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Related Model</label>
                                    <select x-model="fieldConfig.relatedModel"
                                            class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                        <option value="">Select model</option>
                                        <option value="User">User</option>
                                        <option value="Category">Category</option>
                                        <option value="Post">Post</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Foreign Key</label>
                                    <input type="text" x-model="fieldConfig.foreignKey"
                                           class="w-full border border-gray-300 dark:border-gray-600 rounded-md px-3 py-2 bg-white dark:bg-gray-700">
                                </div>
                            </div>
                        `;

                    default:
                        return '<p class="text-sm text-gray-500">No additional options available for this field type.</p>';
                }
            },

            saveField() {
                if (!this.selectedFieldType || !this.fieldConfig.name) {
                    alert('Please configure the field name');
                    return;
                }

                // Emit event to parent component
                this.$wire.call('addConfiguredField', {
                    fieldType: this.selectedFieldType.type,
                    config: this.fieldConfig
                });

                // Reset for next field
                this.selectedFieldType = null;
                this.resetFieldConfig();
            }
        };
    }
    </script>
</div>
