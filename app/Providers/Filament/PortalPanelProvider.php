<?php

namespace App\Providers\Filament;

use Adultdate\FilamentBooking\FilamentBookingPlugin;
use App\Filament\App\Resources\ActivitylogResource;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Filament\Portal\Pages\Auth\Login;
use App\Filament\Portal\Pages\Dashboard;
use App\Filament\Portal\Pages\Support;
use App\Filament\Portal\Pages\TaxistaChats;
use App\Filament\Portal\Pages\TaxistaDocuments;
use App\Filament\Portal\Pages\TaxistaPortal;
use App\Filament\Portal\Pages\TaxistaTracking;
use App\Http\Middleware\HandleSpaCsrfToken;
use App\Livewire\PortalDatabaseNotifications;
use App\Support\ActivityAccess;
use Asmit\AdvancedKanban\KanbanBuilder;
use Filament\Actions\Action;
use Filament\Enums\ThemeMode;
use Filament\Enums\UserMenuPosition;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PortalPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        $isPortalAuthRoute = static fn (): bool => Filament::getCurrentPanel()?->getId() === 'portal'
            && request()->routeIs('filament.portal.auth.*');

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_START,
            function (): string {
                if (Filament::getCurrentPanel()?->getId() !== 'portal' || ! request()->is('portal/login')) {
                    return '';
                }

                return <<<'HTML'
                            <script>
                                localStorage.setItem('theme', 'dark');
                                document.documentElement.classList.add('dark');
                            </script>
                        HTML;
            },
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            function () use ($isPortalAuthRoute): string {
                if (Filament::getCurrentPanel()?->getId() !== 'portal' || $isPortalAuthRoute()) {
                    return '';
                }

                return view('portal.hooks.background')->render();
            },
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            function () use ($isPortalAuthRoute): string {
                if (Filament::getCurrentPanel()?->getId() !== 'portal' || $isPortalAuthRoute()) {
                    return '';
                }

                return Blade::render(<<<'BLADE'
                            <!-- PWA Manifest -->
                            <link rel="manifest" href="{{ asset('manifest.json') }}">
                            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
                            <meta name="theme-color" content="#dc2626">
                            <meta name="mobile-web-app-capable" content="yes">
                            <meta name="apple-mobile-web-app-capable" content="yes">
                            <meta name="apple-mobile-web-app-status-bar-style" content="black">
                            <meta name="apple-mobile-web-app-title" content="Portal Taxista">
                            <link rel="apple-touch-icon" href="{{ asset('icon-192.png') }}">

                            <script>
                                (() => {
                                    const syncViewportMetrics = () => {
                                        const viewport = window.visualViewport;
                                        const height = viewport?.height ?? window.innerHeight;
                                        const offsetTop = viewport?.offsetTop ?? 0;
                                        const keyboardInset = Math.max(window.innerHeight - height - offsetTop, 0);

                                        document.documentElement.style.setProperty('--portal-app-height', `${height}px`);
                                        document.documentElement.style.setProperty('--portal-keyboard-inset', `${keyboardInset}px`);
                                    };

                                    syncViewportMetrics();

                                    window.addEventListener('resize', syncViewportMetrics, { passive: true });
                                    window.addEventListener('orientationchange', syncViewportMetrics, { passive: true });
                                    window.visualViewport?.addEventListener('resize', syncViewportMetrics, { passive: true });
                                    window.visualViewport?.addEventListener('scroll', syncViewportMetrics, { passive: true });

                                    const shouldBlockViewportZoom = () => {
                                        return window.matchMedia('(display-mode: standalone)').matches || window.innerWidth <= 1024;
                                    };

                                    let lastTouchEndAt = 0;

                                    const preventViewportZoom = (event) => {
                                        if (! shouldBlockViewportZoom()) {
                                            return;
                                        }

                                        event.preventDefault();
                                    };

                                    document.addEventListener('gesturestart', preventViewportZoom, { passive: false });
                                    document.addEventListener('gesturechange', preventViewportZoom, { passive: false });
                                    document.addEventListener('gestureend', preventViewportZoom, { passive: false });

                                    document.addEventListener('wheel', (event) => {
                                        if (! shouldBlockViewportZoom() || ! event.ctrlKey) {
                                            return;
                                        }

                                        event.preventDefault();
                                    }, { passive: false });

                                    document.addEventListener('touchmove', (event) => {
                                        if (! shouldBlockViewportZoom() || event.scale === undefined || event.scale === 1) {
                                            return;
                                        }

                                        event.preventDefault();
                                    }, { passive: false });

                                    document.addEventListener('touchend', (event) => {
                                        if (! shouldBlockViewportZoom()) {
                                            return;
                                        }

                                        const now = Date.now();

                                        if (now - lastTouchEndAt <= 300) {
                                            event.preventDefault();
                                        }

                                        lastTouchEndAt = now;
                                    }, { passive: false });
                                })();
                            </script>

                            <!-- Service Worker Cleanup -->
                            <script>
                                if ('serviceWorker' in navigator) {
                                    window.addEventListener('load', function() {
                                        navigator.serviceWorker.getRegistrations().then(function(registrations) {
                                            registrations.forEach(function(registration) {
                                                const activeUrl = registration.active?.scriptURL ?? '';
                                                const waitingUrl = registration.waiting?.scriptURL ?? '';
                                                const installingUrl = registration.installing?.scriptURL ?? '';
                                                const scriptUrl = activeUrl || waitingUrl || installingUrl;

                                                if (scriptUrl.includes('/sw.js')) {
                                                    registration.unregister();
                                                }
                                            });
                                        }).catch(function(error) {
                                            console.log('Portal SW cleanup failed: ', error);
                                        });
                                    });
                                }
                            </script>
                        BLADE
                );
            },
        );
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('portal')
            ->path('portal')
            ->default(fn (): bool => in_array(
                strtolower((string) env('MIGRATION_SCOPE')),
                ['app_portal', 'app-portal'],
                true
            ))
            ->login(Login::class)
            ->homeUrl(fn (): string => Dashboard::getUrl(panel: 'portal'))
            // ->passwordReset()
            ->databaseTransactions()
            ->sidebarCollapsibleOnDesktop(true)
            ->spa()
            ->defaultThemeMode(ThemeMode::Dark)
            ->sidebarFullyCollapsibleOnDesktop()
            ->collapsedSidebarWidth('4rem')
            ->databaseNotifications(true, PortalDatabaseNotifications::class)
            ->globalSearch(false)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->favicon(fn () => asset('favicon.svg'))
            ->brandLogo(fn () => view('portal.brand'))
            ->brandLogoHeight('44px')
            ->viteTheme('resources/css/filament/portal/theme.css')
            ->colors([
                'primary' => Color::Red,
            ])
            // ->userMenu(position: UserMenuPosition::Sidebar)

            // ✅ SOLO páginas aquí
            ->pages([
                Dashboard::class,

            ])

            // ✅ SOLO resources aquí
            ->resources([

            ])
            ->widgets([])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                HandleSpaCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                // KanbanBuilder::make(),
                // FilamentBookingPlugin::make(),
  

            ])
            ->navigationItems([
                NavigationItem::make('Limpiar caché')
                    ->icon('heroicon-o-arrow-path')
                    ->group('Sistema')
                    ->sort(9999)
                    ->url('#')
                    ->isActiveWhen(fn (): bool => false)
                    ->extraAttributes([
                        'x-on:click.prevent' => 'window.clearAppClientCacheAndReload?.()',
                    ]),
                NavigationItem::make('Salir')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->group('Sistema')
                    ->sort(9999)
                    ->url(fn (): string => route('filament.portal.auth.logout'))
                    ->isActiveWhen(fn (): bool => false)
                    ->extraAttributes([
                        'x-on:click.stop.prevent' => '$el.form.submit()',
                    ]),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->userMenuItems([
                'logout' => Action::make('logout')
                    ->label('Salir')
                    ->icon('heroicon-o-arrow-left-end-on-rectangle')
                    ->url(fn (): string => route('filament.portal.auth.logout'))
                    ->postToUrl()
                    ->livewireClickHandlerEnabled(false)
                    ->extraAttributes([
                        'x-on:click.stop.prevent' => '$el.form.submit()',
                    ]),
                'limpiar-caché' => Action::make('limpiar-caché')
                    ->label('Limpiar caché')
                    ->icon('heroicon-o-arrow-path')
                    ->url('#')
                    ->sort(9999)
                    ->url('#')
                    ->extraAttributes([
                        'x-on:click.prevent' => 'window.clearAppClientCacheAndReload?.()',
                    ]),
            ])
            /*->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn(): string => Blade::render('<x-global-help-guide />')
            )*/

            /*->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn(): string => Blade::render('<x-portal-help-guide />')
            )*/

            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => Blade::render(<<<'BLADE'
                    <meta name="csrf-token" content="{{ csrf_token() }}">
                                    <script>
                        (() => {
                            const sidebarKeys = ['isOpen', '_x_isOpen'];
                            const sidebarDesktopKeys = ['isOpenDesktop', '_x_isOpenDesktop'];
                            const hasPersistedSidebarPreference = [...sidebarKeys, ...sidebarDesktopKeys]
                                .some((key) => localStorage.getItem(key) !== null);

                            if (hasPersistedSidebarPreference) {
                                return;
                            }

                            for (const key of sidebarKeys) {
                                localStorage.setItem(key, 'false');
                            }

                            for (const key of sidebarDesktopKeys) {
                                localStorage.setItem(key, 'false');
                            }
                        })();
                    </script>
                    <style>
                        button.fi-color.fi-color-primary.fi-text-color-600.hover\:fi-text-color-500.dark\:fi-text-color-400.dark\:hover\:fi-text-color-300.fi-icon-btn.fi-size-md.shrink-0.grow-0.text-gray-500.hover\:text-gray-700.dark\:text-gray-500.dark\:hover\:text-gray-400 {
                            display: none;
                        }
                        .fi-panel-portal .fi-topbar .nova-topbar-search {
                                    min-width: 2.5rem;
                                    height: 2.5rem;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                    gap: 0.7rem;
                                    padding: 0 0.65rem;
                                    border-radius: 0.95rem;
                                    border: 0;
                                    background: none;
                                    color: rgb(227 199 20 / 78%);
                                    transition: background 160ms ease, border-color 160ms ease, transform 160ms ease;
                                }
                        .fi-panel-portal .fi-topbar .nova-topbar-search:hover {
                            background: rgba(255, 255, 255, 0.065);
                            border-color: rgba(255, 255, 255, 0.12);
                            transform: translateY(-1px);
                        }

                        .fi-panel-portal .fi-topbar .nova-topbar-search__icon {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            color: rgba(255, 255, 255, 0.62);
                        }

                        .fi-panel-portal .fi-topbar .nova-topbar-search__placeholder {
                            display: none;
                            flex: 1;
                            text-align: left;
                            font-size: 0.82rem;
                            line-height: 1;
                        }

                        .fi-panel-portal .fi-topbar .nova-topbar-search__badge,
                        .fi-panel-portal .fi-topbar .nova-topbar-search__kbd {
                            display: none;
                            align-items: center;
                            justify-content: center;
                            min-height: 1.55rem;
                            padding: 0 0.55rem;
                            border-radius: 999px;
                            font-size: 0.68rem;
                            font-weight: 600;
                        }

                        @media (min-width: 1024px) {
                            .fi-panel-portal .fi-topbar .nova-topbar-search {
                                justify-content: flex-start;
                                padding: 0 0.8rem;
                            }

                            .fi-panel-portal .fi-topbar .nova-topbar-search__placeholder {
                                display: block;
                            }

                            .fi-panel-portal .fi-topbar .nova-topbar-search__badge,
                            .fi-panel-portal .fi-topbar .nova-topbar-search__kbd {
                                display: inline-flex;
                            }

                        }

                        .fi-panel-portal .fi-topbar .nova-topbar-search__badge {
                            background: rgba(16, 185, 129, 0.14);
                            border: 1px solid rgba(16, 185, 129, 0.22);
                            color: rgb(110 231 183);
                        }

                        .fi-panel-portal .fi-topbar .nova-topbar-search__kbd {
                            background: rgba(255, 255, 255, 0.05);
                            border: 1px solid rgba(255, 255, 255, 0.08);
                            color: rgba(255, 255, 255, 0.56);
                        }



                        .fi-panel-portal .fi-modal-window {
                        margin: auto;
                            max-height: calc(100dvh - 7rem);
                        }

                        .fi-panel-portal .fi-modal.fi-modal-slide-over .fi-modal-window-ctn,
                        .fi-panel-portal .fi-modal.fi-modal-slide-over .fi-modal-window {
                        margin: auto;
                        }

                        .portal-spotlight__actions-grid {
                            display: grid;
                            grid-template-columns: repeat(2, minmax(0, 1fr));
                            gap: 0.75rem;
                        }

                        .portal-spotlight__utility {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 0.85rem;
                            padding: 0.95rem 1rem;
                            border-radius: 1rem;
                            border: 1px solid rgba(255, 255, 255, 0.08);
                            background: linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0.03));
                            text-align: left;
                            transition: transform 160ms ease, border-color 160ms ease, background 160ms ease;
                        }

                        .portal-spotlight__utility:hover {
                            transform: translateY(-1px);
                            background: linear-gradient(180deg, rgba(255, 255, 255, 0.085), rgba(255, 255, 255, 0.04));
                        }

                        .portal-spotlight__utility-copy {
                            display: flex;
                            min-width: 0;
                            flex: 1;
                            flex-direction: column;
                            gap: 0.18rem;
                        }

                        .portal-spotlight__utility-label {
                            font-size: 0.7rem;
                            font-weight: 700;
                            letter-spacing: 0.18em;
                            text-transform: uppercase;
                            color: rgba(255, 255, 255, 0.68);
                        }

                        .portal-spotlight__utility-subtitle {
                            font-size: 0.76rem;
                            color: rgba(255, 255, 255, 0.86);
                        }

                        .portal-spotlight__utility-icon {
                            display: inline-flex;
                            height: 2rem;
                            width: 2rem;
                            align-items: center;
                            justify-content: center;
                            border-radius: 0.75rem;
                            border: 1px solid rgba(255, 255, 255, 0.1);
                        }

                        .portal-spotlight__utility--indigo .portal-spotlight__utility-icon {
                            background: rgba(99, 102, 241, 0.14);
                            border-color: rgba(99, 102, 241, 0.18);
                        }

                        .portal-spotlight__utility--rose .portal-spotlight__utility-icon {
                            background: rgba(244, 63, 94, 0.14);
                            border-color: rgba(244, 63, 94, 0.2);
                        }

                        .portal-spotlight__utility--amber .portal-spotlight__utility-icon {
                            background: rgba(245, 158, 11, 0.14);
                            border-color: rgba(245, 158, 11, 0.2);
                        }

                        .portal-spotlight__utility--emerald .portal-spotlight__utility-icon {
                            background: rgba(16, 185, 129, 0.14);
                            border-color: rgba(16, 185, 129, 0.2);
                        }

                        @media (max-width: 768px) {
                            .fi-panel-portal .fi-topbar .nova-topbar-search {
                                width: 2.5rem;
                                max-width: 2.5rem;
                                min-width: 2.5rem;
                                padding-inline: 0;
                            }

                            .portal-spotlight__actions-grid {
                                grid-template-columns: repeat(2, minmax(0, 1fr));
                                gap: 0.65rem;
                            }

                            .portal-spotlight__utility {
                                padding: 0.85rem 0.9rem;
                            }

                            .portal-spotlight__utility-label {
                                font-size: 0.66rem;
                            }

                            .portal-spotlight__utility-subtitle {
                                font-size: 0.72rem;
                            }
                            button.fi-icon-btn.fi-size-md.fi-topbar-open-collapse-sidebar-btn,.fi-topbar-collapse-sidebar-btn-ctn {
                            }

                        }

                    </style>
                    <script>
                        (() => {
                            window.Laravel = Object.assign(window.Laravel ?? {}, {
                                userId: @js($notifiable?->getKey()),
                                notifiableType: @js($notifiable?->getMorphClass() ?? 'App.Models.User'),
                            });

                            window.clearAppClientCacheAndReload = async function () {
                                if (! confirm('¿Limpiar caché local y recargar?')) {
                                    return;
                                }

                                try {
                                    localStorage.clear();
                                    sessionStorage.clear();

                                    if ('caches' in window) {
                                        const cacheKeys = await caches.keys();

                                        await Promise.all(cacheKeys.map((cacheKey) => caches.delete(cacheKey)));
                                    }

                                    if ('serviceWorker' in navigator) {
                                        const registrations = await navigator.serviceWorker.getRegistrations();

                                        await Promise.all(registrations.map((registration) => registration.unregister()));
                                    }
                                } finally {
                                    window.location.reload();
                                }
                            };

                            const dispatchLaravelUserContext = function () {
                                window.dispatchEvent(new CustomEvent('laravel-user-context', {
                                    detail: {
                                        userId: window.Laravel?.userId ?? null,
                                        notifiableType: window.Laravel?.notifiableType ?? 'App.Models.User',
                                    },
                                }));
                            };

                            if (window.location.pathname === '/app/login') {
                                return;
                            }

                            let csrfTokenRefreshInterval;

                            function refreshCsrfToken() {
                                const currentToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                                if (!currentToken) {
                                    return;
                                }

                                document.querySelectorAll('input[name="_token"]').forEach(input => {
                                    input.value = currentToken;
                                });
                            }

                            refreshCsrfToken();
                            dispatchLaravelUserContext();
                            csrfTokenRefreshInterval = setInterval(refreshCsrfToken, 30 * 60 * 1000);

                            window.addEventListener('beforeunload', function() {
                                if (csrfTokenRefreshInterval) {
                                    clearInterval(csrfTokenRefreshInterval);
                                }
                            });

                            window.addEventListener('pageshow', function() {
                                refreshCsrfToken();
                                dispatchLaravelUserContext();
                            });

                            document.addEventListener('visibilitychange', function() {
                                if (!document.hidden) {
                                    refreshCsrfToken();
                                    dispatchLaravelUserContext();
                                }
                            });
                        })();
                    </script>
                BLADE, [
                    'notifiable' => auth()->user() ?? auth('web')->user(),
                ])
            )
            /**
             * ✅ Buscador estilo portal-pro dentro de topbar Filament
             * Centrable por CSS (absolute center)
             */
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                fn (): string => request()->routeIs('filament.portal.auth.*')
                    ? ''
                    : Blade::render(<<<'BLADE'
                        <a
                            href="#"
                            x-data="{}"
                            x-on:click.prevent="window.dispatchEvent(new CustomEvent('open-spotlight'))"
                            class="nova-topbar-search"
                            aria-label="Abrir Spotlight"
                            title="Spotlight"
                        >
                            <span class="nova-topbar-search__icon">
                                <x-filament::icon icon="heroicon-o-magnifying-glass" class="h-5 w-5" />
                            </span>
                            <span class="nova-topbar-search__kbd">⌘K</span>
                        </a>
                    BLADE
                    )
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): string => request()->routeIs('filament.portal.auth.*')
                    ? ''
                    : Blade::render(<<<'BLADE'
                        <div class="hidden">
                            <x-portal-help-guide />
                        </div>
                        @livewire('portal-spotlight')
                    BLADE
                    )
            );

    }
}
