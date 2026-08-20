<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketInfolist;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Livewire;
use Guava\FilamentIconSelectColumn\Tables\Columns\IconSelectColumn;
use App\Enums\TicketStatus;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Taxista;

class ManageTicketsTaxista extends ManageRelatedRecords
{
    protected static string $resource = TaxistaResource::class;

    protected static string $relationship = 'tickets';

    protected static ?string $navigationLabel = 'Tickets';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 5;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string) $record->tickets()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return (string) ($this->getRecord()->name ?? 'Taxista');
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Conversación y seguimiento con departamentos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Ticket')
                ->fillForm(fn (): array => [
                    'user_id' => $this->getRecord()->id,
                    'created_by_user_id' => auth()->id(),
                    'status' => 'abierto',
                    'priority' => 'media',
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['user_id'] = $this->getRecord()->id;
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
    public function infolist(Schema $schema): Schema
    {
        return TaxistaTicketInfolist::configure($schema);
    }
    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                            ->label('ID')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('title')
                            ->label('Titulo')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),

                        TextColumn::make('department.name')
                            ->label('Departamento')
                            ->badge()
                            ->placeholder('Sin departamento')
                            ->toggleable(isToggledHiddenByDefault: true)
                            ->sortable(),

                        TextColumn::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'baja' => 'gray',
                                'media' => 'info',
                                'alta' => 'warning',
                                'urgente' => 'danger',
                                default => 'gray',
                            })
                            ->toggleable(isToggledHiddenByDefault: true)
                            ->sortable(),

                    IconSelectColumn::make('status')
                        ->label('Estado')
                        ->options(TicketStatus::class)
                        ->sortable(),

                TextColumn::make('opened_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                                                ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = $this->getRecord()->id;
                        $data['created_by_user_id'] = auth()->id();

                        return $data;
                    }),
                    
                DeleteAction::make(),   
            ])
                       ->filters([
                SelectFilter::make('user_id')
                    ->label('Taxista')
                    ->options(fn(): array => Cache::remember('taxista_ticket_options', now()->addHours(2), function () {
                        return Taxista::where('status', 'active')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    }))->searchable(),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_proceso' => 'En proceso',
                        'resuelto' => 'Resuelto',
                        'cerrado' => 'Cerrado',
                    ]),
            ])
                            ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                        BulkAction::make('pendiente')
                            ->label('Pendiente')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'pendiente']);
                                });

                                Notification::make()
                                    ->title('Ticket pendiente')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('en_proceso')
                            ->label('En Proceso')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'en_proceso']);
                                });

                                Notification::make()
                                    ->title('Ticket en proceso')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('finalizada')
                            ->label('Finalizada')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'finalizada']);
                                });

                                Notification::make()
                                    ->title('Ticket finalizado')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('cancelada')
                            ->label('Cancelada')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'cancelada']);
                                });

                                Notification::make()
                                    ->title('Ticket cancelado')
                                    ->success()
                                    ->send();
                            }),
                    ]),
                ])
            ->defaultSort('id', 'desc');
    }
}
