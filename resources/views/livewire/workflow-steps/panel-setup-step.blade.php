<div>
    <div class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Panel Name *
                </label>
                <input type="text"
                       wire:model.live="workflowData.panel_setup.name"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="e.g., Blog Posts">
                @error('workflowData.panel_setup.name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Panel Slug *
                </label>
                <input type="text"
                       wire:model="workflowData.panel_setup.slug"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="e.g., blog-posts">
                @error('workflowData.panel_setup.slug')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Description -->
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Description
            </label>
            <textarea wire:model="workflowData.panel_setup.description"
                      rows="3"
                      class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                      placeholder="Describe what this panel is for..."></textarea>
            @error('workflowData.panel_setup.description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Navigation Settings -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Icon
                </label>
                <input type="text"
                       wire:model="workflowData.panel_setup.icon"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="e.g., heroicon-o-cube">
                @error('workflowData.panel_setup.icon')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Heroicon name for navigation
                </p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Navigation Group
                </label>
                <input type="text"
                       wire:model="workflowData.panel_setup.navigation_group"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="e.g., Content Management">
                @error('workflowData.panel_setup.navigation_group')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Navigation Sort
                </label>
                <input type="number"
                       wire:model="workflowData.panel_setup.navigation_sort"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white"
                       placeholder="0">
                @error('workflowData.panel_setup.navigation_sort')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Active Status -->
        <div>
            <label class="flex items-center space-x-3">
                <input type="checkbox"
                       wire:model="workflowData.panel_setup.is_active"
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    Active
                </span>
            </label>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Whether this panel should be visible in the navigation
            </p>
        </div>

        <!-- Preview Card -->
        <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-6 border border-gray-200 dark:border-gray-700">
            <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-4">
                Preview
            </h4>
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900 rounded-lg flex items-center justify-center">
                    <x-heroicon-o-cube class="w-5 h-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <h5 class="font-medium text-gray-900 dark:text-white">
                        {{ $workflowData['panel_setup']['name'] ?? 'Panel Name' }}
                    </h5>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        {{ $workflowData['panel_setup']['navigation_group'] ?? 'No group' }}
                    </p>
                </div>
                @if($workflowData['panel_setup']['is_active'] ?? true)
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                        Active
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200">
                        Inactive
                    </span>
                @endif
            </div>
        </div>
    </div>
</div>
