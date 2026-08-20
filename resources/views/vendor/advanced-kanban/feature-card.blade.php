@php
    use Filament\Support\Enums\IconSize;

    $iconColors = match ($color) {
        'primary' => 'text-primary-600 dark:text-primary-400',
        'success' => 'text-success-600 dark:text-success-400',
        'info' => 'text-info-600 dark:text-info-400',
        'warning' => 'text-warning-600 dark:text-warning-400',
        'danger' => 'text-danger-600 dark:text-danger-400',
        'purple' => 'text-purple-600 dark:text-purple-400',
        'indigo' => 'text-indigo-600 dark:text-indigo-400',
        'teal' => 'text-teal-600 dark:text-teal-400',
        'cyan' => 'text-cyan-600 dark:text-cyan-400',
        'rose' => 'text-rose-600 dark:text-rose-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'gray' => 'text-gray-600 dark:text-gray-400',
        default => 'text-primary-600 dark:text-primary-400',
    };
@endphp

@props([
    'icon',
    'title',
    'description',
    'color' => 'primary',
])

<div class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4 transition-all duration-150 hover:border-gray-300 dark:hover:border-gray-600 hover:shadow-sm">
    <div class="flex items-start gap-3">
        <x-filament::icon :icon="$icon" :size="IconSize::Small" class="h-5 w-5 flex-shrink-0 mt-0.5 {{ $iconColors }}" />
        <div class="flex-1 min-w-0">
            <h3 class="text-sm font-medium text-gray-900 dark:text-white mb-1">{{ $title }}</h3>
            <p class="text-xs text-gray-600 dark:text-gray-400 leading-relaxed">{{ $description }}</p>
        </div>
    </div>
</div>

