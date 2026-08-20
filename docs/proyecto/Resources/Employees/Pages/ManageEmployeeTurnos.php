<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

class ManageEmployeeTurnos extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'employeeShifts';

    protected static ?string $navigationLabel = 'Turnos';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string)$record->employeeShifts()
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Turnos asignados al empleado.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('empleados')
                ->label('Empleados')
                ->icon('heroicon-s-user')
                ->color('primary')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/employees';
                }),
            CreateAction::make()
                ->label('Nuevo turno')
                ->fillForm(fn(): array => [
                    'employee_id' => (int)$this->getRecord()->id,
                    'status' => EmployeeShift::STATUS_PLANNED,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['employee_id'] = (int)$this->getRecord()->id;

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            DatePicker::make('date')
                ->label('Fecha')
                ->required(),
            Select::make('shift_code')
                ->label('Turno')
                ->required()
                ->options([
                    EmployeeShift::SHIFT_MANANA => 'Mañana (M)',
                    EmployeeShift::SHIFT_PARTIDO => 'Partido (P)',
                    EmployeeShift::SHIFT_NOCHE => 'Noche (N)',
                    EmployeeShift::SHIFT_LIBRE => 'Libre (L)',
                    EmployeeShift::SHIFT_VACACIONES => 'Vacaciones (V)',
                    EmployeeShift::SHIFT_BAJA => 'Baja (B)',
                    EmployeeShift::SHIFT_SALIENTE => 'Saliente (S)',
                ]),
            Select::make('booking_department_id')
                ->label('Departamento')
                ->options(fn() => BookingDepartment::pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('status')
                ->label('Estado')
                ->required()
                ->options([
                    EmployeeShift::STATUS_PLANNED => 'Planificado',
                    EmployeeShift::STATUS_CONFIRMED => 'Confirmado',
                    EmployeeShift::STATUS_LOCKED => 'Bloqueado',
                ]),
            Textarea::make('notes')
                ->label('Notas')
                ->nullable(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('shift_code')
                    ->label('Turno')
                    ->state(fn (EmployeeShift $record): string => $this->displayShiftState($record))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'M' => 'info',
                        'P' => 'warning',
                        'N' => 'gray',
                        'L' => 'success',
                        'V' => 'primary',
                        'B' => 'danger',
                        'S' => 'gray',
                        'Permiso aprobado' => 'success',
                        'Día personal aprobado' => 'success',
                        'Vacaciones aprobadas' => 'primary',
                        'Baja aprobada' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'M' => 'Mañana',
                        'P' => 'Partido',
                        'N' => 'Noche',
                        'L' => 'Libre',
                        'V' => 'Vacaciones',
                        'B' => 'Baja',
                        'S' => 'Saliente',
                        default => $state,
                    }),
                TextColumn::make('bookingDepartment.name')
                    ->label('Departamento')
                    ->badge()
                    ->placeholder('Sin departamento'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'planned' => 'warning',
                        'confirmed' => 'success',
                        'locked' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'planned' => 'Planificado',
                        'confirmed' => 'Confirmado',
                        'locked' => 'Bloqueado',
                        default => $state,
                    }),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('shift_code')
                    ->label('Turno')
                    ->options([
                        'M' => 'Mañana',
                        'P' => 'Partido',
                        'N' => 'Noche',
                        'L' => 'Libre',
                        'V' => 'Vacaciones',
                        'B' => 'Baja',
                        'S' => 'Saliente',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'planned' => 'Planificado',
                        'confirmed' => 'Confirmado',
                        'locked' => 'Bloqueado',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('date', 'desc');
    }

    private function displayShiftState(EmployeeShift $shift): string
    {
        $approvedTimeOff = EmployeeTimeOff::query()
            ->approved()
            ->where('employee_id', $shift->employee_id)
            ->whereDate('start_date', '<=', $shift->date)
            ->whereDate('end_date', '>=', $shift->date)
            ->first(['type']);

        if (! $approvedTimeOff) {
            return (string) $shift->shift_code;
        }

        return match ((string) $approvedTimeOff->type) {
            EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones aprobadas',
            EmployeeTimeOff::TYPE_BAJA => 'Baja aprobada',
            EmployeeTimeOff::TYPE_PERSONAL => 'Día personal aprobado',
            default => 'Permiso aprobado',
        };
    }
}
