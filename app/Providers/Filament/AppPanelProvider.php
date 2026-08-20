<?php

namespace App\Providers\Filament;

use Agroezinger\FilamentNavigationEnhanced\NavigationEnhancedPlugin;
use App\Filament\App\Facturacion\Resources\ClienteResource;
use App\Filament\App\Facturacion\Resources\FacturaResource;
use App\Filament\App\NovaHub\Pages\LiveSystemGraph2;
use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Archilex\AdvancedTables\Plugin\AdvancedTablesPlugin;
use Asmit\AdvancedKanban\KanbanBuilder;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Guava\FilamentMcp\Enums\McpOperation;
use Guava\FilamentMcp\Mcp\McpResource;
use Guava\FilamentMcp\McpPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use LaBoiteACode\DependencyGraph\DependencyGraphPlugin;
use OsamaAtef\DrilldownSidebar\DrilldownSidebarPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Voodflow\Voodflow\VoodflowPlugin;
use Wezlo\FilamentSearchSpotlight\Actions\SpotlightAction;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;
use App\Filament\App\Rentals\Domotics\Resources\Properties\PropertyResource;
use Caresome\FilamentAuthDesigner\AuthDesignerPlugin;
use Caresome\FilamentAuthDesigner\Data\AuthPageConfig;
use Caresome\FilamentAuthDesigner\Enums\MediaPosition;
use Caresome\FilamentAuthDesigner\View\AuthDesignerRenderHook;
use App\Support\ActivityAccess;
use App\Support\SupportAccess;
use App\Filament\App\Pages\Auth\Login;
use App\Filament\App\Rentals\Pages\CasaElPatioDashboard;
use Martin6363\SidebarResize\SidebarResizePlugin;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Orange,
            ])
           ->plugins([
                SidebarResizePlugin::make(),
            ])
            ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
            ->pages([
                /*Dashboard::class,*/
                /*CasaElPatioDashboard::class,*/
            ])
            ->resources([
                // FacturaResource::class,
                // RentalReservationResource::class,
PropertyResource::class,

            ])

                 // ->discoverResources(in: app_path('Filament/App/Rentals/Resources'), for: 'App\Filament\App\Rentals\Resources')
            ->discoverPages(in: app_path('Filament/App/Rentals/Domotics/Pages'), for: 'App\Filament\App\Rentals\Domotics\Pages')
            /*->discoverPages(in: app_path('Filament/App/Community/Pages'), for: 'App\Filament\App\Community\Pages')*/
            //->discoverPages(in: app_path('Filament/App/Rentals/Pages'), for: 'App\Filament\App\Rentals\Pages')

                // ->discoverResources(in: app_path('Filament/App/Facturacion/Resources'), for: 'App\Filament\App\Facturacion\Resources')
            //->discoverResources(in: app_path('Filament/App/Rentals/Domotics/Resources'), for: 'App\Filament\App\Rentals\Domotics\Resources')
            /*->discoverResources(in: app_path('Filament/App/Community/Resources'), for: 'App\Filament\App\Community\Resources')*/
            // ->discoverResources(in: app_path('Filament/App/NovaHub/Resources'), for: 'App\Filament\App\\NovaHub\\Resources')

            // ->discoverClusters(in: app_path('Filament/App/Facturacion/Facturacion'), for: 'App\Filament\App\Facturacion\Facturacion')
            ->discoverClusters(in: app_path('Filament/App/Facturacion'), for: 'App\\Filament\\App\\Facturacion')
            ->discoverClusters(in: app_path('Filament/App/Rentals'), for: 'App\\Filament\\App\\Rentals')
            // ->discoverClusters(in: app_path('Filament/App/NovaHub'), for: 'App\\Filament\\App\\NovaHub')

            ->discoverWidgets(in: app_path('Nova/Domotics/Widgets'), for: 'Nova\\Domotics\Widgets')
            /*->discoverWidgets(in: app_path('Filament/App/Community/Widgets'), for: 'App\\Filament\\App\\Community\\Widgets')*/
            ->navigationGroups([


                NavigationGroup::make(fn () => 'Nova Properties')
                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make(fn () => 'NOVA Community')
                    ->icon('heroicon-o-building-office-2')->collapsed(),                    
                NavigationGroup::make(fn () => 'Nova Access')
                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make(fn () => 'Nova Hub')
                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make(fn () => 'Nova Invoice')
                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make(fn () => 'Ajustes')
                    ->icon('heroicon-o-pencil')
                    ->collapsible(false),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([

                /*McpPlugin::make()
                    ->local()
                    ->instructions('This server manages invoices and clients.
                    properties, users, etc.')
                    ->resources([
                        McpResource::make(ClienteResource::class)
                            ->crud(),
                        McpResource::make(FacturaResource::class)
                            // Which operations are exposed (default: List + Get)
                            ->operations(McpOperation::List, McpOperation::Get, McpOperation::Create)
                            ->crud(),
                    ])
                    ->tokens(),*/
AuthDesignerPlugin::make()
                    ->login(
                        fn(AuthPageConfig $config) => $config
                            ->media(asset('assets/background.jpg'))
                            ->mediaPosition(MediaPosition::Cover)
                            ->blur(1)
                            ->usingPage(Login::class)
                            ->themeToggle()
                            ->renderHook(AuthDesignerRenderHook::MediaOverlay, fn() => view('filament.filament.app.auth.back-login-view'))
                            ->renderHook('auth.login.back.button', function () {
                                if (env('SWITH_PANELS', false)) {
                                    return view('components.login-back-button');
                                }
                                return null;
                            })
                            ->themeToggle()
                            ->renderHook('auth.login.back.button', function () {
                                if (env('SWITH_PANELS', false)) {
                                    return view('components.login-back-button');
                                }
                                return null;
                            })
                    ),
                ...SupportAccess::canAccess(auth()->user())
                    ? [
                        FileManagerPlugin::make()
                            ->only([
                                FileManager::class,              // Database mode - full CRUD file manager
                                FileSystem::class,               // Storage mode - read-only file browser
                                FileSystemItemResource::class,   // Resource for direct database table editing
                                SchemaExample::class,
                            ]),
                    ]
                    : [],
                /*DependencyGraphPlugin::make(),*/
                /*VoodflowPlugin::make(),*/
                /*AdvancedTablesPlugin::make(),*/
                FilamentSearchSpotlightPlugin::make()
                    // Keyboard binding (Mousetrap syntax). Accepts a string or an array.
                    ->keyBinding('mod+k')

                    // Placeholder text for the search input.
                    ->placeholder('Ir a…')

                    // Any valid CSS width (rem, px, vw, %, …). Applied as an inline style so
                    // it is not subject to Tailwind purging.
                    ->maxWidth('36rem')

                    // Max results per category.
                    ->resultLimitPerCategory(8)

                    // Toggle built-in categories (all default on).
                    ->records()           // records(false) to hide
                    ->resources()
                    ->pages()
                    ->actionsEnabled()
                    // Register actions scoped to this panel (on top of the global registry).
                    ->action(
                        SpotlightAction::make('log-out')
                            ->label('Log out')
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->keywords(['signout', 'quit'])
                            ->group('Account')
                            ->url(fn () => filament()->getLogoutUrl()),

                    )
                    ->actions([
                        SpotlightAction::make('impersonate')->label('Impersonate user')->url('/impersonate'),
                        SpotlightAction::make('bot-ia')
                            ->label('Bot IA')
                            ->icon('heroicon-o-sparkles')
                            ->keywords(['bot', 'ia', 'assistant'])
                            ->group('Public')
                            ->url('/ai-bot'),
                        SpotlightAction::make('explore')
                            ->label('Explore')
                            ->icon('heroicon-o-sparkles')
                            ->keywords(['map', 'explore', 'public'])
                            ->group('Public')
                            ->url('/explore'),
                        SpotlightAction::make('impersonate')->label('Impersonate user')->url('/impersonate'),
                        SpotlightAction::make('mcp-dashboard')
                            ->label('MCP Dashboard')
                            ->icon('heroicon-o-sparkles')
                            ->keywords(['bot', 'ia', 'assistant'])
                            ->group('MCP')
                            ->url('/admin/mcp-dashboard'),
                        SpotlightAction::make('business-dashboard')
                            ->label('Business Dashboard')
                            ->icon('heroicon-o-sparkles')
                            ->keywords(['bot', 'ia', 'assistant'])
                            ->group('MCP')
                            ->url('/admin/mcp-business-hub'),
                    ])

                    // Hide actions registered in the global registry by name. Plugin-scoped
                    // actions with the same name automatically override their global twin,
                    // so overrideActions() is only needed when you want to hide without
                    // replacing.
                    ->overrideActions(['legacy-action'])

                    // Skip the auto-generated "Create {Resource}" entries entirely.
                    ->disableCreateActions(),
                FilamentFullCalendarPlugin::make(),

                //NavCraftPlugin::make(),
                KanbanBuilder::make(),
                AdvancedTablesPlugin::make()
                    ->userViewsEnabled(false),
                NavigationEnhancedPlugin::make(),
                DrilldownSidebarPlugin::make()
                    ->drilledGroups([
                        'Nova Hub',
                        'Facturación',
                        'Ajustes',
                        'roperty OS',
                        'Domotics',
                    ])
                    ->subGroups([
                        'Reservas' => ['Reservas', 'Res. Restaurantes', 'Res. Tours', 'Res. Taxis', 'Res. Hoteles', 'Res. Locations', 'Res. Productos', 'Res. Packages', 'Res. Alquileres', 'Bookings Internos', 'Bookings Externos/Pagos', 'Hub de Cliente', 'Servicios Contratados', 'Integraciones Externas', 'Facturación', 'CMS Pages', 'Nova WhatsApp'],
                        'Nova Hub' => ['Clientes', 'Paneles', 'Tools', 'Editor Visual MCP', 'Workflows', 'Live System Graph', 'Reservas', 'MCP', 'Agentic Chatbot', 'IA', 'Ajustes', 'knowledge-base'],
                        'Facturación' => ['Clientes', 'Facturas', 'Gastos', 'Tareas', 'Notas', 'Proyectos', 'Facturación'],
                        'Catálogo' => ['Restaurantes', 'Tours', 'Taxis', 'Hoteles', 'Locations', 'Productos', 'Paquetes', 'Alquileres', 'Ajustes'],
                        'Ajustes' => ['Ajustes', 'Intents', 'Mapeos', 'Listing Config', 'Cross-selling', 'Integraciones', 'Tool Tester', 'Inspector', 'Logs de Sync', 'Users', 'Menus', 'Custom Fields', 'Database'],
                        'Property OS' => ['Rentals'],
                        'Domotics' => ['Domotics'],
                    ]),

            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
