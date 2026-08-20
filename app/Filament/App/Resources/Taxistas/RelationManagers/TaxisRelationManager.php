<?php

namespace App\Filament\App\Resources\Taxistas\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class TaxisRelationManager extends RelationManager
{
    protected static string $relationship = 'taxis';

    protected static ?string $title = 'Taxis';

    protected static ?string $recordTitleAttribute = 'license_plate';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('taxista_user_id')
                    ->default(fn (): int => (int) $this->getOwnerRecord()->id)
                    ->required(),

                Section::make('Taxi')
                    ->schema([
                        TextInput::make('license_plate')
                            ->label('Matricula')
                            ->required()
                            ->maxLength(20)
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),

                        TextInput::make('vehicle_brand')
                            ->label('Marca')
                            ->maxLength(255),

                        TextInput::make('vehicle_model')
                            ->label('Modelo')
                            ->maxLength(255),

                        Select::make('vehicle_type')
                            ->label('Tipo')
                            ->options([
                                'berlina' => 'Berlina',
                                'familiar' => 'Familiar',
                                'van' => 'Van',
                                'adaptado' => 'Adaptado',
                                'otro' => 'Otro',
                            ]),

                        TextInput::make('seats')
                            ->label('Plazas')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(9),

                        TextInput::make('municipality')
                            ->label('Municipio')
                            ->maxLength(255),

                        Select::make('status')
                            ->label('Estado')
                            ->required()
                            ->default('activo')
                            ->options([
                                'activo' => 'Activo',
                                'mantenimiento' => 'Mantenimiento',
                                'baja' => 'Baja',
                            ]),

                        Toggle::make('is_accessible')
                            ->label('Adaptado PMR')
                            ->default(false),

                        TextInput::make('current_lat')
                            ->label('Latitud')
                            ->numeric()
                            ->minValue(-90)
                            ->maxValue(90),

                        TextInput::make('current_lng')
                            ->label('Longitud')
                            ->numeric()
                            ->minValue(-180)
                            ->maxValue(180),

                        DateTimePicker::make('last_located_at')
                            ->label('Ultima localizacion')
                            ->seconds(false),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('license_plate')
                    ->label('Matricula')
                    ->searchable()
                    ->sortable()
                    ->badge(),

                TextColumn::make('vehicle_brand')
                    ->label('Marca')
                    ->toggleable(),

                TextColumn::make('vehicle_model')
                    ->label('Modelo')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'activo' => 'success',
                        'mantenimiento' => 'warning',
                        'baja' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('municipality')
                    ->label('Municipio')
                    ->toggleable(),

                IconColumn::make('is_accessible')
                    ->label('PMR')
                    ->boolean(),

                TextColumn::make('last_located_at')
                    ->label('Localizado')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'mantenimiento' => 'Mantenimiento',
                        'baja' => 'Baja',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nuevo taxi')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['taxista_user_id'] = (int) $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
