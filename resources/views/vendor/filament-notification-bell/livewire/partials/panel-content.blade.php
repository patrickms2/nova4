@php
    $rtlActive = in_array(app()->getLocale(), config('filament-notification-bell.rtl_locales', []), true)
        || (bool) config('filament-notification-bell.force_rtl', false);

    $notifications = $notifications ?? collect();
    $unreadCount = (int) ($unreadCount ?? 0);
    $canLoadMore = (bool) ($canLoadMore ?? false);
    $viewAllUrl = $viewAllUrl ?? url('/notifications');
@endphp

<div class="fnb-panel {{ $rtlActive ? 'text-right' : 'text-left' }}">
    <header class="fnb-header">
        <div class="fnb-header-main">
            <h3 class="fnb-title">{{ __('filament-notification-bell::notification-bell.title') }}</h3>
            <p class="fnb-subtitle">
                {{ $unreadCount > 0
                    ? trans_choice('filament-notification-bell::notification-bell.unread_count', $unreadCount, ['count' => $unreadCount])
                    : __('filament-notification-bell::notification-bell.no_notifications') }}
            </p>
        </div>

        @if($unreadCount > 0)
            <button type="button" wire:click="markAllRead" wire:loading.attr="disabled" wire:target="markAllRead" class="fnb-mark-all">
                <span wire:loading.remove wire:target="markAllRead">{{ __('filament-notification-bell::notification-bell.mark_all_read') }}</span>
                <span wire:loading wire:target="markAllRead" class="fnb-loading-inline">
                    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle>
                        <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                    </svg>
                    {{ __('filament-notification-bell::notification-bell.mark_all_read') }}
                </span>
            </button>
        @endif
    </header>

    <section class="fnb-list" wire:loading.class="opacity-70" wire:target="loadMore,markRead,markAllRead">
        @forelse($notifications as $notification)
            @php
                $isUnread = $notification->read_at === null;
                $data = $notification->data ?? [];
                $title = $data['title'] ?? class_basename($notification->type);
                $body = $data['body'] ?? '';
                $url = $data['url'] ?? null;
                $type = $data['type'] ?? 'info';

                $typeClass = match ($type) {
                    'success' => 'fnb-type-success',
                    'warning' => 'fnb-type-warning',
                    'error', 'danger' => 'fnb-type-danger',
                    default => 'fnb-type-info',
                };

                $typeIcon = match ($type) {
                    'success' => 'heroicon-o-check-circle',
                    'warning' => 'heroicon-o-exclamation-triangle',
                    'error', 'danger' => 'heroicon-o-x-circle',
                    default => 'heroicon-o-information-circle',
                };

                $typeLabel = match ($type) {
                    'success' => __('filament-notification-bell::notification-bell.type_success'),
                    'warning' => __('filament-notification-bell::notification-bell.type_warning'),
                    'error', 'danger' => __('filament-notification-bell::notification-bell.type_error'),
                    default => __('filament-notification-bell::notification-bell.type_info'),
                };

                $timestamp = config('filament-notification-bell.date_format') === 'datetime'
                    ? $notification->created_at?->toDateTimeString()
                    : ($notification->created_at?->diffForHumans() ?? __('filament-notification-bell::notification-bell.just_now'));
            @endphp

            <button
                type="button"
                wire:click="markRead('{{ $notification->id }}')"
                @if($url) x-on:click="window.location.href='{{ $url }}'" @endif
                class="fnb-item {{ $isUnread ? 'fnb-item-unread' : 'fnb-item-read' }}"
            >
                @if($isUnread)
                    <span class="fnb-unread-dot" aria-hidden="true"></span>
                @endif

                <div class="fnb-item-icon {{ $typeClass }}">
                    <x-dynamic-component :component="$typeIcon" width="14" height="14" aria-hidden="true" />
                </div>

                <div class="fnb-item-content">
                    <div class="fnb-item-top">
                        <p class="fnb-item-title">{{ $title }}</p>
                        <p class="fnb-item-time">{{ $timestamp }}</p>
                    </div>

                    @if(filled($body))
                        <p class="fnb-item-body">{{ $body }}</p>
                    @endif

                    <span class="fnb-item-tag {{ $typeClass }}">{{ $typeLabel }}</span>
                </div>
            </button>
        @empty
            <div class="fnb-empty">
                <x-heroicon-o-bell-slash width="24" height="24" class="text-gray-400 dark:text-gray-500" />
                <p class="fnb-empty-title">{{ __('filament-notification-bell::notification-bell.no_notifications') }}</p>
                <p class="fnb-empty-sub">{{ __('filament-notification-bell::notification-bell.no_notifications_sub') }}</p>
            </div>
        @endforelse
    </section>

    <footer class="fnb-footer">
        <button
            type="button"
            wire:click.stop.prevent="loadMore"
            wire:loading.attr="disabled"
            wire:target="loadMore"
            @disabled(! $canLoadMore)
            class="fnb-footer-btn"
        >
            <span wire:loading.remove wire:target="loadMore">{{ __('filament-notification-bell::notification-bell.load_more') }}</span>
            <span wire:loading wire:target="loadMore" class="fnb-loading-inline">
                <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" class="opacity-30"></circle>
                    <path d="M22 12a10 10 0 0 0-10-10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
                </svg>
                {{ __('filament-notification-bell::notification-bell.load_more') }}
            </span>
        </button>

        <a href="{{ $viewAllUrl }}" x-on:click.stop class="fnb-footer-link">
            {{ __('filament-notification-bell::notification-bell.view_all') }}
        </a>
    </footer>
</div>
