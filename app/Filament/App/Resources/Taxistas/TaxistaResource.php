<?php

namespace App\Filament\App\Resources\Taxistas;

use App\Filament\App\Resources\Taxistas\Pages\CreateTaxista;
use App\Filament\App\Resources\Taxistas\Pages\EditTaxista;
use App\Filament\App\Resources\Taxistas\Pages\ListTaxistas;
use App\Filament\App\Resources\Taxistas\Pages\ViewTaxista;

$kanbanPath = base_path('app/Filament/App/Resources/Taxistas/Pages/ManageDocumentosKanbanTaxista.php');
if (file_exists($kanbanPath)) {
    require_once $kanbanPath;
}

use App\Filament\App\Resources\TaxistaAppointments\Widgets\TaxistaAppointmentsCalendar;
use App\Filament\App\Resources\Taxistas\Pages\ManageCitasTaxista;
use App\Filament\App\Resources\Taxistas\Pages\ManageConductoresTaxista;
use App\Filament\App\Resources\Taxistas\Pages\ManageDocumentosTaxista;
use App\Filament\App\Resources\Taxistas\Pages\ManageGastosTaxista;
use App\Filament\App\Resources\Taxistas\Pages\ManageTaxisTaxista;
use App\Filament\App\Resources\Taxistas\Pages\ManageTicketsTaxista;
use App\Filament\App\Resources\Taxistas\RelationManagers\AppointmentsRelationManager;
use App\Filament\App\Resources\Taxistas\RelationManagers\DocumentsRelationManager;
use App\Filament\App\Resources\Taxistas\RelationManagers\ExpensesRelationManager;
use App\Filament\App\Resources\Taxistas\RelationManagers\TaxisRelationManager;
use App\Filament\App\Resources\Taxistas\RelationManagers\TicketsRelationManager;
use App\Filament\App\Resources\Taxistas\Schemas\TaxistaForm;
use App\Filament\App\Resources\Taxistas\Schemas\TaxistaInfolist;
use App\Filament\App\Resources\Taxistas\Tables\TaxistasTable;
use App\Filament\App\Resources\Taxistas\Widgets\TaxistaDataAlerts;
use App\Filament\App\Resources\Taxistas\Widgets\TaxistaStats;
use App\Models\Taxista;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxistaResource extends Resource
{
    protected static ?string $model = Taxista::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $navigationLabel = 'Taxistas';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    protected static ?int $navigationSort = 3;

    protected static bool $isScopedToTenant = false;

    protected static ?\Filament\Pages\Enums\SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            //ViewTaxista::class,
            //EditTaxista::class,
            ManageTaxisTaxista::class,
            ManageConductoresTaxista::class,
            ManageCitasTaxista::class,
            ManageDocumentosTaxista::class,
            ManageGastosTaxista::class,
            ManageTicketsTaxista::class,
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return TaxistaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxistaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxistasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'type:id,label',
            'municipio:id,nombre',
        ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'name',
            'licencia',
            'nif',
            'municipio.nombre',
            'conductores.name',
            'taxis.license_plate',
        ];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $normalizedSearch = strtolower(preg_replace('/\s+/', '', $search));

        if ($normalizedSearch === '') {
            return;
        }

        $query->orWhereRaw("
            LOWER(
                CONCAT(
                    COALESCE(REGEXP_SUBSTR(COALESCE(users.licencia, ''), '[0-9]+'), ''),
                    CASE
                        WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) IN ('tías', 'tias') THEN 'ti'
                        WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'teguise' THEN 'te'
                        WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'yaiza' THEN 'ya'
                        WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'tinajo' THEN 'tn'
                        WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%tías%' OR LOWER(COALESCE(users.licencia, '')) LIKE '%tias%' THEN 'ti'
                        WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%teguise%' THEN 'te'
                        WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%yaiza%' THEN 'ya'
                        WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%tinajo%' THEN 'tn'
                        ELSE ''
                    END
                )
            ) LIKE ?
        ", ['%' . $normalizedSearch . '%']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Taxista $record */

        return TaxistaInfolist::globalSearchDetails($record);
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->leftJoin('municipios as global_search_municipios', 'users.municipio_id', '=', 'global_search_municipios.id')
            ->select('users.*')
            ->selectRaw("
                LOWER(
                    CONCAT(
                        COALESCE(REGEXP_SUBSTR(COALESCE(users.licencia, ''), '[0-9]+'), ''),
                        CASE
                            WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) IN ('tías', 'tias') THEN 'ti'
                            WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'teguise' THEN 'te'
                            WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'yaiza' THEN 'ya'
                            WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'tinajo' THEN 'tn'
                            WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%tías%' OR LOWER(COALESCE(users.licencia, '')) LIKE '%tias%' THEN 'ti'
                            WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%teguise%' THEN 'te'
                            WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%yaiza%' THEN 'ya'
                            WHEN LOWER(COALESCE(users.licencia, '')) LIKE '%tinajo%' THEN 'tn'
                            ELSE ''
                        END
                    )
                ) as search_shortcut
            ")
            ->with([
            'taxis:id,taxista_user_id,license_plate',
            'conductores:id,taxista_id,name',
            'municipio:id,nombre',
        ])->withCount([
            'taxis',
            'conductores',
            'appointments',
            'documents',
            'tickets',
        ]);
    }

    public static function getRelations(): array
    {
        return [
            TaxisRelationManager::class,
            AppointmentsRelationManager::class,
            DocumentsRelationManager::class,
            ExpensesRelationManager::class,
            TicketsRelationManager::class,
        ];
    }

    public static function getWidgets(): array
    {
        return [
            TaxistaDataAlerts::class,
            TaxistaStats::class,
            // TaxistaAppointmentsCalendar::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxistas::route('/'),
            'create' => CreateTaxista::route('/create'),
            'view' => ViewTaxista::route('/{record}'),
            //'edit' => EditTaxista::route('/{record}/edit'),
            'taxis' => ManageTaxisTaxista::route('/{record}/taxis'),
            'conductores' => ManageConductoresTaxista::route('/{record}/conductores'),
            'citas' => ManageCitasTaxista::route('/{record}/citas'),
            'documentos' => ManageDocumentosTaxista::route('/{record}/documentos'),
            'gastos' => ManageGastosTaxista::route('/{record}/gastos'),
            'tickets' => ManageTicketsTaxista::route('/{record}/tickets'),
            // 'documentos.kanban' => \App\Filament\App\Resources\Taxistas\Pages\ManageDocumentosKanbanTaxista::route('/{record}/documentos/kanban'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $modelClass = static::$model;

        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return (string)Cache::remember(
            "nav_badge:taxistas:{$panelId}",
            now()->addSeconds(20),
            static fn(): int => (int)$modelClass::count(),
        );
    }
}
