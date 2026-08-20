@props([
    'name',
    'label' => null,
    'value' => '',
    'language' => 'php',
    'height' => '300px',
    'placeholder' => '',
    'helperText' => null,
    'required' => false,
])

<div {{ $attributes->merge(['class' => 'code-editor-wrapper']) }}>
    @if($label)
        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div
        x-data="{
            value: @js($value),
            init() {
                this.$watch('value', value => {
                    this.$dispatch('input', value);
                    $wire.set('{{ $name }}', value);
                });
            }
        }"
        class="relative"
    >
        <div class="overflow-hidden rounded-lg border border-gray-300 dark:border-gray-700">
            <div class="flex items-center justify-between bg-gray-100 px-4 py-2 dark:bg-gray-800">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ strtoupper($language) }}</span>
                <div class="flex gap-2">
                    <button
                        type="button"
                        x-on:click="navigator.clipboard.writeText(value); $dispatch('notify', {message: 'Copied to clipboard'})"
                        class="rounded p-1 text-gray-400 hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                        title="Copy code"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                        </svg>
                    </button>
                </div>
            </div>
            <textarea
                x-model="value"
                name="{{ $name }}"
                placeholder="{{ $placeholder }}"
                class="block w-full resize-none border-0 bg-gray-900 p-4 font-mono text-sm text-gray-100 placeholder-gray-500 focus:ring-0"
                style="height: {{ $height }}; tab-size: 4;"
                spellcheck="false"
            ></textarea>
        </div>
    </div>

    @if($helperText)
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $helperText }}</p>
    @endif
</div>
