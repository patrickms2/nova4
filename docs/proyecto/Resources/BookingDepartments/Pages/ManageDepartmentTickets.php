<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Livewire;

class ManageDepartmentTickets extends ManageRelatedRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static string $relationship = 'tickets';

    protected static ?string $navigationLabel = 'Tickets';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 8;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo ticket')
                ->fillForm(fn (): array => [
                    'booking_department_id' => (int) $this->getRecord()->id,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'abierto',
                    'priority' => 'media',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['booking_department_id'] = (int) $this->getRecord()->id;
                    $data['created_by_user_id'] = auth()->id();
                    $data['opened_at'] = $data['opened_at'] ?? now()->toDateTimeString();

                    return $data;
                }),
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn (): string => view('components.employee-help-popup-content', ['page' => 'department-tickets'])->render())
                ->modalHeading('Ayuda - Tickets del Departamento')
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

        return (string) $record->tickets()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tickets gestionados por este departamento.';
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaTicketForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('booking_department_id', (int) $this->getRecord()->id))
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable()
                                        ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                    TextColumn::make('department.name')
                    ->label('Departamento')
                    ->searchable()
                    ->sortable()
                                        ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'baja' => 'gray',
                        'media' => 'info',
                        'alta' => 'warning',
                        'urgente' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'abierto' => 'warning',
                        'en_proceso' => 'info',
                        'resuelto' => 'success',
                        'cerrado' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('opened_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
