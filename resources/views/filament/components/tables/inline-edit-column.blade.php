@php
    use Filament\Support\Enums\Alignment;

    $isDisabled = $isDisabled();
    $state = $getState();
    $mask = $getMask();

    $alignment = $getAlignment() ?? Alignment::Start;

    if (! $alignment instanceof Alignment) {
        $alignment = filled($alignment) ? (Alignment::tryFrom($alignment) ?? $alignment) : null;
    }

    if (filled($mask)) {
        $type = 'text';
    } else {
        $type = $getType();
    }
@endphp

<div
    x-data="{
        error: undefined,
        isEditing: false,
        isLoading: false,
        name: @js($getName()),
        recordKey: @js($getRecordKey()),
        state: @js($state),
        originalState: @js($state),

        save() {
            this.isLoading = true

            $wire.updateTableColumnState(
                this.name,
                String(this.recordKey),
                this.state,
            ).then(response => {
                this.error = response?.error ?? undefined

                if (! this.error) {
                    this.state = response
                    this.originalState = response
                    this.isEditing = false
                }

                this.isLoading = false
            })
        },

        cancel() {
            this.state = this.originalState
            this.isEditing = false
            this.error = undefined
        }
    }"
    x-init="
        () => {
            Livewire.hook('commit', ({ component, commit, succeed, fail, respond }) => {
                succeed(({ snapshot, effect }) => {
                    $nextTick(() => {
                        if (component.id !== @js($this->getId())) {
                            return
                        }

                        if (isEditing) {
                            return
                        }

                        if (! $refs.newState) {
                            return
                        }

                        let newState = $refs.newState.value.replaceAll('\\'+String.fromCharCode(34), String.fromCharCode(34))

                        if (state === newState) {
                            return
                        }

                        state = newState
                        originalState = newState
                    })
                })
            })
        }
    "
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class([
                'fi-ta-text-input w-full min-w-48 relative',
                'px-3 py-4' => ! $isInline(),
            ])
    }}
>
    <input
        type="hidden"
        value="{{ str($state)->replace('"', '\\"') }}"
        x-ref="newState"
    />

    <!-- Display text when not editing -->
    <div
        x-show="!isEditing"
        @class([
            'cursor-pointer min-h-[1.5rem]', // Added min-height for empty states
            match ($alignment) {
                Alignment::Start => 'text-start',
                Alignment::Center => 'text-center',
                Alignment::End => 'text-end',
                Alignment::Left => 'text-left',
                Alignment::Right => 'text-right',
                Alignment::Justify, Alignment::Between => 'text-justify',
                default => $alignment,
            },
        ])
        x-on:click="isEditing = !{{ $isDisabled ? 'false' : 'isLoading' }}"
        x-tooltip="{
            content: 'Click to edit',
            theme: $store.theme
        }"
    >
        {{ $state ?: '—' }} <!-- Show placeholder dash when empty -->
    </div>

    <!-- Input field and action buttons when editing -->
    <div x-show="isEditing" class="flex items-center space-x-2">
        <x-filament::input.wrapper
            :alpine-disabled="'isLoading || ' . \Illuminate\Support\Js::from($isDisabled)"
            alpine-valid="error === undefined"
            x-tooltip="
                error === undefined
                    ? false
                    : {
                        content: error,
                        theme: $store.theme,
                    }
            "
            class="flex-1"
        >
            {{-- format-ignore-start --}}
            <x-filament::input
                :disabled="$isDisabled"
                :input-mode="$getInputMode()"
                :placeholder="$getPlaceholder()"
                :step="$getStep()"
                :type="$type"
                :x-bind:disabled="$isDisabled ? null : 'isLoading'"
                x-model="state"
                :attributes="
                    \Filament\Support\prepare_inherited_attributes(
                        $getExtraInputAttributeBag()
                            ->merge([
                                'x-mask' . ($mask instanceof \Filament\Support\RawJs ? ':dynamic' : '') => filled($mask) ? $mask : null,
                            ])
                            ->class([
                                match ($alignment) {
                                    Alignment::Start => 'text-start',
                                    Alignment::Center => 'text-center',
                                    Alignment::End => 'text-end',
                                    Alignment::Left => 'text-left',
                                    Alignment::Right => 'text-right',
                                    Alignment::Justify, Alignment::Between => 'text-justify',
                                    default => $alignment,
                                },
                            ])
                    )
                "
            />
            {{-- format-ignore-end --}}
        </x-filament::input.wrapper>

        <!-- Save button -->
        <button
            type="button"
            x-on:click="save()"
            x-bind:disabled="isLoading"
            class="text-success-600 hover:text-success-500 disabled:opacity-70"
            title="Save"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
            </svg>
        </button>

        <!-- Cancel button -->
        <button
            type="button"
            x-on:click="cancel()"
            x-bind:disabled="isLoading"
            class="text-danger-600 hover:text-danger-500 disabled:opacity-70"
            title="Cancel"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>
</div>
