<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;

class ManageEmployeeTickets extends ManageRelatedRecords
{
    protected static string $resource = EmployeeResource::class;

    protected static string $relationship = 'tickets';

    protected static ?string $navigationLabel = 'Tickets';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->tickets()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return (string) ($this->getRecord()->name ?? 'Empleado');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Tickets y seguimiento con departamentos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo ticket')
                ->fillForm(fn (): array => [
                    'user_id' => (int) $this->getRecord()->id,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'abierto',
                    'priority' => 'media',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = (int) $this->getRecord()->id;
                    $data['created_by_user_id'] = auth()->id();
                    $data['opened_at'] = $data['opened_at'] ?? now()->toDateTimeString();

                    return $data;
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaTicketForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(),

                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->badge(),

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
                    ->sortable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = (int) $this->getRecord()->id;
                        $data['created_by_user_id'] = auth()->id();

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }
}
