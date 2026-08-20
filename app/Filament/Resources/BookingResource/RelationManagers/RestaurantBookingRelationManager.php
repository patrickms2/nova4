<?php

namespace App\Filament\Resources\BookingResource\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class RestaurantBookingRelationManager extends RelationManager
{
    protected static string $relationship = 'restaurantBooking';
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('restaurant_id')
                    ->relationship('restaurant', 'name')->searchable()
                    ->required(),
                Forms\Components\DatePicker::make('reservation_date'),
                Forms\Components\TimePicker::make('reservation_time'),
                Forms\Components\TextInput::make('number_of_guests')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('duration')
                    ->label('Duration (minutes)'),
            ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('restaurant.name')
                    ->label('Restaurant')->searchable(),
                Tables\Columns\TextColumn::make('reservation_date')
                    ->date(),
                Tables\Columns\TextColumn::make('reservation_time')
                    ->time(),
                Tables\Columns\TextColumn::make('number_of_guests'),
                Tables\Columns\TextColumn::make('duration')
                    ->label('Duration (minutes)'),
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
