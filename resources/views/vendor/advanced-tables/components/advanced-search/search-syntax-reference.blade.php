@php
    use Archilex\AdvancedTables\Support\Config;
    use Filament\Support\Enums\Platform;
    use Illuminate\Support\Arr;
    use Illuminate\Support\Stringable;

    $columnsEnabled = Config::advancedSearchColumnsAreEnabled();
    $groupingEnabled = Config::advancedSearchGroupingIsEnabled();
    $booleanEnabled = Config::advancedSearchBooleanSyntaxIsEnabled();
    $keybindings = Config::getAdvancedSearchKeybindings();
    $keybindingLabel = null;

    if ($keybindings) {
        $platform = Platform::detect();

        if ($platform !== Platform::Other) {
            $keybindingLabel = (string) str(Arr::first($keybindings))
                ->when(
                    $platform === Platform::Mac,
                    fn (Stringable $string) => $string
                        ->replace('command', '⌘')
                        ->replace('mod', '⌘')
                        ->replace('ctrl', '⌃')
                        ->replace('alt', '⌥')
                        ->replace('option', '⌥'),
                    fn (Stringable $string) => $string
                        ->replace('command', 'meta')
                        ->replace('mod', 'ctrl')
                        ->replace('option', 'alt')
                )
                ->replace('shift', '⇧')
                ->replace('+', '')
                ->upper();

            $keybindingHtml = preg_replace(
                '/([A-Z0-9]+)/u',
                '<span class="font-mono">$1</span>',
                e($keybindingLabel)
            );
        }
    }
@endphp

<div class="space-y-4 text-sm p-2">
    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
        {{ __('advanced-tables::advanced-tables.advanced_search.search_reference.heading') }}
    </h2>

    {{-- Navigation keyboard shortcuts --}}
    <div class="space-y-1">
        <h3 class="text-sm font-medium text-gray-400 dark:text-gray-500">
            {{ __('advanced-tables::advanced-tables.advanced_search.search_reference.navigation') }}
        </h3>

        @if ($keybindingLabel)
            <div class="flex items-center justify-between">
                <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.focus_search') }}</span>
                <div>
                    <span class="text-info-600 dark:text-info-400">{!! $keybindingHtml !!}</span>
                </div>
            </div>
        @endif

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.open_dropdown') }}</span>
            <div>
                <span class="text-info-600 dark:text-info-400">↓</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.navigate_dropdown') }}</span>
            <div>
                <span class="text-info-600 dark:text-info-400">↑ ↓</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.select_tag') }}</span>
            <div>
                <span class="text-info-600 dark:text-info-400">↵</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.remove_tag') }}</span>
            <div>
                <span class="text-info-600 dark:text-info-400">⌫</span>
            </div>
        </div>
    </div>

    {{-- Constraint syntax reference --}}
    <div class="space-y-1">
        <h3 class="text-sm font-medium text-gray-400 dark:text-gray-500">
            {{ __('advanced-tables::advanced-tables.advanced_search.search_reference.constraints') }}
        </h3>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.contains') }}</span>
            <span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.equals') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">=</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.starts_with') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">^</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.ends_with') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">$</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.does_not_contain') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">-</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.does_not_equal') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">-=</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.does_not_start_with') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">-^</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.constraints.does_not_end_with') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">-$</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
            </div>
        </div>

        <div class="flex items-center justify-between">
            <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.exact_phrase') }}</span>
            <div>
                <span class="font-mono text-info-600 dark:text-info-400">"</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search_words') }}</span><span class="font-mono text-info-600 dark:text-info-400">"</span>
            </div>
        </div>
    </div>

    {{-- Column syntax reference --}}
    @if ($columnsEnabled)
        <div class="space-y-1">
            <h3 class="text-sm font-medium text-gray-400 dark:text-gray-500">
                {{ __('advanced-tables::advanced-tables.advanced_search.search_reference.columns') }}
            </h3>

            <div class="flex items-center justify-between">
                <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.single') }}</span>
                <div>
                    <span class="font-mono text-info-600 dark:text-info-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.column_example_column') }}:</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.multiple') }}</span>
                <div>
                    <span class="font-mono text-info-600 dark:text-info-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.column_example_columns') }}:</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.combined') }}</span>
                <div>
                    <span class="font-mono text-info-600 dark:text-info-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.column_example_columns') }}:-^</span><span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span>
                </div>
            </div>
        </div>
    @endif

    {{-- Boolean operator syntax reference --}}
    @if ($groupingEnabled)
        <div class="space-y-1">
            <h3 class="text-sm font-medium text-gray-400 dark:text-gray-500">
                {{ __('advanced-tables::advanced-tables.advanced_search.search_reference.boolean') }}
            </h3>

            <div class="flex items-center justify-between">
                <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.and_operator') }}</span>
                @if ($booleanEnabled)
                    <div>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span><span class="font-mono text-info-600 dark:text-info-400">&</span>
                    </div>
                @endif
            </div>

            <div class="flex items-center justify-between">
                <span class="text-gray-950 dark:text-white">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.or_operator') }}</span>
                @if ($booleanEnabled)
                    <div>
                        <span class="font-mono text-gray-500 dark:text-gray-400">{{ __('advanced-tables::advanced-tables.advanced_search.search_reference.search') }}</span><span class="font-mono text-info-600 dark:text-info-400">|</span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
