<?php

namespace App\Filament\App\Resources\BookingDepartments;

use App\Filament\App\Resources\BookingDepartments\Pages\CreateBookingDepartment;
use App\Filament\App\Resources\BookingDepartments\Pages\EditBookingDepartment;
use App\Filament\App\Resources\BookingDepartments\Pages\ListBookingDepartments;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentCalendario;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentCitas;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentDocuments;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentEmployees;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentTickets;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentTurnos;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageDepartmentVacaciones;
use App\Filament\App\Resources\BookingDepartments\Pages\ManageTaxistas;
use App\Filament\App\Resources\BookingDepartments\Pages\ViewBookingDepartment;
use App\Filament\App\Resources\BookingDepartments\Schemas\BookingDepartmentForm;
use App\Filament\App\Resources\BookingDepartments\Schemas\BookingDepartmentInfolist;
use App\Filament\App\Resources\BookingDepartments\Tables\BookingDepartmentsTable;
use App\Filament\App\Resources\Tiposdocs\ManageDepartmentTaxistas;
use App\Models\BookingDepartment;
use App\Support\DepartmentManagerAccess;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class BookingDepartmentResource extends Resource
{
    protected static ?string $model = BookingDepartment::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Departamentos';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    protected static ?int $navigationSort = 1;

    protected static bool $isScopedToTenant = false;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getEloquentQuery(): Builder
    {
        return DepartmentManagerAccess::scopeManagedDepartments(parent::getEloquentQuery())
            ->withCount(['taxistas', 'employees', 'employeeShifts', 'appointments', 'documents', 'tickets', 'schedules', 'shiftSchedules']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var BookingDepartment $record */

        $details = [
            'Encargado' => (string) ($record->creator?->name ?: '—'),
        ];

        $summaryCounts = [
            'Empleados' => $record->employees_count ?? $record->employees()->count(),
            'Taxistas' => $record->taxistas_count ?? $record->taxistas()->count(),
            'Turnos' => $record->employee_shifts_count ?? $record->employeeShifts()->count(),
            'Citas' => $record->appointments_count ?? $record->appointments()->count(),
            'Tickets' => $record->tickets_count ?? $record->tickets()->count(),
        ];

        foreach ($summaryCounts as $label => $count) {
            if ((int) $count > 0) {
                $details[$label] = (int) $count;
            }
        }

        return $details;
    }

    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return static::getEloquentQuery()->with([
            'creator:id,name',
        ]);
    }

    public static function form(Schema $schema): Schema
    {
        return BookingDepartmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingDepartmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingDepartmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookingDepartments::route('/'),
            'create' => CreateBookingDepartment::route('/create'),
            'view' => ViewBookingDepartment::route('/{record}'),
            'edit' => EditBookingDepartment::route('/{record}/edit'),
            'empleados' => ManageDepartmentEmployees::route('/{record}/empleados'),
            'taxistas' => ManageTaxistas::route('/{record}/taxistas'),
            'turnos' => ManageDepartmentTurnos::route('/{record}/turnos'),
            'calendario' => ManageDepartmentCalendario::route('/{record}/calendario'),
            'vacaciones' => ManageDepartmentVacaciones::route('/{record}/vacaciones'),
            'citas' => ManageDepartmentCitas::route('/{record}/citas'),
            'documentos' => ManageDepartmentDocuments::route('/{record}/documentos'),
            'tickets' => ManageDepartmentTickets::route('/{record}/tickets'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();

        $pages = [
            //ViewBookingDepartment::class,
            //EditBookingDepartment::class,
        ];

        if ($record->has_shifts_service) {
            $pages[] = ManageDepartmentEmployees::class;
            $pages[] = ManageDepartmentTurnos::class;
            $pages[] = ManageDepartmentCalendario::class;
            $pages[] = ManageDepartmentVacaciones::class;
        }

        if ($record->has_meetings_service) {
            $pages[] = ManageDepartmentCitas::class;
        }

        if ($record->has_documents_service) {
            $pages[] = ManageDepartmentDocuments::class;
        }

        if ($record->has_tickets_service) {
            $pages[] = ManageDepartmentTickets::class;
        }

        if ($record->has_taxistas_service) {
            $pages[] = ManageTaxistas::class;
        }

        return $page->generateNavigationItems($pages);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) DepartmentManagerAccess::scopeManagedDepartments(static::$model::query())->count();
    }
}
