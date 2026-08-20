<x-filament::section>
<div>
    <div class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
        <div class="px-4 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Workflow Panel Manager</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Create and manage panels using a step-by-step workflow</p>
                </div>
                <div class="flex space-x-2">
                        <button wire:click="startWorkflow('create')"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <x-heroicon-o-plus class="fi-icon fi-size-sm inline-block mr-2" />
                            New Panel Workflow
                        </button>
                </div>
            </div>
        </div>
    </div>

</div>
</x-filament::section>
