@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $isMultiple = $isMultiple();
    $acceptedFileTypes = $getAcceptedFileTypes();
    $placeholder = $getPlaceholder();
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    label-tag="div"
>
    <div
        {{
            $attributes
                ->merge([
                    'id' => $getId(),
                    'role' => 'group',
                ], escape: false)
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-fo-file-upload'])
        }}
    >
        <input
            {{
                $getExtraInputAttributeBag()
                    ->merge([
                        'accept' => filled($acceptedFileTypes) ? implode(',', $acceptedFileTypes) : null,
                        'disabled' => $isDisabled,
                        'multiple' => $isMultiple,
                        'type' => 'file',
                        $applyStateBindingModifiers('wire:model') => $statePath,
                    ], escape: false)
                    ->class([
                        'fi-input block w-full cursor-pointer rounded-xl border border-white/10 bg-white/5 px-4 py-4 text-sm text-gray file:mr-4 file:rounded-lg file:border-0 file:bg-red-600 file:px-4 file:py-2 file:text-sm file:font-medium file:text-gray hover:file:bg-red-500',
                    ])
            }}
        />

        @if (filled($placeholder))
            <p class="mt-2 text-sm text-gray-400">
                {{ $placeholder }}
            </p>
        @endif
    </div>
</x-dynamic-component>
