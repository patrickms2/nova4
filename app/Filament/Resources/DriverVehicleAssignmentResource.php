<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\DriverVehicleAssignmentResource\Pages;
use App\Models\DriverVehicleAssignment;
use BackedEnum;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use UnitEnum;

class DriverVehicleAssignmentResource extends Resource
{
    protected static ?string $model = DriverVehicleAssignment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Taxis';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?int $navigationSort = 21;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('driver_id')
                    ->label('Driver')
                    ->preload()
                    ->relationship('driver', 'license_number') // or whatever field is appropriate
                    ->required()
                    ->searchable(),
                Select::make('vehicle_id')
                    ->label('Vehicle')
                    ->relationship('vehicle', 'registration_number')->searchable(), // or whatever field is appropriate
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.registration_number')
                    ->label('Vehicle Registration Number')->searchable(),
                Tables\Columns\TextColumn::make('driver.license_number')
                    ->label('Driver License Number'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\EditAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDriverVehicleAssignments::route('/'),
            // 'create' => Pages\CreateDriverVehicleAssignment::route('/create'),
            // 'edit' => Pages\EditDriverVehicleAssignment::route('/{record}/edit'),
        ];
    }
}
