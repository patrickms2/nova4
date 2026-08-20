<?php

namespace App\Filament\App\NovaHub\Resources\NovaBusinesses;

use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\CreateNovaBusiness;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\EditNovaBusiness;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ListNovaBusinesses;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessAiKnowledge;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessAiProfiles;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessPromps;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessBookings;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessChat;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessCrossSellingRules;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessExternalBookings;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessExternalPayments;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessFacturas;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessHub;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessInspector;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessIntegrations;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessIntentRules;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessListingCategories;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessMcpServers;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessProducts;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessPublicBookingRequests;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessRestaurants;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessServices;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessSyncLogs;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessTools;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessMcpVisualEditor;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessToolTester;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessTours;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Pages\ManageNovaBusinessWhatsappChannels;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\RelationManagers\ClientesRelationManager;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\RelationManagers\FacturasRelationManager;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Schemas\NovaBusinessForm;
use App\Filament\App\NovaHub\Resources\NovaBusinesses\Tables\NovaBusinessesTable;
use App\Models\NovaBusiness;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaBusinessResource extends Resource
{
    protected static ?string $model = NovaBusiness::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Nova Hub';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?string $navigationLabel = 'Clientes';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 1;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function form(Schema $schema): Schema
    {
        return NovaBusinessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaBusinessesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            ClientesRelationManager::class,
            FacturasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaBusinesses::route('/'),
            'create' => CreateNovaBusiness::route('/create'),
            'edit' => EditNovaBusiness::route('/{record}/edit'),
            'servicios' => ManageNovaBusinessServices::route('/{record}/servicios'),
            'facturas' => ManageNovaBusinessFacturas::route('/{record}/facturas'),
            'integraciones' => ManageNovaBusinessIntegrations::route('/{record}/integraciones'),
            'tours' => ManageNovaBusinessTours::route('/{record}/tours'),
            'restaurantes' => ManageNovaBusinessRestaurants::route('/{record}/restaurantes'),
            'productos' => ManageNovaBusinessProducts::route('/{record}/productos'),
            'reservas' => ManageNovaBusinessBookings::route('/{record}/reservas'),
            'reservas-externas' => ManageNovaBusinessExternalBookings::route('/{record}/reservas-externas'),
            'solicitudes-publicas' => ManageNovaBusinessPublicBookingRequests::route('/{record}/solicitudes-publicas'),
            'pagos-externos' => ManageNovaBusinessExternalPayments::route('/{record}/pagos-externos'),
            'logs-sync' => ManageNovaBusinessSyncLogs::route('/{record}/logs-sync'),
            'whatsapp' => ManageNovaBusinessWhatsappChannels::route('/{record}/whatsapp'),
            'hub' => ManageNovaBusinessHub::route('/{record}/hub'),
            'mcp' => ManageNovaBusinessMcpServers::route('/{record}/mcp'),
            'mcp-visual-editor' => ManageNovaBusinessMcpVisualEditor::route('/{record}/mcp-visual-editor'),
            'tools' => ManageNovaBusinessTools::route('/{record}/tools'),
            'inspector' => ManageNovaBusinessInspector::route('/{record}/inspector'),
            'tool-tester' => ManageNovaBusinessToolTester::route('/{record}/tool-tester'),
            'chat' => ManageNovaBusinessChat::route('/{record}/chat'),
            'ia' => ManageNovaBusinessAiProfiles::route('/{record}/ia'),
            'promps' => ManageNovaBusinessPromps::route('/{record}/promp'),
            'conocimiento-ia' => ManageNovaBusinessAiKnowledge::route('/{record}/conocimiento-ia'),
            'listing-config' => ManageNovaBusinessListingCategories::route('/{record}/listing-config'),
            'cross-selling' => ManageNovaBusinessCrossSellingRules::route('/{record}/cross-selling'),
            'intents' => ManageNovaBusinessIntentRules::route('/{record}/intents'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            EditNovaBusiness::class,
            ManageNovaBusinessWhatsappChannels::class,
            ManageNovaBusinessAiProfiles::class,
            ManageNovaBusinessAiKnowledge::class,
            ManageNovaBusinessPromps::class,
            ManageNovaBusinessChat::class,
            ManageNovaBusinessTours::class,
            ManageNovaBusinessRestaurants::class,
            ManageNovaBusinessProducts::class,
            ManageNovaBusinessBookings::class,
            ManageNovaBusinessExternalBookings::class,
            ManageNovaBusinessPublicBookingRequests::class,
            ManageNovaBusinessExternalPayments::class,
            ManageNovaBusinessServices::class,
            ManageNovaBusinessFacturas::class,
            ManageNovaBusinessIntegrations::class,
            ManageNovaBusinessSyncLogs::class,
            ManageNovaBusinessListingCategories::class,
            ManageNovaBusinessCrossSellingRules::class,
            ManageNovaBusinessIntentRules::class,
            ManageNovaBusinessHub::class,
            ManageNovaBusinessMcpServers::class,
            ManageNovaBusinessMcpVisualEditor::class,
            ManageNovaBusinessTools::class,
            ManageNovaBusinessInspector::class,
            ManageNovaBusinessToolTester::class,
        ]);
    }
}
