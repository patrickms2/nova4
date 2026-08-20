<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

class ManageDepartmentEmployees extends ManageRelatedRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static string $relationship = 'employees';

    protected static ?string $navigationLabel = 'Empleados';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?int $navigationSort = 4;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('create_employee')
                ->label('Crear empleado')
                ->icon('heroicon-s-plus')
                ->form([
                    \Filament\Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->string()
                        ->maxLength(255),

                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    \Filament\Forms\Components\TextInput::make('phone')
                        ->label('Teléfono')
                        ->maxLength(50),

                    \Filament\Forms\Components\TextInput::make('nif')
                        ->label('NIF')
                        ->maxLength(50),

                    \Filament\Forms\Components\TextInput::make('employee_code')
                        ->label('Código empleado')
                        ->maxLength(50),

                    \Filament\Forms\Components\DatePicker::make('employment_started_at')
                        ->label('Alta')
                        ->native(false),

                    \Filament\Forms\Components\Hidden::make('booking_department_id')
                        ->default($this->getRecord()->id),

                    \Filament\Forms\Components\Toggle::make('status')
                        ->label('Activo')
                        ->required()
                        ->default(true),
                ])
                ->action(function (array $data) {
                    $user = \App\Models\User::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'nif' => $data['nif'] ?? null,
                        'employee_code' => $data['employee_code'] ?? null,
                        'employment_started_at' => $data['employment_started_at'] ?? null,
                        'booking_department_id' => $data['booking_department_id'],
                        'status' => $data['status'],
                        'role' => 'empleado',
                        'is_employee' => true,
                        'password' => \Illuminate\Support\Facades\Hash::make('password'), // Default password, should be changed
                    ]);

                    \Filament\Notifications\Notification::make()
                        ->title('Empleado creado')
                        ->body('El empleado ha sido creado y asociado al departamento.')
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('associate_employees')
                ->label('Asociar empleados existentes')
                ->icon('heroicon-s-user-plus')
                ->form([
                    \Filament\Forms\Components\CheckboxList::make('employee_ids')
                        ->label('Seleccionar empleados')
                        ->required()
                        ->options(function () {
                            return \App\Models\User::where('status', true)
                                ->where(function ($query) {
                                    $query->where('role', 'empleado')
                                        ->orWhere('role', 'admin')
                                        ->orWhere('role', 'super')
                                        ->orWhere('is_employee', true);
                                })
                                ->where(function ($query) {
                                    $query->whereNull('booking_department_id')
                                        ->orWhere('booking_department_id', '!=', $this->getRecord()->id);
                                })
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->bulkToggleable()
                        ->columns(1),
                ])
                ->action(function (array $data) {
                    $department = $this->getRecord();
                    $employeeIds = $data['employee_ids'];
                    
                    \App\Models\User::whereIn('id', $employeeIds)
                        ->update(['booking_department_id' => $department->id]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Empleados asociados')
                        ->body(count($employeeIds) . ' empleados han sido asociados al departamento.')
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn (): string => view('components.employee-help-popup-content', ['page' => 'department-employees'])->render())
                ->modalHeading('Ayuda - Empleados del Departamento')
                ->modalFooterActions([
                    \Filament\Actions\Action::make('close')
                        ->label('Entendido')
                        ->color('primary')
                        ->close(),
                ]),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->employees()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Empleados asignados a este departamento.';
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('employee_code')
                    ->label('Codigo')
                    ->searchable()
                    ->placeholder('-'),

                TextColumn::make('email')
                    ->label('Email')
                    ->toggleable(),

                IconColumn::make('status')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('employment_started_at')
                    ->label('Alta')
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->defaultSort('name');
    }
}
