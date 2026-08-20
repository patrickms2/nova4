<div>
    <div class="space-y-6">
        <!-- Code Generation Status -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                    Code Generation Preview
                </h3>
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                    @if ($reviewStatus === 'approved')
                        bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                    @elseif ($reviewStatus === 'rejected')
                        bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                    @else
                        bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                    @endif">
                    {{ ucfirst($reviewStatus) }}
                </span>
            </div>

            <p class="text-gray-600 dark:text-gray-400 mb-4">
                Review the generated code below. Once approved, the files will be created in your project.
            </p>

            <!-- Files to be Generated -->
            <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4 mb-6">
                <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                    Files to be Generated:
                </h4>
                <ul class="space-y-2">
                    @foreach ($generatedFiles as $file)
                        <li class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                            <x-heroicon-o-document class="w-4 h-4 mr-2 text-green-500" />
                            {{ $file }}
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Code Preview Tabs -->
            <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                <div class="bg-gray-50 dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                    <nav class="flex space-x-1 p-1">
                        <button wire:click="$set('activeTab', 'model')"
                                class="px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ $this->activeTab === 'model' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border border-gray-200 dark:border-gray-700' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                            Model
                        </button>
                        <button wire:click="$set('activeTab', 'migration')"
                                class="px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ $this->activeTab === 'migration' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border border-gray-200 dark:border-gray-700' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                            Migration
                        </button>
                        <button wire:click="$set('activeTab', 'resource')"
                                class="px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ $this->activeTab === 'resource' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border border-gray-200 dark:border-gray-700' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                            Resource
                        </button>
                        <button wire:click="$set('activeTab', 'factory')"
                                class="px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ $this->activeTab === 'factory' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border border-gray-200 dark:border-gray-700' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                            Factory
                        </button>
                        <button wire:click="$set('activeTab', 'seeder')"
                                class="px-3 py-2 text-sm font-medium rounded-md transition-colors
                                    {{ $this->activeTab === 'seeder' ? 'bg-white dark:bg-gray-800 text-blue-600 dark:text-blue-400 border border-gray-200 dark:border-gray-700' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                            Seeder
                        </button>
                    </nav>
                </div>

                <div class="bg-gray-900 text-gray-100 p-4 overflow-x-auto">
                    @switch($this->activeTab ?? 'model')
                        @case('model')
                            <pre class="text-sm font-mono whitespace-pre-wrap">{{ $codePreview['model'] }}</pre>
                            @break
                        @case('migration')
                            <pre class="text-sm font-mono whitespace-pre-wrap">{{ $codePreview['migration'] }}</pre>
                            @break
                        @case('resource')
                            <pre class="text-sm font-mono whitespace-pre-wrap">{{ $codePreview['resource'] }}</pre>
                            @break
                        @case('factory')
                            <pre class="text-sm font-mono whitespace-pre-wrap">{{ $codePreview['factory'] }}</pre>
                            @break
                        @case('seeder')
                            <pre class="text-sm font-mono whitespace-pre-wrap">{{ $codePreview['seeder'] }}</pre>
                            @break
                        @default
                            <pre class="text-sm font-mono whitespace-pre-wrap">{{ $codePreview['model'] }}</pre>
                    @endswitch
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    <p>Review the code carefully before approving. Once approved, files will be created.</p>
                </div>

                <div class="flex space-x-3">
                    <button wire:click="rejectCode"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        <x-heroicon-o-x-mark class="w-5 h-5 inline-block mr-2" />
                        Reject & Edit
                    </button>
                    <button wire:click="approveCode"
                            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                        <x-heroicon-o-check class="w-5 h-5 inline-block mr-2" />
                        Approve Code
                    </button>
                </div>
            </div>
        </div>

        <!-- Generation Summary -->
        <div class="bg-blue-50 dark:bg-blue-900 rounded-lg p-6 border border-blue-200 dark:border-blue-700">
            <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100 mb-4">
                Generation Summary
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-cube class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Model</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $workflowData['model_design']['model_name'] ?? 'Model' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-green-100 dark:bg-green-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-table-cells class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Table</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $workflowData['model_design']['table_name'] ?? 'table' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-cog-6-tooth class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Resource</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $workflowData['model_design']['model_name'] ?? 'Model' }}Resource
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-document-text class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Fields</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ count($workflowData['model_design']['fields'] ?? []) }} defined
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-link class="w-6 h-6 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Relations</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ count($workflowData['relations_setup']['relations'] ?? []) }} defined
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-lg p-4">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 bg-pink-100 dark:bg-pink-900 rounded-lg flex items-center justify-center">
                            <x-heroicon-o-table-cells class="w-6 h-6 text-pink-600 dark:text-pink-400" />
                        </div>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Form Fields</h5>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ count($workflowData['resource_config']['form_fields'] ?? []) }} defined
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Next Steps -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                Next Steps After Approval
            </h4>
            <div class="space-y-3 text-sm text-gray-600 dark:text-gray-400">
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-green-600 dark:text-green-400 text-xs font-bold">1</span>
                    </div>
                    <div>
                        <strong>Run Migration:</strong> Execute <code class="bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded text-xs">php artisan migrate</code> to create the database table
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-green-600 dark:text-green-400 text-xs font-bold">2</span>
                    </div>
                    <div>
                        <strong>Clear Cache:</strong> Run <code class="bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded text-xs">php artisan config:clear</code> and <code class="bg-gray-200 dark:bg-gray-800 px-2 py-1 rounded text-xs">php artisan route:clear</code>
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-green-600 dark:text-green-400 text-xs font-bold">3</span>
                    </div>
                    <div>
                        <strong>Test Resource:</strong> Navigate to your Filament admin panel and test the new resource
                    </div>
                </div>
                <div class="flex items-start space-x-3">
                    <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-green-600 dark:text-green-400 text-xs font-bold">4</span>
                    </div>
                    <div>
                        <strong>Optional:</strong> Run factory seeder to populate sample data
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
