@props([
    'title' => '¿Qué quieres conseguir hoy?',
    'eyebrow' => 'Centro de mando',
    'placeholder' => 'Describe un objetivo. Nova lo planificará antes de ejecutar cualquier acción.',
    'model' => 'prompt',
    'action' => 'submitPrompt',
    'submitLabel' => 'Ejecutar',
    'loadingLabel' => 'Planificando…',
    'suggestions' => [],
    'voice' => false,
    'upload' => false,
    'submitOnEnter' => false,
])

<section
    x-data="{
        focused: false,
        isTyping: false,
        typingInterval: null,
        model: @js($model),
        typewriter(text) {
            if (this.isTyping) {
                clearInterval(this.typingInterval);
            }

            this.isTyping = true;
            const input = this.$refs.input;
            input.value = '';

            let i = 0;
            this.typingInterval = setInterval(() => {
                if (i < text.length) {
                    input.value += text.charAt(i);
                    i++;
                } else {
                    clearInterval(this.typingInterval);
                    this.isTyping = false;
                    this.$wire.set(this.model, text);
                }
            }, 25);
        },
    }"
    x-on:nova-typewriter.window="typewriter($event.detail)"
    {{ $attributes->class('grid gap-6') }}
>
    <x-nova.section-heading :eyebrow="$eyebrow" :title="$title" />

    <form wire:submit="{{ $action }}" class="grid gap-4">
        <div
            class="rounded-2xl border border-neutral-800 bg-neutral-900 p-2 shadow-sm transition-colors"
            :class="focused ? 'border-neutral-700' : ''"
        >
            <label for="nova-command-input" class="sr-only">{{ $title }}</label>
            <textarea
                id="nova-command-input"
                x-ref="input"
                wire:model="{{ $model }}"
                rows="3"
                placeholder="{{ $placeholder }}"
                x-on:focus="focused = true"
                x-on:blur="focused = false"
                x-on:keydown.meta.enter.prevent="$wire.{{ $action }}()"
                x-on:keydown.ctrl.enter.prevent="$wire.{{ $action }}()"
                @if ($submitOnEnter)
                    x-on:keydown.enter.exact.prevent="$wire.{{ $action }}()"
                @endif
                class="min-h-24 w-full resize-none border-0 bg-transparent px-3 py-3 text-[15px] leading-7 text-white placeholder:text-neutral-500 focus:ring-0"
            ></textarea>

            <div class="flex items-center justify-between gap-4 px-3 pb-2">
                <div class="flex items-center gap-2">
                    @if ($voice)
                        <button
                            type="button"
                            x-on:click="$dispatch('nova-voice')"
                            class="rounded-2xl bg-neutral-800 px-3 py-2 text-xs font-semibold text-neutral-400 transition-colors hover:text-white"
                        >
                            Voz
                        </button>
                    @endif

                    @if ($upload)
                        <button
                            type="button"
                            x-on:click="$dispatch('nova-upload')"
                            class="rounded-2xl bg-neutral-800 px-3 py-2 text-xs font-semibold text-neutral-400 transition-colors hover:text-white"
                        >
                            Adjuntar
                        </button>
                    @endif

                    {{ $slot }}
                </div>

                <div class="flex items-center gap-4">
                    <span class="hidden text-xs text-neutral-500 sm:inline">⌘ + Enter</span>
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="{{ $action }}"
                        class="rounded-2xl bg-orange-500 px-4 py-2 text-sm font-semibold text-black shadow-sm transition-colors hover:bg-orange-400 disabled:cursor-wait disabled:opacity-60"
                    >
                        <span wire:loading.remove wire:target="{{ $action }}">{{ $submitLabel }}</span>
                        <span wire:loading wire:target="{{ $action }}">{{ $loadingLabel }}</span>
                    </button>
                </div>
            </div>
        </div>

        @if (count($suggestions))
            <x-nova.quick-actions :actions="$suggestions" :model="$model" />
        @endif
    </form>
</section>
