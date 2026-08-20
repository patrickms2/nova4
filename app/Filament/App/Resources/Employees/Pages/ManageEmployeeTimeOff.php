<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\BookingDepartment;
use App\Models\EmployeeTimeOff;
use App\Services\Hrm\EmployeeTimeOffService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

class ManageEmployeeTimeOff extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'employeeTimeOff';

    protected static ?string $navigationLabel = 'Vacaciones';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->employeeTimeOff()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Solicitudes de vacaciones y permisos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva solicitud')
                ->fillForm(fn (): array => [
                    'employee_id' => (int) $this->getRecord()->id,
                    'user_id' => (int) $this->getRecord()->id,
                    'booking_department_id' => $this->getRecord()->booking_department_id,
                    'status' => EmployeeTimeOff::STATUS_PENDING,
                    'is_full_day' => true,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['employee_id'] = (int) $this->getRecord()->id;
                    $data['user_id'] = (int) $this->getRecord()->id;
                    $data['booking_department_id'] ??= $this->getRecord()->booking_department_id;

                    return $data;
                })
                ->using(fn (array $data, EmployeeTimeOffService $employeeTimeOffService): EmployeeTimeOff => $employeeTimeOffService->create($data)),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            Select::make('type')
                ->label('Tipo')
                ->required()
                ->options([
                    EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones',
                    EmployeeTimeOff::TYPE_BAJA => 'Baja médica',
                    EmployeeTimeOff::TYPE_PERSONAL => 'Día personal',
                    EmployeeTimeOff::TYPE_PERMISO => 'Permiso',
                ]),
            DatePicker::make('start_date')
                ->label('Fecha inicio')
                ->required(),
            DatePicker::make('end_date')
                ->label('Fecha fin')
                ->required()
                ->afterOrEqual('start_date'),
            Toggle::make('is_full_day')
                ->label('Día completo')
                ->default(true),
            Select::make('booking_department_id')
                ->label('Departamento')
                ->options(fn () => BookingDepartment::pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),
            Select::make('status')
                ->label('Estado')
                ->required()
                ->options([
                    EmployeeTimeOff::STATUS_PENDING => 'Pendiente',
                    EmployeeTimeOff::STATUS_APPROVED => 'Aprobada',
                    EmployeeTimeOff::STATUS_DENIED => 'Denegada',
                ]),
            Textarea::make('notes')
                ->label('Notas del empleado')
                ->nullable(),
            Textarea::make('review_notes')
                ->label('Notas de revisión')
                ->nullable()
                ->visible(fn (): bool => auth()->user()?->role === 'admin' || auth()->user()?->role === 'super_admin'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'vacaciones' => 'Vacaciones',
                        'baja' => 'Baja médica',
                        'personal' => 'Día personal',
                        'permiso' => 'Permiso',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'vacaciones' => 'primary',
                        'baja' => 'danger',
                        'personal' => 'info',
                        'permiso' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('start_date')
                    ->label('Desde')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Hasta')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('duration_days')
                    ->label('Días')
                    ->badge()
                    ->color('info')
                    ->state(fn ($record): int => $record->getDurationDays()),
                IconColumn::make('is_full_day')
                    ->label('Día completo')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'success',
                        'denied' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobada',
                        'denied' => 'Denegada',
                        default => ucfirst($state),
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Revisado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        if (isset($data['status']) && $data['status'] !== 'pending') {
                            $data['reviewed_by'] = auth()->id();
                            $data['reviewed_at'] = now()->toDateTimeString();
                        }

                        return $data;
                    })
                    ->using(fn (EmployeeTimeOff $record, array $data, EmployeeTimeOffService $employeeTimeOffService): EmployeeTimeOff => $employeeTimeOffService->update($record, $data)),
                DeleteAction::make(),

                \Filament\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->isPending())
                    ->action(function (EmployeeTimeOff $record, EmployeeTimeOffService $employeeTimeOffService): void {
                        $employeeTimeOffService->approve($record, (int) auth()->id());
                    }),

                \Filament\Actions\Action::make('deny')
                    ->label('Denegar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => $record->isPending())
                    ->action(function (EmployeeTimeOff $record, EmployeeTimeOffService $employeeTimeOffService): void {
                        $employeeTimeOffService->deny($record, (int) auth()->id());
                    }),
            ])
            ->defaultSort('start_date', 'desc');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Todas')
                ->badge(fn (): int => $this->getRecord()->employeeTimeOff()->count()),

            'pending' => Tab::make()
                ->label('Pendientes')
                ->badge(fn (): int => $this->getRecord()->employeeTimeOff()->where('status', 'pending')->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending')),

            'approved' => Tab::make()
                ->label('Aprobadas')
                ->badge(fn (): int => $this->getRecord()->employeeTimeOff()->where('status', 'approved')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'approved')),

            'denied' => Tab::make()
                ->label('Denegadas')
                ->badge(fn (): int => $this->getRecord()->employeeTimeOff()->where('status', 'denied')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'denied')),
        ];
    }
}
