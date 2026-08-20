<?php

namespace App\Filament\App\Resources\TaxistaTaxis;

use App\Filament\App\Resources\TaxistaTaxis\Pages\CreateTaxistaTaxi;
use App\Filament\App\Resources\TaxistaTaxis\Pages\EditTaxistaTaxi;
use App\Filament\App\Resources\TaxistaTaxis\Pages\ListTaxistaTaxis;
use App\Filament\App\Resources\TaxistaTaxis\Schemas\TaxistaTaxiForm;
use App\Filament\App\Resources\TaxistaTaxis\Tables\TaxistaTaxisTable;
use App\Filament\App\Resources\TaxistaTaxis\Widgets\TaxistaTaxiStats;
use App\Models\TaxistaTaxi;
use App\Support\PortalTaxistaContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxistaTaxiResource extends Resource
{
    protected static ?string $model = TaxistaTaxi::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'license_plate';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Taxis';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Taxista';

    protected static ?int $navigationSort = 8;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return TaxistaTaxiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxistaTaxisTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
    }

    public static function canViewAny(): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::taxistaUserId() !== null;
        }

        return parent::canViewAny();
    }

    public static function getNavigationGroup(): \UnitEnum|string|null
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Taxista';
        }

        return static::$navigationGroup;
    }

    public static function canCreate(): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::taxistaUserId() !== null;
        }

        return parent::canCreate();
    }

    public static function canView(Model $record): bool
    {
        return parent::canView($record) && PortalTaxistaContext::canAccessTaxistaRecord($record);
    }

    public static function canEdit(Model $record): bool
    {
        return parent::canEdit($record) && PortalTaxistaContext::canAccessTaxistaRecord($record);
    }

    public static function canDelete(Model $record): bool
    {
        return parent::canDelete($record) && PortalTaxistaContext::canAccessTaxistaRecord($record);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return [];
        }

        return [
            TaxistaTaxiStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxistaTaxis::route('/'),
            'create' => CreateTaxistaTaxi::route('/create'),
            'edit' => EditTaxistaTaxi::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return null;
        }

        $modelClass = static::$model;
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel() ? (string) (PortalTaxistaContext::taxistaUserId() ?? 0) : 'all';

        return (string) Cache::remember(
            sprintf('nav_badge:%s:%s:%s', str_replace('\\', '.', (string) $modelClass), $panelId, $scopeId),
            now()->addSeconds(20),
            static function () use ($modelClass): int {
                $query = $modelClass::query();
                PortalTaxistaContext::scopeTaxistaRecordQuery($query);

                return (int) $query->count();
            },
        );
    }
}
