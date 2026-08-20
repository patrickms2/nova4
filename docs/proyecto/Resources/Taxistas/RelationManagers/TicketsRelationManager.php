<?php

namespace App\Filament\App\Resources\Taxistas\RelationManagers;

use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'tickets';

    protected static ?string $title = 'Tickets';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('user_id')
                    ->default(fn (): int => (int) $this->getOwnerRecord()->id)
                    ->required(),

                Hidden::make('created_by_user_id')
                    ->default(fn (): ?int => auth()->id()),

                Section::make('Ticket')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titulo')
                            ->required()
                            ->maxLength(255),

                        Select::make('booking_department_id')
                            ->label('Departamento')
                            ->relationship(
                                'department',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->where('is_active', true)
                                    ->where('has_tickets_service', true)
                                    ->orderBy('name')
                            )
                            ->searchable()
                            ->preload(),

                        Select::make('priority')
                            ->label('Prioridad')
                            ->required()
                            ->default('media')
                            ->options([
                                'baja' => 'Baja',
                                'media' => 'Media',
                                'alta' => 'Alta',
                                'urgente' => 'Urgente',
                            ]),

                        Select::make('status')
                            ->label('Estado')
                            ->required()
                            ->default('abierto')
                            ->options([
                                'abierto' => 'Abierto',
                                'en_proceso' => 'En proceso',
                                'resuelto' => 'Resuelto',
                                'cerrado' => 'Cerrado',
                            ]),

                        Select::make('assigned_to_user_id')
                            ->label('Asignado a')
                            ->options(fn (): array => User::query()
                                ->whereIn('role', ['admin', 'booking', 'manager', 'service'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload(),

                        DateTimePicker::make('due_at')
                            ->label('Fecha limite')
                            ->seconds(false),

                        Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
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
                    ->badge()
                    ->toggleable(),

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

                TextColumn::make('assignedTo.name')
                    ->label('Asignado')
                    ->toggleable(),

                TextColumn::make('due_at')
                    ->label('Vence')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
            ])
            ->filters([
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
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo ticket')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = (int) $this->getOwnerRecord()->id;
                        $data['created_by_user_id'] = auth()->id();
                        $data['opened_at'] = $data['opened_at'] ?? now()->toDateTimeString();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
