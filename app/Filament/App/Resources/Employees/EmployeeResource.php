<?php

namespace App\Filament\App\Resources\Employees;

use App\Filament\App\Resources\Employees\Pages\EditEmployee;
use App\Filament\App\Resources\Employees\Pages\ListEmployees;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeAttendances;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeCalendar;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeCitas;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeDocuments;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeSwapRequests;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeTickets;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeTimeOff;
use App\Filament\App\Resources\Employees\Pages\ManageEmployeeTurnos;
use App\Filament\App\Resources\Employees\Pages\ViewEmployee;
use App\Filament\App\Resources\Employees\Schemas\EmployeeForm;
use App\Filament\App\Resources\Employees\Schemas\EmployeeInfolist;
use App\Filament\App\Resources\Employees\Tables\EmployeesTable;
use App\Models\BookingDepartment;
use App\Models\EmployeeTimeOff;
use App\Models\ShiftSwapRequest;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaTicket;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema as DbSchema;
use Archilex\AdvancedTables\AdvancedTables;

class EmployeeResource extends Resource
{       use AdvancedTables;

    protected static ?string $model = User::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $navigationLabel = 'Empleados';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    protected static ?int $navigationSort = 1;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static bool $isScopedToTenant = false;

    public static function getEloquentQuery(): Builder
    {
        $usersTable = (new User())->getTable();

        $counts = [
            'employeeTimeOff',
            'shiftSwapRequests',
            'appointments',
            'documents',
            'tickets',
            'attendances',
            'employeeTimeOff as pending_timeoff_count' => fn($q) => $q->where('status', 'pending'),
            'shiftSwapRequests as pending_swaps_count' => fn($q) => $q->where('status', 'pending'),
            'appointments as pending_appointments_count' => fn($q) => $q->where('status', 'pendiente'),
            'tickets as open_tickets_count' => fn($q) => $q->whereNull('closed_at'),
        ];

        if (DbSchema::hasTable('employee_shifts') && DbSchema::hasColumn('employee_shifts', 'employee_id')) {
            array_unshift($counts, 'employeeShifts');
        }

        return parent::getEloquentQuery()
            ->with([
                'bookingDepartment',
                'todayEmployeeShift.centralTurno:id,name',
            ])
            ->addSelect([
                'managed_pending_timeoff_count' => EmployeeTimeOff::query()
                    ->selectRaw('count(*)')
                    ->where('status', 'pending')
                    ->whereIn('booking_department_id', BookingDepartment::query()
                        ->select('id')
                        ->whereColumn('created_by', $usersTable . '.id')),
                'managed_pending_swaps_count' => ShiftSwapRequest::query()
                    ->selectRaw('count(*)')
                    ->where('status', 'pending')
                    ->whereIn('booking_department_id', BookingDepartment::query()
                        ->select('id')
                        ->whereColumn('created_by', $usersTable . '.id')),
                'managed_pending_appointments_count' => TaxistaAppointment::query()
                    ->selectRaw('count(*)')
                    ->where('status', 'pendiente')
                    ->whereIn('booking_department_id', BookingDepartment::query()
                        ->select('id')
                        ->whereColumn('created_by', $usersTable . '.id')),
                'managed_open_tickets_count' => TaxistaTicket::query()
                    ->selectRaw('count(*)')
                    ->whereNull('closed_at')
                    ->whereIn('booking_department_id', BookingDepartment::query()
                        ->select('id')
                        ->whereColumn('created_by', $usersTable . '.id')),
            ])
            ->where('status', true)
            ->where(function (Builder $query): void {
                $query->where('role', 'empleado')->orWhere('role', 'admin')->orWhere('role', 'super')->orWhere('is_employee', true);
            })
            ->withCount($counts);
    }

    public static function form(Schema $schema): Schema
    {
        return EmployeeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return EmployeeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EmployeesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmployees::route('/'),
            'view' => ViewEmployee::route('/{record}'),
            //'edit' => EditEmployee::route('/{record}/edit'),
            'turnos' => ManageEmployeeTurnos::route('/{record}/turnos'),
            'calendario' => ManageEmployeeCalendar::route('/{record}/calendario'),
            'asistencias' => ManageEmployeeAttendances::route('/{record}/asistencias'),
            'citas' => ManageEmployeeCitas::route('/{record}/citas'),
            'documentos' => ManageEmployeeDocuments::route('/{record}/documentos'),
            'tickets' => ManageEmployeeTickets::route('/{record}/tickets'),
            'vacaciones' => ManageEmployeeTimeOff::route('/{record}/vacaciones'),
            'permisos' => ManageEmployeeSwapRequests::route('/{record}/permisos'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        $record = $page->getRecord();
        $dept = $record->bookingDepartment;

        $pages = [
            ViewEmployee::class,
            //EditEmployee::class,
            ManageEmployeeCitas::class,
            ManageEmployeeDocuments::class,
            ManageEmployeeTickets::class,
        ];

        if (!$dept || $dept->has_shifts_service) {
            $pages[] = ManageEmployeeTurnos::class;
            $pages[] = ManageEmployeeCalendar::class;
            $pages[] = ManageEmployeeTimeOff::class;
            $pages[] = ManageEmployeeSwapRequests::class;
        }

        if (!$dept || $dept->has_attendance_service) {
            $pages[] = ManageEmployeeAttendances::class;
        }

        return $page->generateNavigationItems($pages);
    }

    public static function getNavigationBadge(): ?string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return (string)Cache::remember(
            "nav_badge:employees:{$panelId}",
            now()->addSeconds(20),
            static fn(): int => (int)static::getEloquentQuery()->count(),
        );
    }
}
