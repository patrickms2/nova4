<div>
    <div class="space-y-6">
        <!-- Auto-Generate Button -->
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">
                        Auto-Generate from Model Fields
                    </h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Generate form fields and table columns automatically from your model design
                    </p>
                </div>
                <button wire:click="autoGenerateFromModelFields"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <x-heroicon-o-cog-6-tooth class="fi-icon fi-size-sm inline-block mr-2" />
                    Auto-Generate
                </button>
            </div>
        </div>

        <!-- Form Fields Configuration -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Form Fields Configuration
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($workflowData['resource_config']['form_fields'] ?? []) }} fields
                </span>
            </div>

            <!-- Existing Form Fields -->
            @if (!empty($workflowData['resource_config']['form_fields']))
                <div class="space-y-3 mb-6">
                    @foreach ($workflowData['resource_config']['form_fields'] as $index => $field)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $field['name'] }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $field['label'] }} • {{ $field['type'] }}
                                        @if ($field['required'] ?? false) • Required @endif
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button wire:click="moveFormFieldUp({{ $index }})"
                                            @if ($index == 0) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-up class="fi-icon fi-size-sm" />
                                    </button>
                                    <button wire:click="moveFormFieldDown({{ $index }})"
                                            @if ($index == count($workflowData['resource_config']['form_fields']) - 1) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-down class="fi-icon fi-size-sm" />
                                    </button>
                                    <button wire:click="removeFormField({{ $index }})"
                                            class="p-1 text-red-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="fi-icon fi-size-sm" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Field Type:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $field['type'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Required:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                        {{ $field['required'] ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                @if (!empty($field['validation']))
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Validation:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                            {{ implode(', ', $field['validation']) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 text-center mb-6">
                    <x-heroicon-o-document-text class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p class="text-gray-600 dark:text-gray-400">
                        No form fields defined yet. Add fields manually or use auto-generate.
                    </p>
                </div>
            @endif

            <!-- Add New Form Field -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">
                    Add New Form Field
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Field Name *
                        </label>
                        <input type="text"
                               wire:model="newFormField.name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., title">
                        @error('newFormField.name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Field Label *
                        </label>
                        <input type="text"
                               wire:model="newFormField.label"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., Title">
                        @error('newFormField.label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Field Type *
                        </label>
                        <select wire:model="newFormField.type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach ($availableFormFieldTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center space-x-3 mt-6">
                            <input type="checkbox"
                                   wire:model="newFormField.required"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Required
                            </span>
                        </label>
                    </div>
                </div>

                <button wire:click="addFormField"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <x-heroicon-o-plus class="fi-icon fi-size-sm inline-block mr-2" />
                    Add Form Field
                </button>

                @error('form_field_name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Table Columns Configuration -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Table Columns Configuration
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($workflowData['resource_config']['table_columns'] ?? []) }} columns
                </span>
            </div>

            <!-- Existing Table Columns -->
            @if (!empty($workflowData['resource_config']['table_columns']))
                <div class="space-y-3 mb-6">
                    @foreach ($workflowData['resource_config']['table_columns'] as $index => $column)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $column['name'] }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $column['label'] }} • {{ $column['type'] }}
                                        @if ($column['searchable'] ?? false) • Searchable @endif
                                        @if ($column['sortable'] ?? false) • Sortable @endif
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button wire:click="moveTableColumnUp({{ $index }})"
                                            @if ($index == 0) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-up class="fi-icon fi-size-sm" />
                                    </button>
                                    <button wire:click="moveTableColumnDown({{ $index }})"
                                            @if ($index == count($workflowData['resource_config']['table_columns']) - 1) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-down class="fi-icon fi-size-sm" />
                                    </button>
                                    <button wire:click="removeTableColumn({{ $index }})"
                                            class="p-1 text-red-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="fi-icon fi-size-sm" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Column Type:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $column['type'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Searchable:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                        {{ $column['searchable'] ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Sortable:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                        {{ $column['sortable'] ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 text-center mb-6">
                    <x-heroicon-o-table-cells class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p class="text-gray-600 dark:text-gray-400">
                        No table columns defined yet. Add columns manually or use auto-generate.
                    </p>
                </div>
            @endif

            <!-- Add New Table Column -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">
                    Add New Table Column
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Column Name *
                        </label>
                        <input type="text"
                               wire:model="newTableColumn.name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., title">
                        @error('newTableColumn.name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Column Label *
                        </label>
                        <input type="text"
                               wire:model="newTableColumn.label"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., Title">
                        @error('newTableColumn.label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Column Type *
                        </label>
                        <select wire:model="newTableColumn.type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach ($availableTableColumnTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="flex items-center space-x-3 mt-6">
                            <input type="checkbox"
                                   wire:model="newTableColumn.searchable"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Searchable
                            </span>
                        </label>
                    </div>

                    <div>
                        <label class="flex items-center space-x-3 mt-6">
                            <input type="checkbox"
                                   wire:model="newTableColumn.sortable"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Sortable
                            </span>
                        </label>
                    </div>
                </div>

                <button wire:click="addTableColumn"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <x-heroicon-o-plus class="fi-icon fi-size-sm inline-block mr-2" />
                    Add Table Column
                </button>

                @error('table_column_name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Resource Preview -->
        @if (!empty($workflowData['resource_config']['form_fields']) || !empty($workflowData['resource_config']['table_columns']))
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    Resource Preview
                </h4>
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @if (!empty($workflowData['resource_config']['form_fields']))
                        <div>
                            <h5 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Form Schema</h5>
                            <div class="bg-gray-800 text-gray-100 rounded-lg p-3 font-mono text-xs overflow-x-auto">
                                <pre>->schema([
@foreach ($workflowData['resource_config']['form_fields'] as $field)
    Forms\Components\{{ $field['type'] }}::make('{{ $field['name'] }}')
        ->label('{{ $field['label'] }}')@if ($field['required'])
        ->required()@endif,
@endforeach
])</pre>
                            </div>
                        </div>
                    @endif

                    @if (!empty($workflowData['resource_config']['table_columns']))
                        <div>
                            <h5 class="text-xs font-medium text-gray-600 dark:text-gray-400 mb-2">Table Columns</h5>
                            <div class="bg-gray-800 text-gray-100 rounded-lg p-3 font-mono text-xs overflow-x-auto">
                                <pre>->columns([
@foreach ($workflowData['resource_config']['table_columns'] as $column)
    Tables\Columns\{{ $column['type'] }}::make('{{ $column['name'] }}')
        ->label('{{ $column['label'] }}')@if ($column['searchable'])
        ->searchable()@endif@if ($column['sortable'])
        ->sortable()@endif,
@endforeach
])</pre>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>
</div>
