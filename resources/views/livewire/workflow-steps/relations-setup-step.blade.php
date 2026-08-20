<div>
    <div class="space-y-6">
        <!-- Relations Management -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Model Relations
                </h3>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ count($workflowData['relations_setup']['relations'] ?? []) }} relations
                </span>
            </div>

            <!-- Existing Relations -->
            @if (!empty($workflowData['relations_setup']['relations']))
                <div class="space-y-3 mb-6">
                    @foreach ($workflowData['relations_setup']['relations'] as $index => $relation)
                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h4 class="font-medium text-gray-900 dark:text-white">
                                        {{ $relation['method_name'] ?? 'relation' }}()
                                    </h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $relation['type'] }} • {{ $relation['related_model'] }}
                                    </p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button wire:click="moveRelationUp({{ $index }})"
                                            @if ($index == 0) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-up class="w-5 h-5" />
                                    </button>
                                    <button wire:click="moveRelationDown({{ $index }})"
                                            @if ($index == count($workflowData['relations_setup']['relations']) - 1) disabled @endif
                                            class="p-1 text-gray-400 hover:text-gray-600 disabled:opacity-50 disabled:cursor-not-allowed">
                                        <x-heroicon-o-chevron-down class="w-5 h-5" />
                                    </button>
                                    <button wire:click="removeRelation({{ $index }})"
                                            class="p-1 text-red-400 hover:text-red-600">
                                        <x-heroicon-o-trash class="w-5 h-5" />
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Relation Type:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $relation['type'] }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-500 dark:text-gray-400">Related Model:</span>
                                    <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $relation['related_model'] }}</span>
                                </div>
                                @if (!empty($relation['foreign_key']))
                                    <div>
                                        <span class="text-gray-500 dark:text-gray-400">Foreign Key:</span>
                                        <span class="ml-2 font-medium text-gray-900 dark:text-white">{{ $relation['foreign_key'] }}</span>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-2 bg-gray-100 dark:bg-gray-900 rounded p-2 font-mono text-sm">
                                <span class="text-gray-600 dark:text-gray-400">// Generated code:</span>
                                <span class="text-gray-900 dark:text-white">


                                </span>
                            </div>
                    @endforeach
                </div>
            @else
                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 text-center mb-6">
                    <x-heroicon-o-link class="w-12 h-12 text-gray-400 mx-auto mb-3" />
                    <p class="text-gray-600 dark:text-gray-400">
                        No relations defined yet. Add your first relation below.
                    </p>
                </div>
            @endif

            <!-- Add New Relation -->
            <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <h4 class="font-medium text-gray-900 dark:text-white mb-4">
                    Add New Relation
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Relation Type *
                        </label>
                        <select wire:model.live="newRelation.type"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @foreach ($availableRelationTypes as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Related Model *
                        </label>
                        <select wire:model.live="newRelation.related_model"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            <option value="">Select a model...</option>
                            @foreach ($availableModels as $model)
                                <option value="{{ $model }}">{{ $model }}</option>
                            @endforeach
                        </select>
                        @error('newRelation.related_model')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Method Name
                        </label>
                        <input type="text"
                               wire:model="newRelation.method_name"
                               class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                               placeholder="e.g., user, posts, comments">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Auto-generated based on relation type and model name
                        </p>
                    </div>

                    @if ($newRelation['type'] === 'belongsTo')
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Foreign Key
                            </label>
                            <input type="text"
                                   wire:model="newRelation.foreign_key"
                                   class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="e.g., user_id">
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                Foreign key column name (for belongsTo relations)
                            </p>
                        </div>
                    @endif
                </div>

                <button wire:click="addRelation"
                        class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <x-heroicon-o-plus class="w-5 h-5 inline-block mr-2" />
                    Add Relation
                </button>

                @error('relation_method')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Relation Types Guide -->
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-4">
                Relation Types Guide
            </h4>
            <div class="space-y-3 text-sm">
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <strong>Belongs To:</strong> Many records belong to one record (e.g., Post belongs to User)
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <strong>Has Many:</strong> One record has many records (e.g., User has many Posts)
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <strong>Has One:</strong> One record has one record (e.g., User has one Profile)
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <strong>Belongs To Many:</strong> Many records belong to many records (e.g., Post belongs to many Tags)
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <strong>Morph Many:</strong> Many records can belong to this record (e.g., Post has many Comments)
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-2 h-2 bg-blue-500 rounded-full mt-2"></div>
                    <div>
                        <strong>Morph One:</strong> One record can belong to this record (e.g., Post has one Featured Image)
                    </div>
                </div>
            </div>
        </div>

        <!-- Relations Preview -->
        @if (!empty($workflowData['relations_setup']['relations']))
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                    Relations Preview
                </h4>
                <div class="bg-gray-800 text-gray-100 rounded-lg p-4 font-mono text-sm overflow-x-auto">
                    <pre>class {{ $workflowData['model_design']['model_name'] ?? 'Model' }} extends Model
{
@foreach ($workflowData['relations_setup']['relations'] as $relation)
    public function {{ $relation['method_name'] ?? 'relation' }}()
    {

@endforeach}</pre>
                </div>
            </div>
        @endif
    </div>
</div>
