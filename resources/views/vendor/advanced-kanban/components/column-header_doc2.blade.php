{{-- This component is based on the Advanced Kanban package column header --}}
{{-- You can customize this component to match your design requirements --}}

@php use Filament\Support\Enums\IconSize;

    //dd($column);

@endphp
@props([
    'column' => null,
    'status' => null,
    'actions' => [],
])
<div>
    <div class="max-w-screen-xl mx-auto px-4 md:px-8">
        
    </div>

    <!--    <div class="flex space-x-4 bg-white p-1 rounded">
            <div x-data="{ isOpen: false }">
                <button
                    @mouseover="isOpen = true"
                    @mouseleave="isOpen = false"
                    class="text-gray-800 hover:bg-gray-200 font-bold p-2 rounded transition-colors duration-300">
                    <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                         fill="#000000">
                        <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                        <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                        <g id="SVGRepo_iconCarrier">
                            <g>
                                <path fill="none" d="M0 0h24v24H0z"></path>
                                <path
                                    d="M12 2c5.52 0 10 4.48 10 10s-4.48 10-10 10S2 17.52 2 12 6.48 2 12 2zM6.023 15.416C7.491 17.606 9.695 19 12.16 19c2.464 0 4.669-1.393 6.136-3.584A8.968 8.968 0 0 0 12.16 13a8.968 8.968 0 0 0-6.137 2.416zM12 11a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
                            </g>
                        </g>
                    </svg>
                </button>

                <div x-show="isOpen"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-95"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100 transform scale-100"
                     x-transition:leave-end="opacity-0 transform scale-95"
                     class="popover absolute bg-gray-700 border shadow-md mt-2 px-4 py-2 rounded-lg">

                    <p class="text-white">Account</p>
                </div>
            </div>


    <div x-data="{ isOpen: false }">
        <button
            @mouseover="isOpen = true"
            @mouseleave="isOpen = false"
            class="text-gray-800 hover:bg-gray-200 font-bold p-2 rounded transition-colors duration-300">
            <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                 fill="#000000">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                    <g>
                        <path fill="none" d="M0 0h24v24H0z"></path>
                        <path
                            d="M7.291 20.824L2 22l1.176-5.291A9.956 9.956 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10a9.956 9.956 0 0 1-4.709-1.176zM7 12a5 5 0 0 0 10 0h-2a3 3 0 0 1-6 0H7z"></path>
                    </g>
                </g>
            </svg>
        </button>

        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="popover absolute bg-gray-700 border shadow-md mt-2 px-4 py-2 rounded-lg">

            <p class="text-white">Chat</p>
        </div>
    </div>


    <div x-data="{ isOpen: true }">
        <button
            @mouseover="isOpen = true"
            @mouseleave="isOpen = false"
            class="text-gray-800 hover:bg-gray-200 font-bold p-2 rounded transition-colors duration-300">
            <svg width="1.5em" height="1.5em" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"
                 fill="#000000">
                <g id="SVGRepo_bgCarrier" stroke-width="0"></g>
                <g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g>
                <g id="SVGRepo_iconCarrier">
                    <g>
                        <path fill="none" d="M0 0h24v24H0z"></path>
                        <path
                            d="M5.334 4.545a9.99 9.99 0 0 1 3.542-2.048A3.993 3.993 0 0 0 12 3.999a3.993 3.993 0 0 0 3.124-1.502 9.99 9.99 0 0 1 3.542 2.048 3.993 3.993 0 0 0 .262 3.454 3.993 3.993 0 0 0 2.863 1.955 10.043 10.043 0 0 1 0 4.09c-1.16.178-2.23.86-2.863 1.955a3.993 3.993 0 0 0-.262 3.455 9.99 9.99 0 0 1-3.542 2.047A3.993 3.993 0 0 0 12 20a3.993 3.993 0 0 0-3.124 1.502 9.99 9.99 0 0 1-3.542-2.047 3.993 3.993 0 0 0-.262-3.455 3.993 3.993 0 0 0-2.863-1.954 10.043 10.043 0 0 1 0-4.091 3.993 3.993 0 0 0 2.863-1.955 3.993 3.993 0 0 0 .262-3.454zM13.5 14.597a3 3 0 1 0-3-5.196 3 3 0 0 0 3 5.196z"></path>
                    </g>
                </g>
            </svg>
        </button>

        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 transform scale-95"
             x-transition:enter-end="opacity-100 transform scale-100"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 transform scale-100"
             x-transition:leave-end="opacity-0 transform scale-95"
             class="popover absolute bg-gray-700 border shadow-md mt-2 px-4 py-2 rounded-lg">

            <p class="text-white">Settings</p>
        </div>
    </div>
</div>
<div x-data="{ isOpen: false }"
     data-side="bottom"
     data-align="center"
     data-state="open"
     role="dialog"
     id="radix-«r48»"
     data-slot="popover-content"
     class="leading-6"
     tabindex="-1"
     style="--radix-popover-content-transform-origin: var(--radix-popper-transform-origin); --radix-popover-content-available-width: var(--radix-popper-available-width); --radix-popover-content-available-height: var(--radix-popper-available-height); --radix-popover-trigger-width: var(--radix-popper-anchor-width); --radix-popover-trigger-height: var(--radix-popper-anchor-height); border-spacing: 0px;"
>
    <div class="" wire:click="() => isOpen = !isOpen" x-bind:aria-expanded="isOpen.toString()"
         style="border-spacing: 0px;">
        <a href="#" class="font-bold cursor-pointer" style="border-spacing: 0px;"
        >@creative-tim-ui</a
        ><span
            data-slot="chip"
            class="text-xs font-medium leading-4 whitespace-nowrap"
            style="border-spacing: 0px;"
        >Public</span
        >
    </div>
    <p class="text-sm leading-5" style="border-spacing: 0px;">
        Creative Tim UI is an easy-to-use components library for Tailwind CSS and
        shadcn/ui.
    </p>
    <div class="" style="border-spacing: 0px;">
        <div class="" style="border-spacing: 0px;">
            <span class="" style="border-spacing: 0px;"></span>
            <p class="text-xs leading-4" style="border-spacing: 0px;">TypeScript</p>
        </div>
        <div class="" style="border-spacing: 0px;">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class=""
                style="border-spacing: 0px;"
            >
                <path
                    d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"
                    class=""
                    style="border-spacing: 0px;"
                ></path>
            </svg>
            <p class="text-xs leading-4" style="border-spacing: 0px;">1,480</p>
        </div>
        <div class="" style="border-spacing: 0px;">
            <svg
                xmlns="http://www.w3.org/2000/svg"
                width="24"
                height="24"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                class=""
                style="border-spacing: 0px;"
            >
                <circle
                    cx="12"
                    cy="12"
                    r="10"
                    class=""
                    style="border-spacing: 0px;"
                ></circle>
                <path d="m9 12 2 2 4-4" class="" style="border-spacing: 0px;"></path>
            </svg>
            <p class="text-xs leading-4" style="border-spacing: 0px;">Verified</p>
        </div>
    </div>
</div>
-->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="kanban-column-label">

                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <x-filament::icon :icon="$column->getIcon()"
                                          color="{{$column->getIconColor()}}"
                                          :size="IconSize::Small"
                        />
                        <h3 class="kanban-column-title">
                            {{ $column->getLabel() ?? $column->getStatus() }}
                        </h3>
                        <x-filament::badge
                            color="primary"
                            size="sm"
                        >
                            {{ $this->getKanban()->getTotalCount($column->getStatus()) ?? 0 }}
                        </x-filament::badge>
                    </div>
                    <p class="kanban-column-description text-xs text-gray-500">
                        {{ $column->getDescription() }}
                    </p>
                </div>
            </div>

        </div>
        <div class="flex items-center gap-2">
            @foreach($actions as $action)
                {{ $action }}
            @endforeach
        </div>
    </div>
</div>
