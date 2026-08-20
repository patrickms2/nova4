@once
    <link rel="stylesheet" href="{{ asset('vendor/filament-notification-bell/notification-bell.css') }}?v=20260323-3">
@endonce

<div
    x-data="{ open: @entangle('isOpen').live, theme: '{{ $theme }}' }"
    :class="theme === 'dark' ? 'dark' : (theme === 'light' ? '' : '')"
    @if($rtl || in_array(app()->getLocale(), config('filament-notification-bell.rtl_locales', []), true)) dir="rtl" @endif
    class="fnb-root fi-topbar-item relative {{ ($rtl || in_array(app()->getLocale(), config('filament-notification-bell.rtl_locales', []), true)) ? 'text-right' : 'text-left' }}"
    @if(isset($polling) && $polling) wire:poll.{{ $pollInterval }}s="refresh" @endif
>
    @if($panelMode === 'slideOver')
        <button
            type="button"
            x-on:click="open = ! open"
            class="fnb-trigger fi-topbar-item-btn"
            aria-label="{{ __('filament-notification-bell::notification-bell.bell_label') }}"
            title="{{ __('filament-notification-bell::notification-bell.open_panel') }}"
        >
            @if($unreadCount > 0)
                <x-heroicon-s-bell class="fi-icon fi-size-lg fnb-bell-icon text-warning-500 dark:text-warning-400" />
            @else
                <x-heroicon-o-bell class="fi-icon fi-size-lg fnb-bell-icon text-gray-500 dark:text-gray-400" />
            @endif

            @if($unreadCount > 0)
                <span class="fnb-badge">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
            @endif
        </button>

        <div
            x-cloak
            x-show="open"
            class="fnb-overlay"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-on:click="open = false"
            aria-hidden="true"
        ></div>

        <aside
            x-cloak
            x-show="open"
            class="fnb-slide"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-x-4"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-4"
        >
            @include('filament-notification-bell::livewire.partials.panel-content', [
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'canLoadMore' => $canLoadMore,
                'viewAllUrl' => $viewAllUrl,
            ])
        </aside>
    @else
        <button
            type="button"
            class="fnb-trigger fi-topbar-item-btn"
            x-on:click="open = ! open"
            aria-label="{{ __('filament-notification-bell::notification-bell.bell_label') }}"
        >
            @if($unreadCount > 0)
                <x-heroicon-s-bell class="fi-icon fi-size-lg fnb-bell-icon text-warning-500 dark:text-warning-400" />
            @else
                <x-heroicon-o-bell class="fi-icon fi-size-lg fnb-bell-icon text-gray-500 dark:text-gray-400" />
            @endif

            @if($unreadCount > 0)
                <span
                    class="fnb-badge"
                    aria-label="{{ trans_choice('filament-notification-bell::notification-bell.unread_plural', $unreadCount, ['count' => $unreadCount]) }}"
                >
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <div
            x-cloak
            x-show="open"
            class="fixed inset-0 z-40"
            x-on:click="open = false"
            aria-hidden="true"
        ></div>

        <div
            x-cloak
            x-show="open"
            class="fnb-dropdown-fixed"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
        >
            @include('filament-notification-bell::livewire.partials.panel-content', [
                'notifications' => $notifications,
                'unreadCount' => $unreadCount,
                'canLoadMore' => $canLoadMore,
                'viewAllUrl' => $viewAllUrl,
            ])
        </div>
    @endif
</div>
