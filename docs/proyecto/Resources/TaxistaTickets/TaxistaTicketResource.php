<?php

namespace App\Filament\App\Resources\TaxistaTickets;

use App\Filament\App\Resources\TaxistaTickets\Pages\CalendarTaxistaTickets;
use App\Filament\App\Resources\TaxistaTickets\Pages\CreateTaxistaTicket;
use App\Filament\App\Resources\TaxistaTickets\Pages\EditTaxistaTicket;
use App\Filament\App\Resources\TaxistaTickets\Pages\ViewTaxistaTicket;
use App\Filament\App\Resources\TaxistaTickets\Pages\ListTaxistaTickets;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use App\Filament\App\Resources\TaxistaTickets\Tables\TaxistaTicketsTable;
use App\Filament\App\Resources\TaxistaTickets\Widgets\TaxistaTicketStats;
use App\Models\TaxistaTicket;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class TaxistaTicketResource extends Resource
{
    protected static ?string $model = TaxistaTicket::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'Tickets';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Taxista';

    protected static ?int $navigationSort = 9;

    protected static bool $isScopedToTenant = false;

    public static function shouldRegisterNavigation(): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return auth()->user()?->departmentHasService('tickets') ?? false;
        }

        return DepartmentManagerAccess::canAccessService('has_tickets_service');
    }

    public static function form(Schema $schema): Schema
    {
        return TaxistaTicketForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxistaTicketsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query, 'user_id');
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'taxista_tickets.booking_department_id');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'description',
            'status',
            'priority',
            'ticket_type',
            'department.name',
            'user.name',
            'user.licencia',
            'user.municipio.nombre',
            'user.taxis.matricula',
        ];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $search = static::stripGlobalSearchPrefix($search, ['tic']);
        $normalizedSearch = static::normalizeShortcutSearch($search);

        if ($search === '' && $normalizedSearch === '') {
            return;
        }

        $query->orWhere(function (Builder $or) use ($search, $normalizedSearch): void {
            if ($search !== '') {
                $or->where('taxista_tickets.title', 'like', "%{$search}%")
                    ->orWhere('taxista_tickets.description', 'like', "%{$search}%")
                    ->orWhere('taxista_tickets.status', 'like', "%{$search}%")
                    ->orWhere('taxista_tickets.priority', 'like', "%{$search}%")
                    ->orWhere('taxista_tickets.ticket_type', 'like', "%{$search}%")
                    ->orWhere('global_search_departments.name', 'like', "%{$search}%")
                    ->orWhere('global_search_taxistas.name', 'like', "%{$search}%")
                    ->orWhere('global_search_taxistas.licencia', 'like', "%{$search}%")
                    ->orWhere('global_search_municipios.nombre', 'like', "%{$search}%");
            }

            if ($normalizedSearch !== '') {
                $or->orWhereRaw("
                    LOWER(
                        CONCAT(
                            COALESCE(REGEXP_SUBSTR(COALESCE(global_search_taxistas.licencia, ''), '[0-9]+'), ''),
                            CASE
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) IN ('tías', 'tias') THEN 'ti'
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'teguise' THEN 'te'
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'yaiza' THEN 'ya'
                                WHEN LOWER(COALESCE(global_search_municipios.nombre, '')) = 'tinajo' THEN 'tn'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%tías%' OR LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%tias%' THEN 'ti'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%teguise%' THEN 'te'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%yaiza%' THEN 'ya'
                                WHEN LOWER(COALESCE(global_search_taxistas.licencia, '')) LIKE '%tinajo%' THEN 'tn'
                                ELSE ''
                            END
                        )
                    ) LIKE ?
                ", ['%' . $normalizedSearch . '%'])
                    ->orWhereExists(function ($taxiQuery) use ($normalizedSearch): void {
                        $taxiQuery->selectRaw('1')
                            ->from('taxista_taxis')
                            ->whereColumn('taxista_taxis.taxista_user_id', 'taxista_tickets.user_id')
                            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(COALESCE(taxista_taxis.license_plate, ''), ' ', ''), '-', ''), '.', '')) LIKE ?", ['%' . $normalizedSearch . '%']);
                    })
                    ->orWhereExists(function ($taxiQuery) use ($normalizedSearch): void {
                        $taxiQuery->selectRaw('1')
                            ->from('taxis')
                            ->where(function ($taxiMatch): void {
                                $taxiMatch
                                    ->whereColumn('taxis.taxista_id', 'taxista_tickets.user_id')
                                    ->orWhereColumn('taxis.usuario_id', 'taxista_tickets.user_id');
                            })
                            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(COALESCE(taxis.matricula, ''), ' ', ''), '-', ''), '.', '')) LIKE ?", ['%' . $normalizedSearch . '%']);
                    });
            }
        });
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->leftJoin('users as global_search_taxistas', 'taxista_tickets.user_id', '=', 'global_search_taxistas.id')
            ->leftJoin('municipios as global_search_municipios', 'global_search_taxistas.municipio_id', '=', 'global_search_municipios.id')
            ->leftJoin('booking_departments as global_search_departments', 'taxista_tickets.booking_department_id', '=', 'global_search_departments.id')
            ->select('taxista_tickets.*')
            ->with([
                'department:id,name',
                'user:id,name,licencia,municipio_id',
                'user.municipio:id,nombre',
                'user.taxis:id,taxista_id,usuario_id,matricula',
            ]);
    }

    public static function getNavigationLabel(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Tickets';
        }

        return 'Tickets';
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
        if (PortalTaxistaContext::isPortalPanel()) {
            return parent::canView($record) && PortalTaxistaContext::canAccessTaxistaRecord($record, 'user_id');
        }

        return parent::canView($record) && DepartmentManagerAccess::canAccessDepartment((int) ($record->booking_department_id ?? 0));
    }

    public static function canEdit(Model $record): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return parent::canEdit($record) && PortalTaxistaContext::canAccessTaxistaRecord($record, 'user_id');
        }

        return parent::canEdit($record) && DepartmentManagerAccess::canAccessDepartment((int) ($record->booking_department_id ?? 0));
    }

    public static function canDelete(Model $record): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return parent::canDelete($record) && PortalTaxistaContext::canAccessTaxistaRecord($record, 'user_id');
        }

        return parent::canDelete($record) && DepartmentManagerAccess::canAccessDepartment((int) ($record->booking_department_id ?? 0));
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
            TaxistaTicketStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxistaTickets::route('/'),
            'calendar' => CalendarTaxistaTickets::route('/calendar'),
            'create' => CreateTaxistaTicket::route('/create'),
            'view' => ViewTaxistaTicket::route('/{record}'),
            'edit' => EditTaxistaTicket::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return null;
        }

        $modelClass = static::$model;
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';
        $scopeId = PortalTaxistaContext::isPortalPanel()
            ? 'taxista:' . (string) (PortalTaxistaContext::taxistaUserId() ?? 0)
            : 'user:' . (string) (auth()->id() ?? 0);

        return (string)Cache::remember(
            sprintf('nav_badge:%s:%s:%s', str_replace('\\', '.', (string)$modelClass), $panelId, $scopeId),
            now()->addSeconds(20),
            static function () use ($modelClass): int {
                $query = $modelClass::query();

                if (PortalTaxistaContext::isPortalPanel()) {
                    PortalTaxistaContext::scopeTaxistaRecordQuery($query, 'user_id');
                } else {
                    DepartmentManagerAccess::scopeManagedDepartments($query, column: 'taxista_tickets.booking_department_id');
                }

                return (int) $query->count();
            },
        );
    }

    private static function stripGlobalSearchPrefix(string $search, array $prefixes): string
    {
        $normalizedSearch = static::normalizeShortcutSearch($search);

        foreach ($prefixes as $prefix) {
            if (Str::startsWith($normalizedSearch, $prefix)) {
                $stripped = substr($normalizedSearch, strlen($prefix));

                return $stripped !== '' ? $stripped : trim($search);
            }
        }

        return trim($search);
    }

    private static function normalizeShortcutSearch(string $value): string
    {
        $ascii = Str::of($value)->ascii()->lower()->value();

        return (string) preg_replace('/[^a-z0-9]+/', '', $ascii);
    }
}
