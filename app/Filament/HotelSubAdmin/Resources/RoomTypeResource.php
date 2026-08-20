<?php

namespace App\Filament\HotelSubAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use Filament\Actions;

use BackedEnum;
use Filament\Forms\Components\Select;
use UnitEnum;

use App\Events\HotelRoom;
use App\Filament\HotelSubAdmin\Resources\RoomTypeResource\Pages\CreateRoomType;
use App\Filament\HotelSubAdmin\Resources\RoomTypeResource\Pages\EditRoomType;
use App\Filament\HotelSubAdmin\Resources\RoomTypeResource\Pages\ListRoomTypes;
use App\Filament\HotelSubAdmin\Resources\RoomTypeResource\Pages\ViewRoomType;
use App\Filament\HotelSubAdmin\Resources\RoomTypeResource\Pages;
use App\Filament\HotelSubAdmin\Resources\RoomTypeResource\RelationManagers;
use App\Models\RoomType;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema as Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class RoomTypeResource extends Resource
{
    protected static ?string $model = RoomType::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedSparkles;
    protected static string | UnitEnum | null $navigationGroup = 'Hoteles';
    protected static ?string $navigationParentGroup = 'Catálogo';

    public static function canAccess(): bool
    {
        return true;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
        ->whereHas('hotel');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('hotel_id')
                    ->label('hotel')
                    ->relationship('hotel', 'name')->searchable()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                    Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('number')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('max_occupancy')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('base_price')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('discount_percentage')
                    ->required()
                    ->numeric()
                    ->default(0.00),
                Forms\Components\TextInput::make('size')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('bed_type')
                    ->maxLength(255)
                    ->default(null),
                    FileUpload::make('image')
                ->label('Hotel Image')
                ->image()
                ->directory('hotel_images')
                ->visibility('public')
                ->required()->maxSize(2048),

            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('number')
                    ->numeric(),
                Tables\Columns\TextColumn::make('max_occupancy')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('size')
                    ->searchable(),
                Tables\Columns\TextColumn::make('bed_type')
                    ->searchable(),
                ImageColumn::make('image')
                    ->label('Image')
                    ->getStateUsing(fn ($record) => asset(asset('images/'.$record->image) ))
                    ->width(50),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\ViewAction::make(),
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
            'index' => ListRoomTypes::route('/'),
            'create' => CreateRoomType::route('/create'),
            'view' => ViewRoomType::route('/{record}'),
            'edit' => EditRoomType::route('/{record}/edit'),
        ];
    }
}
