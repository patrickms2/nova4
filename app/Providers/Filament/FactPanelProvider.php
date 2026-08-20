<?php

namespace App\Providers\Filament;

use App\Support\Nova\NovaFilamentMenuBuilder;

use Agroezinger\FilamentNavigationEnhanced\NavigationEnhancedPlugin;
use App\Enums\TablerIcon;
use Archilex\AdvancedTables\Plugin\AdvancedTablesPlugin;
use Crumbls\FilamentDatabase\FilamentDatabasePlugin;
use Crumbls\Layup\LayupPlugin;
use Crumbls\NavCraft\NavCraftPlugin;
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
use Filament\Pages\Dashboard;
use Filament\Launchpad\LaunchpadPlugin;
use Filament\Launchpad\Launchpad\{LaunchpadTab, TileGroup, Tile};
use App\Models\Sale;

use Vaslv\FilamentTopbarMenu\TopbarMenuPlugin;

class FactPanelProvider extends PanelProvider
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



        return $panel
            ->default()
            ->id('fact')
            ->path('fact')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->spa()
            ->dragAndScroll()

        ->globalSearch(true)
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
            ->navigationGroups([
                NavigationGroup::make('Facturación'),
                NavigationGroup::make('Gastos'),
                NavigationGroup::make('Clientes'),
                NavigationGroup::make('Ajustes'),
                NavigationGroup::make('Villas'),

            ])


            ->discoverResources(in: app_path('Filament/KnowledgeBase/Resources'), for: 'App\Filament\KnowledgeBase\Resources')
       ->discoverResources(in: app_path('Filament/Domotics/Resources'), for: 'App\Filament\Domotics\Resources')
            ->discoverPages(in: app_path('Filament/Domotics/Pages'), for: 'App\Filament\Domotics\Pages')
            ->discoverResources(in: app_path('Filament/TourAdmin/Resources'), for: 'App\Filament\TourAdmin\Resources')
            ->pages([
            ])

            ->resources([

            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\Filament\App\Widgets')
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
                //SidebarResizePlugin::make(),

                TopbarMenuPlugin::make()
                    // Hide the management resource on this panel (e.g. a public panel
                    // that should only display the menu):
                    ->resource(true)

                    // Put the resource into a navigation group / position:
                    ->resourceNavigationGroup('Ajustes')
                    ->resourceNavigationSort(10),


         LaunchpadPlugin::make()->tabs([
             LaunchpadTab::make('Início')->groups([
                 TileGroup::make('Favoritos')->tiles([
                     Tile::make('Vendas Hoje')
                         ->subtitle('Ponto de Venda')
                         ->icon('heroicon-o-banknotes')
                         ->kpi(fn () => Sale::today()->count())
                         ->trend('+0% vs ontem', 'success')
                         ->url('/admin/sales'),
                 ]),
             ]),
             LaunchpadTab::make('Facturas')->groups([
                 TileGroup::make('Favoritos')->tiles([
                     Tile::make('Vendas Hoje')
                         ->subtitle('Ponto de Venda')
                         ->icon('heroicon-o-banknotes')
                         ->kpi(fn () => Sale::today()->count())
                         ->trend('+0% vs ontem', 'success')
                         ->url('/admin/sales'),
                 ]),
             ]),
             LaunchpadTab::make('Nova')->groups([
                 TileGroup::make('Favoritos')->tiles([
                     Tile::make('Vendas Hoje')
                         ->subtitle('Ponto de Venda')
                         ->icon('heroicon-o-banknotes')
                         ->kpi(fn () => Sale::today()->count())
                         ->trend('+0% vs ontem', 'success')
                         ->url('/admin/sales'),
                 ]),
             ]),
         ]),
        AdvancedTablesPlugin::make(),
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
                FilamentDatabasePlugin::make()
                    ->authorize(function () {
                        // You need to customize this. It controls who can view it.
                        return true;
                    }),
                CustomFieldsPlugin::make()
                    ->navigationGroup('Ajustes')
                    ->navigationLabel('Custom Fields')
                    ->navigationIcon('heroicon-o-puzzle-piece')
                    ->navigationSort(50)
                    ->navigationBadge(fn () => Field::count())
                    ->navigationBadgeColor('primary')
                    ->slug('admin/custom-fields'),



                AnnouncementsPlugin::make()
                    ->pollingInterval('120s'), // optional, default: '60s'


            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
