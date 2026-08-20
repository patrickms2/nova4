@props([
    'code' => '',
    'language' => 'json',
    'maxHeight' => '300px',
    'copyable' => true,
])

@php
    $formattedCode = is_array($code) || is_object($code)
        ? json_encode($code, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : $code;
@endphp

<div
    x-data="{
        copied: false,
        copyCode() {
            navigator.clipboard.writeText(this.$refs.code.textContent);
            this.copied = true;
            setTimeout(() => this.copied = false, 2000);
        }
    }"
    {{ $attributes->merge(['class' => 'mcp-code-block group relative']) }}
>
    @if($copyable)
        <button
            x-on:click="copyCode"
            type="button"
            class="absolute right-2 top-2 z-10 flex h-8 w-8 items-center justify-center rounded-lg bg-gray-700/50 text-gray-400 opacity-0 backdrop-blur transition-all hover:bg-gray-600/50 hover:text-white group-hover:opacity-100"
            title="Copy to clipboard"
        >
            <template x-if="!copied">
                          <x-filament::icon-button icon="heroicon-m-clipboard" label="{{ __('Copy') }}" onclick="copyToken(event)" class="h-4 w-4" />

            </template>
            <template x-if="copied">
                          <x-filament::icon-button icon="heroicon-m-check" class="h-4 w-4 text-green-400" />

            </template>
        </button>
    @endif

    <pre
        class="overflow-auto rounded-lg bg-gray-900 p-4 text-sm text-gray-300 scrollbar-thin scrollbar-track-gray-800 scrollbar-thumb-gray-600"
        style="max-height: {{ $maxHeight }};"
    ><code x-ref="code" class="language-{{ $language }}">{{ $formattedCode }}</code></pre>
</div>
