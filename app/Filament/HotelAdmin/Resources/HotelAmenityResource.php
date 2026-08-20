<?php

namespace App\Filament\HotelAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\HotelAdmin\Resources\HotelAmenityResource\Pages;
use App\Models\HotelAmenity;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Actions;

class HotelAmenityResource extends Resource
{
    protected static ?string $model = HotelAmenity::class;
    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedSparkles;
    protected static string | UnitEnum | null $navigationGroup = 'Hoteles';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?int $navigationSort = 4;
    public static function canAccess(): bool
    {
        return Filament::auth()->check() 
         && Filament::auth()->user()->role === 'admin' 
         && Filament::auth()->user()->section === 'hotel'
         ;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return Filament::auth()->check()  
         && Filament::auth()->user()->role === 'admin' 
            && Filament::auth()->user()->section === 'hotel';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('icon')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->getStateUsing(fn ($record) => "<i class='{$record->icon}' style='font-size: 20px'></i>")
                    ->html(),
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
            'index' => Pages\ListHotelAmenities::route('/'),
            'create' => Pages\CreateHotelAmenity::route('/create'),
            'view' => Pages\ViewHotelAmenity::route('/{record}'),
            'edit' => Pages\EditHotelAmenity::route('/{record}/edit'),
        ];
    }
}
