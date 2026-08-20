<?php

namespace App\Filament\App\Resources\TaxistaAppointments;

use App\Filament\App\Resources\TaxistaAppointments\Pages\CalendarTaxistaAppointments;
use App\Filament\App\Resources\TaxistaAppointments\Pages\CreateTaxistaAppointment;
use App\Filament\App\Resources\TaxistaAppointments\Pages\EditTaxistaAppointment;
use App\Filament\App\Resources\TaxistaAppointments\Pages\KanbanTaxistaAppointments;
use App\Filament\App\Resources\TaxistaAppointments\Pages\ListTaxistaAppointments;
use App\Filament\App\Resources\TaxistaAppointments\Pages\ViewTaxistaAppointment;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\Tables\TaxistaAppointmentsTable;
use App\Filament\App\Resources\TaxistaAppointments\Widgets\TaxistaAppointmentStats;
use App\Models\TaxistaAppointment;
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

class TaxistaAppointmentResource extends Resource
{
    protected static ?string $model = TaxistaAppointment::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'title';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Citas';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Taxista';

    protected static ?int $navigationSort = 6;

    protected static bool $isScopedToTenant = false;

    public static function shouldRegisterNavigation(): bool
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return auth()->user()?->departmentHasService('meetings') ?? false;
        }

        return DepartmentManagerAccess::canAccessService('has_meetings_service');
    }

    public static function form(Schema $schema): Schema
    {
        return TaxistaAppointmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TaxistaAppointmentsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (PortalTaxistaContext::isPortalPanel()) {
            return PortalTaxistaContext::scopeTaxistaRecordQuery($query);
        }

        return DepartmentManagerAccess::scopeManagedDepartments($query, column: 'taxista_appointments.booking_department_id');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [
            'title',
            'status',
            'department.name',
            'tipo.nombre',
            'taxista.name',
            'taxista.licencia',
            'taxista.municipio.nombre',
            'taxista.taxis.license_plate',
        ];
    }

    public static function modifyGlobalSearchQuery(Builder $query, string $search): void
    {
        $search = static::stripGlobalSearchPrefix($search, ['cit']);
        $normalizedSearch = static::normalizeShortcutSearch($search);

        if ($search === '' && $normalizedSearch === '') {
            return;
        }

        $query->orWhere(function (Builder $or) use ($search, $normalizedSearch): void {
            if ($search !== '') {
                $or->where('taxista_appointments.title', 'like', "%{$search}%")
                    ->orWhere('taxista_appointments.status', 'like', "%{$search}%")
                    ->orWhere('global_search_departments.name', 'like', "%{$search}%")
                    ->orWhere('global_search_tipos.nombre', 'like', "%{$search}%")
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
                            ->whereColumn('taxista_taxis.taxista_user_id', 'taxista_appointments.taxista_user_id')
                            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(COALESCE(taxista_taxis.license_plate, ''), ' ', ''), '-', ''), '.', '')) LIKE ?", ['%' . $normalizedSearch . '%']);
                    })
                    ->orWhereExists(function ($taxiQuery) use ($normalizedSearch): void {
                        $taxiQuery->selectRaw('1')
                            ->from('taxis')
                            ->where(function ($taxiMatch): void {
                                $taxiMatch
                                    ->whereColumn('taxis.taxista_id', 'taxista_appointments.taxista_user_id')
                                    ->orWhereColumn('taxis.usuario_id', 'taxista_appointments.taxista_user_id');
                            })
                            ->whereRaw("LOWER(REPLACE(REPLACE(REPLACE(COALESCE(taxis.matricula, ''), ' ', ''), '-', ''), '.', '')) LIKE ?", ['%' . $normalizedSearch . '%']);
                    });
            }
            
        });
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->leftJoin('users as global_search_taxistas', 'taxista_appointments.taxista_user_id', '=', 'global_search_taxistas.id')
            ->leftJoin('municipios as global_search_municipios', 'global_search_taxistas.municipio_id', '=', 'global_search_municipios.id')
            ->leftJoin('booking_departments as global_search_departments', 'taxista_appointments.booking_department_id', '=', 'global_search_departments.id')
            ->leftJoin('tipos_citas as global_search_tipos', 'taxista_appointments.tipo_cita_id', '=', 'global_search_tipos.id')
            ->select('taxista_appointments.*')
            ->with([
                'taxista:id,name,licencia,municipio_id',
                'taxista.municipio:id,nombre',
                'taxista.taxis:id,taxista_user_id,license_plate',
                'department:id,name',
                'tipo:id,nombre',
            ]);
    }

    public static function getNavigationLabel(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Citas';
        }

        return 'Citas';
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        /** @var TaxistaAppointment $record */

        $department = (string) ($record->department?->name ?? 'Sin departamento');
        $date = ($record->starts_at ?? null)?->format('d/m/Y') ?? 'Sin fecha';
        $status = filled($record->status) ? Str::headline((string) $record->status) : 'Sin estado';

        return "{$department} - {$date} - {$status}";
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var TaxistaAppointment $record */

        return array_filter([
            'Hora' => ($record->starts_at ?? null)?->format('H:i') ?: null,
            'Tipo' => $record->tipo?->nombre,
            'Taxista' => $record->taxista?->name,
        ]);
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
            TaxistaAppointmentStats::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxistaAppointments::route('/'),
            'calendar' => CalendarTaxistaAppointments::route('/calendar'),
            'kanban' => KanbanTaxistaAppointments::route('/kanban'),
            //'create' => CreateTaxistaAppointment::route('/create'),
            'view' => ViewTaxistaAppointment::route('/{record}'),
            'edit' => EditTaxistaAppointment::route('/{record}/edit'),
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
                    PortalTaxistaContext::scopeTaxistaRecordQuery($query);
                } else {
                    DepartmentManagerAccess::scopeManagedDepartments($query, column: 'taxista_appointments.booking_department_id');
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
