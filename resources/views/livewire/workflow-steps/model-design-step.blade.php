<div>
    <div class="space-y-6">
        <!-- Model Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Model Name *
                </label>
                <input type="text"
                       wire:model.live="workflowData.model_design.model_name"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="e.g., BlogPost">
                @error('workflowData.model_design.model_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Table Name *
                </label>
                <input type="text"
                       wire:model="workflowData.model_design.table_name"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="e.g., blog_posts">
                @error('workflowData.model_design.table_name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Fields Management -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Model Fields
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($workflowData['model_design']['fields'] ?? []) }} fields
                </span>
            </div>

            <!-- Existing Fields -->
            @if (!empty($workflowData['model_design']['fields']))
                <div class="space-y-3 mb-6">
                    @foreach ($workflowData['model_design']['fields'] as $index => $field)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $field['name'] }}
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $field['label'] }} • {{ $field['type'] }} • {{ $field['filament_type'] }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button wire:click="moveFieldUp({{ $index }})"
                                            @if ($index == 0) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-up class="w-5 h-5" />
                                    </button>
                                    <button wire:click="moveFieldDown({{ $index }})"
                                            @if ($index == count($workflowData['model_design']['fields']) - 1) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-down class="w-5 h-5" />
                                    </button>
                                    <button wire:click="removeField({{ $index }})"
                                            class="p-1 text-red-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Database Type:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $field['type'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Form Field:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $field['filament_type'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Table Column:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $field['column_type'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Nullable:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">
                                        {{ $field['nullable'] ? 'Yes' : 'No' }}
                                    </span>
                                </div>
                            </div>

                            @if (!empty($field['default']))
                                <div class="mt-2">
                                    <span class="text-gray-500 dark:text-gray-400">Default:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $field['default'] }}</span>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 text-center mb-6">
                    <x-heroicon-o-document-text class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p class="text-gray-600 dark:text-gray-400">
                        No fields defined yet. Add your first field below.
                    </p>
                </div>
            @endif

            <!-- Add New Field -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">
                    Add New Field
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Field Name *
                        </label>
                        <input type="text"
                               wire:model="newField.name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., title">
                        @error('newField.name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Field Label *
                        </label>
                        <input type="text"
                               wire:model="newField.label"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., Title">
                        @error('newField.label')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Database Type *
                        </label>
                        <select wire:model="newField.type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach ($availableFieldTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Form Field Type *
                        </label>
                        <select wire:model="newField.filament_type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach ($availableFilamentFieldTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Table Column Type *
                        </label>
                        <select wire:model="newField.column_type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach ($availableColumnTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Default Value
                        </label>
                        <input type="text"
                               wire:model="newField.default"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="Leave empty for no default">
                    </div>

                    <div>
                        <label class="flex items-center space-x-3 mt-6">
                            <input type="checkbox"
                                   wire:model="newField.nullable"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Nullable
                            </span>
                        </label>
                    </div>
                </div>

                <button wire:click="addField"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <x-heroicon-o-plus class="w-5 h-5 inline-block mr-2" />
                    Add Field
                </button>

                @error('field_name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Model Preview -->
        @if (!empty($workflowData['model_design']['model_name']))
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    Model Preview
                </h4>
                <div class="bg-gray-800 text-gray-100 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                    <pre>class {{ $workflowData['model_design']['model_name'] }} extends Model
{
    protected $fillable = [
@foreach ($workflowData['model_design']['fields'] as $field)
        '{{ $field['name'] }}',
@endforeach
    ];
}</pre>
                </div>
            </div>
        @endif
    </div>
</div>
