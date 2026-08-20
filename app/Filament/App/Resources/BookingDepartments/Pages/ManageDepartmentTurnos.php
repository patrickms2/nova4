<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Models\EmployeeShift;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

class ManageDepartmentTurnos extends ManageRelatedRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static string $relationship = 'employeeShifts';

    protected static ?string $navigationLabel = 'Turnos';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 5;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo turno')
                ->fillForm(fn (): array => [
                    'booking_department_id' => (int) $this->getRecord()->id,
                    'status' => EmployeeShift::STATUS_PLANNED,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['booking_department_id'] = (int) $this->getRecord()->id;

                    return $data;
                }),
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn (): string => view('components.employee-help-popup-content', ['page' => 'department-turnos'])->render())
                ->modalHeading('Ayuda - Turnos del Departamento')
                ->modalFooterActions([
                    Action::make('close')
                        ->label('Entendido')
                        ->color('primary')
                        ->close(),
                ]),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->employeeShifts()
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
        return 'Turnos de empleados en este departamento.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('employee_id')
                ->label('Empleado')
                ->options(fn () => User::where('status', true)
                    ->where(fn ($q) => $q->where('role', 'empleado')->orWhere('is_employee', true))
                    ->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),
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
                TextColumn::make('employee.name')
                    ->label('Empleado')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('shift_code')
                    ->label('Turno')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'M' => 'info',
                        'P' => 'warning',
                        'N' => 'gray',
                        'L' => 'success',
                        'V' => 'primary',
                        'B' => 'danger',
                        'S' => 'gray',
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
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planned' => 'warning',
                        'confirmed' => 'success',
                        'locked' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'planned' => 'Planificado',
                        'confirmed' => 'Confirmado',
                        'locked' => 'Bloqueado',
                        default => $state,
                    }),
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
}
