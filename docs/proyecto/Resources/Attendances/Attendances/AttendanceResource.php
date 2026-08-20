<?php

namespace App\Filament\App\Resources\Attendances\Attendances;

use App\Filament\Support\baseresource;
use App\Models\Taxi\Attendance;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Widgets;
use App\Filament\App\Resources\Attendances\Widgets\CalendarioWidget;
use Filament\Tables\Table;
use Filament\Schemas\Schema as Form;
use App\Filament\App\Resources\Attendances\TableSchema;
use App\Filament\App\Resources\Attendances\FormSchema;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;

class AttendanceResource extends baseresource
{
    protected static ?string $model = Attendance::class;
    protected static string|UnitEnum|null $navigationGroup = 'Servicios de Empleados';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static ?int $navigationSort = 2;
    protected static bool $isScopedToTenant = false; // Added this line
    protected static bool $shouldRegisterNavigation = false;
    //  protected static string | UnitEnum | null $navigationGroup = 'Departamentos';

    //protected static string | BackedEnum | null $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = "Reg Asistencias";
    protected static ?string $modelLabel = 'Asistencia';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public static function table(Table $table): Table
    {
        return TableSchema::table($table);
    }

    public static function form(Form $form): Form
    {
        return FormSchema::form($form);
    }

    public static function getRelations(): array
    {
        return [
        ];
    }

    public static function getWidgets(): array
    {
        return [
            CalendarioWidget::class,
        ];
    }

    public function checkIn(): void
    {
        $data = $this->form->getState();

        $employeeId = (int)($data['employee_id'] ?? 0);

        $existingAttendance = Attendance::where('usuario_id', $employeeId)
            ->whereDate('date', $data['date'])
            ->first();

        if ($existingAttendance) {
            Notification::make()
                ->title('Already Checked In')
                ->body('This employee has already checked in today.')
                ->warning()
                ->send();
            return;
        }

        Attendance::create([
            'employee_id' => $employeeId,
            'date' => $data['date'],
            'startDate' => now(),
            'description' => $data['description'] ?? null,
        ]);

        Notification::make()
            ->title('Check-in Successful')
            ->body('Employee checked in at ' . now()->format('h:i A'))
            ->success()
            ->send();

        $this->form->fill();
    }

    public function checkOut(): void
    {
        $data = $this->form->getState();
        $employeeId = (int)($data['employee_id'] ?? 0);

        $attendance = Attendance::where('usuario_id', $employeeId)
            ->whereDate('date', $data['date'])
            ->whereNull('endDate')
            ->first();

        if (!$attendance) {
            Notification::make()
                ->title('No Check-in Found')
                ->body('Employee must check in first before checking out.')
                ->warning()
                ->send();
            return;
        }

        $attendance->update([
            'endDate' => now(),
            'description' => $data['description'] ?? $attendance->description,
        ]);

        Notification::make()
            ->title('Check-out Successful')
            ->body('Employee checked out at ' . now()->format('h:i A'))
            ->success()
            ->send();

        $this->form->fill();
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\App\Resources\Attendances\Pages\ListAttendances::route('/'),
        ];
    }
}
