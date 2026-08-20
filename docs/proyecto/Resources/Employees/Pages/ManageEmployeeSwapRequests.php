<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\Hrm\ShiftSwapService;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

class ManageEmployeeSwapRequests extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'shiftSwapRequests';

    protected static ?string $navigationLabel = 'Permisos';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 9;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->shiftSwapRequests()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Solicitudes de intercambio de turno y permisos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva solicitud')
                ->fillForm(fn (): array => [
                    'requester_user_id' => (int) $this->getRecord()->id,
                    'booking_department_id' => $this->getRecord()->booking_department_id,
                    'status' => ShiftSwapRequest::STATUS_PENDING,
                    'type' => ShiftSwapRequest::TYPE_SWAP,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['requester_user_id'] = (int) $this->getRecord()->id;

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        $recordId = $this->getRecord()->id;

        return $schema->schema([
            Select::make('type')
                ->label('Tipo')
                ->required()
                ->options([
                    ShiftSwapRequest::TYPE_SWAP => 'Intercambio',
                    ShiftSwapRequest::TYPE_COVER => 'Cobertura',
                    ShiftSwapRequest::TYPE_DAYOFF => 'Día libre',
                ]),
            Select::make('target_user_id')
                ->label('Compañero')
                ->required()
                ->options(fn (callable $get): array => app(ShiftSwapService::class)->getAvailableTargetsForDate(
                    requesterUserId: $recordId,
                    swapDate: (string) ($get('swap_date') ?: now()->toDateString()),
                    departmentId: (int) ($get('booking_department_id') ?: $this->getRecord()->booking_department_id),
                ))
                ->searchable()
                ->preload()
                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($recordId): void {
                    if (! filled($state) || ! filled($get('swap_date'))) {
                        $set('target_shift_id', null);

                        return;
                    }

                    $options = app(ShiftSwapService::class)->getAvailableTargetShifts(
                        targetUserId: (int) $state,
                        swapDate: (string) $get('swap_date'),
                    );

                    $set('target_shift_id', $options !== [] ? (int) array_key_first($options) : null);
                })
                ->live(),
            DatePicker::make('swap_date')
                ->label('Fecha del turno')
                ->required()
                ->afterStateUpdated(function ($state, Set $set, Get $get) use ($recordId): void {
                    if (! filled($state)) {
                        $set('requester_shift_id', null);
                        $set('target_shift_id', null);

                        return;
                    }

                    $requesterShiftOptions = app(ShiftSwapService::class)->getRequesterShiftOptions(
                        requesterUserId: $recordId,
                        swapDate: (string) $state,
                    );

                    $set('requester_shift_id', $requesterShiftOptions !== [] ? (int) array_key_first($requesterShiftOptions) : null);

                    if (! filled($get('target_user_id'))) {
                        $set('target_shift_id', null);

                        return;
                    }

                    $targetShiftOptions = app(ShiftSwapService::class)->getAvailableTargetShifts(
                        targetUserId: (int) $get('target_user_id'),
                        swapDate: (string) $state,
                    );

                    $set('target_shift_id', $targetShiftOptions !== [] ? (int) array_key_first($targetShiftOptions) : null);
                })
                ->live(),
            DatePicker::make('return_date')
                ->label('Fecha devolución')
                ->afterOrEqual('swap_date')
                ->nullable(),
            Select::make('requester_shift_id')
                ->label('Tu turno')
                ->options(fn (callable $get) => EmployeeShift::query()
                    ->where('employee_id', $recordId)
                    ->when($get('swap_date'), fn (Builder $query, string $swapDate): Builder => $query->whereDate('date', $swapDate))
                    ->with('centralTurno')
                    ->get()
                    ->mapWithKeys(fn ($s) => [$s->id => $s->date?->format('d/m') . ' — ' . ($s->centralTurno?->name ?? EmployeeShift::shiftLabel((string) $s->shift_code))]))
                ->searchable()
                ->nullable()
                ->live(),
            Select::make('target_shift_id')
                ->label('Turno del compañero')
                ->options(fn (callable $get): array => filled($get('target_user_id')) && filled($get('swap_date'))
                    ? app(ShiftSwapService::class)->getAvailableTargetShifts(
                        targetUserId: (int) $get('target_user_id'),
                        swapDate: (string) $get('swap_date'),
                    )
                    : [])
                ->searchable()
                ->nullable(),
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
                    ShiftSwapRequest::STATUS_PENDING => 'Pendiente',
                    ShiftSwapRequest::STATUS_TARGET_ACCEPTED => 'Aceptado por compañero',
                    ShiftSwapRequest::STATUS_APPROVED => 'Aprobado',
                    ShiftSwapRequest::STATUS_DENIED => 'Denegado',
                    ShiftSwapRequest::STATUS_CANCELLED => 'Cancelado',
                ]),
            Textarea::make('requester_notes')
                ->label('Notas del solicitante')
                ->nullable(),
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
                        'swap' => 'Intercambio',
                        'cover' => 'Cobertura',
                        'dayoff' => 'Día libre',
                        default => ucfirst($state),
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'swap' => 'primary',
                        'cover' => 'info',
                        'dayoff' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('target.name')
                    ->label('Compañero'),
                TextColumn::make('swap_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('return_date')
                    ->label('Devolución')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'target_accepted' => 'info',
                        'approved' => 'success',
                        'denied' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'target_accepted' => 'Aceptado',
                        'approved' => 'Aprobado',
                        'denied' => 'Denegado',
                        'cancelled' => 'Cancelado',
                        default => ucfirst($state),
                    }),
                TextColumn::make('reviewer.name')
                    ->label('Revisado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),

                \Filament\Actions\Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => in_array($record->status, [ShiftSwapRequest::STATUS_PENDING, ShiftSwapRequest::STATUS_TARGET_ACCEPTED]))
                    ->action(function ($record, ShiftSwapService $shiftSwapService): void {
                        $shiftSwapService->approveRequest(
                            request: $record,
                            reviewedBy: (int) auth()->id(),
                        );

                        Notification::make()
                            ->title('Intercambio aprobado')
                            ->success()
                            ->send();
                    }),

                \Filament\Actions\Action::make('deny')
                    ->label('Denegar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record): bool => in_array($record->status, [ShiftSwapRequest::STATUS_PENDING, ShiftSwapRequest::STATUS_TARGET_ACCEPTED]))
                    ->action(function ($record, ShiftSwapService $shiftSwapService): void {
                        $shiftSwapService->denyRequest(
                            request: $record,
                            reviewedBy: (int) auth()->id(),
                        );

                        Notification::make()
                            ->title('Intercambio denegado')
                            ->warning()
                            ->send();
                    }),
            ])
            ->defaultSort('swap_date', 'desc');
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Todas')
                ->badge(fn (): int => $this->getRecord()->shiftSwapRequests()->count()),

            'pending' => Tab::make()
                ->label('Pendientes')
                ->badge(fn (): int => $this->getRecord()->shiftSwapRequests()->where('status', 'pending')->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'pending')),

            'approved' => Tab::make()
                ->label('Aprobadas')
                ->badge(fn (): int => $this->getRecord()->shiftSwapRequests()->where('status', 'approved')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'approved')),

            'denied' => Tab::make()
                ->label('Denegadas')
                ->badge(fn (): int => $this->getRecord()->shiftSwapRequests()->where('status', 'denied')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', 'denied')),
        ];
    }
}
