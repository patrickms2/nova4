<?php

namespace App\Providers\Filament;

use Agroezinger\FilamentNavigationEnhanced\NavigationEnhancedPlugin;
use App\Enums\TablerIcon;
use Voodflow\Voodflow\VoodflowPlugin;
use Crumbls\NavCraft\NavCraftPlugin;
use Archilex\AdvancedTables\Plugin\AdvancedTablesPlugin;
use Crumbls\FilamentDatabase\FilamentDatabasePlugin;
use Crumbls\Layup\LayupPlugin;
use Crumbls\NavCraft\Resources\MenuResource;
use Filament\Actions\View\ActionsIconAlias;
use Filament\Facades\Filament;
use Filament\Forms\View\FormsIconAlias;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Notifications\View\NotificationsIconAlias;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\View\SchemaIconAlias;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentIcon;
use Filament\Support\Facades\FilamentView;
use Filament\Support\View\SupportIconAlias;
use Filament\Tables\View\TablesIconAlias;
use Filament\View\PanelsIconAlias;
use Filament\View\PanelsRenderHook;
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBaseCompanionPlugin;
use Guava\FilamentKnowledgeBase\Plugins\KnowledgeBasePlugin;
use Heiner\FilamentAgenticChatbot\FilamentAgenticChatbotPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Marcelodelgado\Announcements\AnnouncementsPlugin;
use OsamaAtef\DrilldownSidebar\DrilldownSidebarPlugin;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Webkul\CustomFields\CustomFieldsPlugin;
use Webkul\CustomFields\Models\Field;
use Wezlo\FilamentSearchSpotlight\Actions\SpotlightAction;
use Wezlo\FilamentSearchSpotlight\FilamentSearchSpotlightPlugin;
use Filament\Support\Enums\Width;
use Filament\Pages\Page;
use App\Filament\App\Community\Pages\CommunityDashboard;
use Filament\Launchpad\LaunchpadPlugin;
use Asmit\AdvancedKanban\KanbanBuilder;
use App\Models\Sale;
use App\Filament\App\Facturacion\Resources\FacturaResource;
use Vaslv\FilamentTopbarMenu\TopbarMenuPlugin;
use App\Filament\App\Rentals\Resources\RentalReservationResource;
use App\Filament\App\Facturacion\Resources\ClienteResource;
use App\Filament\App\Facturacion\Resources\EmpresaResource;
use App\Filament\App\Facturacion\Resources\GastoResource;
use App\Filament\App\Facturacion\Resources\TaskResource;
use App\Filament\App\Facturacion\Resources\ProjectResource;
use App\Filament\App\Facturacion\Resources\NoteResource;
use App\Filament\App\Resources\Announcements\AnnouncementResource;
use Martin6363\SidebarResize\SidebarResizePlugin;

class CommunityPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        FilamentIcon::register([
            ActionsIconAlias::DELETE_ACTION => TablerIcon::Trash,
            ActionsIconAlias::DETACH_ACTION => TablerIcon::Trash,
            ActionsIconAlias::EDIT_ACTION => TablerIcon::Pencil,
            ActionsIconAlias::VIEW_ACTION => TablerIcon::Eye,
            ActionsIconAlias::REPLICATE_ACTION => TablerIcon::CopyPlus,

            PanelsIconAlias::USER_MENU_LOGOUT_BUTTON => TablerIcon::Logout2,
            PanelsIconAlias::USER_MENU_PROFILE_ITEM => TablerIcon::User,
            PanelsIconAlias::THEME_SWITCHER_LIGHT_BUTTON => TablerIcon::Sun,
            PanelsIconAlias::THEME_SWITCHER_DARK_BUTTON => TablerIcon::Moon,
            PanelsIconAlias::THEME_SWITCHER_SYSTEM_BUTTON => TablerIcon::DeviceDesktop,
            PanelsIconAlias::SIDEBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON => TablerIcon::Bell,
            PanelsIconAlias::TOPBAR_OPEN_DATABASE_NOTIFICATIONS_BUTTON => TablerIcon::Bell,
            PanelsIconAlias::GLOBAL_SEARCH_FIELD => TablerIcon::Search,
            PanelsIconAlias::SIDEBAR_EXPAND_BUTTON => TablerIcon::ArrowRightDashed,
            PanelsIconAlias::SIDEBAR_COLLAPSE_BUTTON => TablerIcon::ArrowLeftDashed,

            TablesIconAlias::ACTIONS_FILTER => TablerIcon::Filters,
            TablesIconAlias::SEARCH_FIELD => TablerIcon::Search,
            TablesIconAlias::ACTIONS_COLUMN_MANAGER => TablerIcon::Columns,
            TablesIconAlias::ACTIONS_OPEN_BULK_ACTIONS => TablerIcon::BoxMultiple,

            NotificationsIconAlias::DATABASE_MODAL_EMPTY_STATE => TablerIcon::BellOff,
            NotificationsIconAlias::NOTIFICATION_CLOSE_BUTTON => TablerIcon::X,
            NotificationsIconAlias::NOTIFICATION_INFO => TablerIcon::InfoCircle,
            NotificationsIconAlias::NOTIFICATION_SUCCESS => TablerIcon::CircleCheck,
            NotificationsIconAlias::NOTIFICATION_WARNING => TablerIcon::AlertTriangle,
            NotificationsIconAlias::NOTIFICATION_DANGER => TablerIcon::AlertCircle,

            SupportIconAlias::MODAL_CLOSE_BUTTON => TablerIcon::X,
            SupportIconAlias::BREADCRUMBS_SEPARATOR => TablerIcon::ChevronsRight,
            SupportIconAlias::PAGINATION_NEXT_BUTTON => TablerIcon::ArrowRight,
            SupportIconAlias::PAGINATION_PREVIOUS_BUTTON => TablerIcon::ArrowLeft,
            SupportIconAlias::SECTION_COLLAPSE_BUTTON => TablerIcon::ChevronUp,

            FormsIconAlias::COMPONENTS_KEY_VALUE_ACTIONS_DELETE => TablerIcon::Trash,
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_DELETE => TablerIcon::Trash,
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_EXPAND => TablerIcon::ChevronDown,
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_COLLAPSE => TablerIcon::ChevronUp,
            FormsIconAlias::COMPONENTS_REPEATER_ACTIONS_REORDER => TablerIcon::ArrowsSort,

            SchemaIconAlias::COMPONENTS_WIZARD_COMPLETED_STEP => TablerIcon::Check,
        ]);

      

        FilamentView::registerRenderHook(
            PanelsRenderHook::GLOBAL_SEARCH_AFTER,
            function (): string {
                if (Filament::getCurrentPanel()?->getId() !== 'community') {
                    return '';
                }

                return Blade::render(<<<'BLADE'
                    <button
                        type="button"
                        x-data="{}"
                        x-on:click.prevent="window.dispatchEvent(new CustomEvent('open-global-help-guide'))"
                        class="app-topbar-pill app-topbar-pill--help"
                        aria-label="Abrir ayuda"
                        title="Ayuda"
                    >
                        <span class="app-topbar-pill__icon">
                            <x-filament::icon icon="heroicon-o-question-mark-circle" class="h-5 w-5" />
                        </span>
                        <span class="app-topbar-pill__label">Ayuda</span>
                    </button>
                BLADE
                );
            },
        );

        return $panel
            ->default()
            ->id('community')
            ->path('community')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Red,
            ])
            ->spa()
           ->plugins([
                SidebarResizePlugin::make(),
            ])
        ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
           ->navigationGroups([
                NavigationGroup::make(fn () => 'Mantenimiento')
                                    ->icon('heroicon-o-wrench-screwdriver')
                    ->collapsible(true),            
                NavigationGroup::make(fn () => 'Propietarios')
                                    ->icon('heroicon-o-pencil')
                    ->collapsible(true),
                NavigationGroup::make(fn () => 'Empresa')
                                    ->icon('heroicon-o-pencil')
                    ->collapsible(true),
                                NavigationGroup::make(fn () => 'Nova Hub')
                                    ->icon('heroicon-o-pencil')->collapsed(),

                                NavigationGroup::make(fn () => 'Nova Hub')
                                    ->icon('heroicon-o-pencil')->collapsed(),

                NavigationGroup::make(fn () => 'Ajustes')
                                    ->icon('heroicon-o-pencil')
                    ->collapsible(false),
                NavigationGroup::make(fn () => 'Nova Community')
                                    ->icon('heroicon-o-pencil')
                    ->collapsible(false),

                NavigationGroup::make(fn () => 'Nova Invoice')
                                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make()
                    ->label('Shop')
                    ->collapsed(),
                NavigationGroup::make(fn () => 'Nova Property')
                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make(fn () => 'Nova Hub')
                    ->icon('heroicon-o-pencil')->collapsed(),
                NavigationGroup::make(fn () => 'Property OS')
                    ->icon('heroicon-o-pencil')->collapsed(),
                                    NavigationGroup::make(fn () => 'Tourist')
                                    ->icon('heroicon-o-pencil')->collapsed(),
            ])
                /*->discoverResources(in: app_path('Filament/App/Facturacion/Resources'), for: 'App\Filament\App\Facturacion\Resources')*/
           /* ->discoverResources(in: 'app/Filament/App/Rentals/Resources', for: 'App\\Filament\\App\\Rentals\\Resources')*/
            /*->discoverPages(in: 'app/Filament/App/Rentals/Pages', for: 'App\\Filament\\App\\Rentals\\Pages')*/
            ->discoverResources(in: 'app/Filament/App/Resources/Announcements', for: 'App\\Filament\\App\\Resources\\Announcements')
            // ->discoverResources(in: 'app/Filament/App/Resources', for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Facturacion/Pages'), for: 'App\\Filament\\App\\Facturacion\\Pages')

                                  ->discoverResources(in: app_path('Nova/NovaHub/Resources'), for: 'Nova\\NovaHub\\Resources')
                       ->discoverResources(in: app_path('Nova/Domotics/Resources'), for: 'Nova\\Domotics\\Resources')
                 // ->discoverResources(in: app_path('Filament/App/Rentals/Resources'), for: 'App\Filament\App\Rentals\Resources')
            ->discoverPages(in: app_path('Filament/App/Domotics/Pages'), for: 'App\Filament\App\Domotics\Pages')
            ->discoverPages(in: app_path('Filament/App/Community/Pages'), for: 'App\Filament\App\Community\Pages')

                // ->discoverResources(in: app_path('Filament/App/Facturacion/Resources'), for: 'App\Filament\App\Facturacion\Resources')
            //->discoverResources(in: app_path('Filament/App/Rentals/Domotics/Resources'), for: 'App\Filament\App\Rentals\Domotics\Resources')
            ->discoverResources(in: app_path('Filament/App/Community/Resources'), for: 'App\Filament\App\Community\Resources')
            // ->discoverResources(in: app_path('Filament/App/NovaHub/Resources'), for: 'App\Filament\App\\NovaHub\\Resources')

            // ->discoverClusters(in: app_path('Filament/App/Facturacion/Facturacion'), for: 'App\Filament\App\Facturacion\Facturacion')
            //->discoverClusters(in: app_path('Filament/App/Facturacion'), for: 'App\\Filament\\App\\Facturacion')
            /*->discoverClusters(in: app_path('Filament/App/Rentals'), for: 'App\\Filament\\App\\Rentals')*/
            // ->discoverClusters(in: app_path('Filament/App/NovaHub'), for: 'App\\Filament\\App\\NovaHub')

            ->discoverWidgets(in: app_path('Filament/App/Community/Widgets'), for: 'App\\Filament\\App\\Community\\Widgets')


            ->pages([
                CommunityDashboard::class,
            ])
            ->resources([
AnnouncementResource::class,
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
                            ->label('Salir')
                            ->icon('heroicon-o-arrow-right-on-rectangle')
                            ->keywords(['signout', 'quit'])
                            ->group('Account')
                            ->url(fn () => filament()->getLogoutUrl()),

                    )
                    ->actions([
                                                SpotlightAction::make('comunidades')->label('Comunidades')->url('/community/communities'),
                                                SpotlightAction::make('planes')->label('Planes')->url('/community/community-plans'),
                                                SpotlightAction::make('incidencias')->label('Incidencias')->url('/community/incidents'),
                                                SpotlightAction::make('tickets')->label('Tickets')->url('/community/community-tickets'),
                                                SpotlightAction::make('documents')->label('Documents')->url('/community/community-owner-documents'),
                                                SpotlightAction::make('properties')->label('Properties')->url('/community/community-properties'),
                                                SpotlightAction::make('propietarios')->label('Propietarios')->url('/community/owners'),



                    
                    ])

                    // Hide actions registered in the global registry by name. Plugin-scoped
                    // actions with the same name automatically override their global twin,
                    // so overrideActions() is only needed when you want to hide without
                    // replacing.
                    ->overrideActions(['legacy-action'])

                    // Skip the auto-generated "Create {Resource}" entries entirely.
                    ->disableCreateActions(),
                FilamentFullCalendarPlugin::make(),
  

                KanbanBuilder::make(),
                AdvancedTablesPlugin::make()
                    ->userViewsEnabled(false),
                NavigationEnhancedPlugin::make(),

                
                DrilldownSidebarPlugin::make()
                    ->drilledGroups([
                        'Nova Property',
                        'Propietarios',
                        'Nova Hub',
                        'Nova Community',
                        'Ajustes',
                        'Nova Invoice',
                        'Empresa',

                    ])
                    ->subGroups([
                        'Reservas' => ['Reservas','Res. Restaurantes', 'Res. Tours', 'Res. Taxis', 'Res. Hoteles', 'Res. Locations', 'Res. Productos', 'Res. Packages', 'Res. Alquileres','Bookings Internos', 'Bookings Externos/Pagos', 'Hub de Cliente', 'Servicios Contratados', 'Integraciones Externas', 'Facturación', 'CMS Pages', 'Nova WhatsApp'],
                        'Nova Hub' => ['Clientes','Paneles', 'Tools', 'Editor Visual MCP', 'Workflows', 'Reservas','MCP','Agentic Chatbot','IA','Ajustes','knowledge-base'],
                        'Facturación' => ['Clientes', 'Facturas','Gastos','Tareas','Notas','Proyectos','Facturación'],
                        'Nova Community' => ['Propiedades','Propietarios',  'Comunidades', 'Empresa','People','Planes', 'Departamentos','Roles', 'Permisos'],
                        'Nova Property' => ['Propiedades', 'Usuarios', 'Roles', 'Permisos'],

                        'IA' => ['Perfiles de IA', 'Conocimiento IA', 'Prompts', 'Chats', 'Bots', 'API Connectors', 'Channels', 'Conversaciones'],
                        'Catálogo' => ['Restaurantes', 'Tours', 'Taxis', 'Hoteles', 'Locations', 'Productos', 'Paquetes', 'Alquileres','Ajustes'],
                        'Ajustes' => ['Ajustes','Intents', 'Mapeos', 'Listing Config', 'Cross-selling', 'Integraciones', 'Tool Tester', 'Inspector', 'Logs de Sync', 'Users', 'Menus', 'Custom Fields', 'Database'],
                    ]),

            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
