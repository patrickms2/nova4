<?php

namespace App\Filament\App\Resources\TaxistaExpenses;

use App\Filament\App\Resources\TaxistaExpenses\Pages\CreateTaxistaExpense;
use App\Filament\App\Resources\TaxistaExpenses\Pages\EditTaxistaExpense;
use App\Filament\App\Resources\TaxistaExpenses\Pages\ListTaxistaExpenses;
use App\Filament\App\Resources\TaxistaExpenses\Pages\ManageTaxistaExpensePayments;
use App\Filament\App\Resources\TaxistaExpenses\Pages\ViewTaxistaExpense;
use App\Filament\App\Resources\TaxistaExpenses\Schemas\TaxistaExpenseForm;
use App\Filament\App\Resources\TaxistaExpenses\Schemas\TaxistaExpenseInfolist;
use App\Filament\App\Resources\TaxistaExpenses\Tables\TaxistaExpensesTable;
use App\Filament\App\Resources\TaxistaExpenses\Widgets\TaxistaExpenseStats;
use App\Models\TaxistaExpense;
use App\Support\PortalTaxistaContext;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxistaExpenseResource extends Resource
{
    protected static ?string $model = TaxistaExpense::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Gastos';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Taxista';

    protected static ?int $navigationSort = 5;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected static bool $isScopedToTenant = false;

    public static function form(Schema $schema): Schema
    {
        return TaxistaExpenseForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return TaxistaExpenseInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxistaExpensesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
    }

    public static function getNavigationLabel(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Gastos';
        }

        return 'Gastos';
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
            TaxistaExpenseStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxistaExpenses::route('/'),
            'create' => CreateTaxistaExpense::route('/create'),
            'view' => ViewTaxistaExpense::route('/{record}'),
            'edit' => EditTaxistaExpense::route('/{record}/edit'),
            'payments' => ManageTaxistaExpensePayments::route('/{record}/payments'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewTaxistaExpense::class,
            EditTaxistaExpense::class,
            ManageTaxistaExpensePayments::class,
        ]);
    }

    public static function getNavigationBadge(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return null;
        }

        $modelClass = static::$model;
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel() ? (string)(PortalTaxistaContext::taxistaUserId() ?? 0) : 'all';

        return (string)Cache::remember(
            sprintf('nav_badge:%s:%s:%s', str_replace('\\', '.', (string)$modelClass), $panelId, $scopeId),
            now()->addSeconds(20),
            static function () use ($modelClass): int {
                $query = $modelClass::query();
                PortalTaxistaContext::scopeTaxistaRecordQuery($query);

                return (int)$query->count();
            },
        );
    }
}
