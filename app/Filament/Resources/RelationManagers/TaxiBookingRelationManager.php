<?php

namespace App\Filament\Resources\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class TaxiBookingRelationManager extends RelationManager
{
    protected static string $relationship = 'taxiBooking';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('taxi_service_id')
                    ->relationship('taxiService', 'name')->searchable()
                    ->required(),
                Forms\Components\Select::make('vehicle_type_id')
                    ->relationship('vehicleType', 'name')->searchable(),
                Forms\Components\Select::make('pickup_location_id')
                    ->relationship('pickupLocation', 'name')->searchable(),
                Forms\Components\Select::make('dropoff_location_id')
                    ->relationship('dropoffLocation', 'name'),
                Forms\Components\DateTimePicker::make('pickup_date_time'),
            ]);
    }
    public function table(Table $table): Table
{
        return $table
            ->recordTitleAttribute('TaxiBookingID')
            ->columns([
                Tables\Columns\TextColumn::make('taxiService.name')
                    ->label('Taxi Service')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vehicleType.name')
                    ->label('Vehicle Type'),
                Tables\Columns\TextColumn::make('pickupLocation.name')
                    ->label('Pickup Location'),
                Tables\Columns\TextColumn::make('dropoffLocation.name')
                    ->label('Dropoff Location'),
                Tables\Columns\TextColumn::make('pickup_date_time')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
