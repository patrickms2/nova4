<?php

namespace App\Filament\TravelAdmin\Resources;

use Filament\Support\Icons\Heroicon;
use App\Filament\TravelAdmin\Resources\TravelAgencyResource\Pages;
use App\Models\Admin;
use App\Models\TravelAgency;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Infolists\Components\TextEntry;

use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Validation\Rule;
use UnitEnum;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class TravelAgencyResource extends Resource
{
    protected static ?string $model = TravelAgency::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;
    protected static string|UnitEnum|null $navigationGroup = 'Paquetes';
    protected static ?string $navigationParentGroup = 'Catálogo';
    public static function canAccess(): bool
    {
        return true;

        return Filament::auth()->check()
            && Filament::auth()->user()->role === 'sub_admin'
            && Filament::auth()->user()->section === 'tour';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;

        return Filament::auth()->check()
            && Filament::auth()->user()->role === 'sub_admin'
            && Filament::auth()->user()->section === 'tour';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
        // ->whereHas('tour', function ($query) {
        // $query->where('admin_id', auth()->id());
        // });
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Section::make('Location')
                            ->schema([
                                Forms\Components\TextInput::make('latitude')
                                    ->label('Latitude')
                                    ->required()
                                    ->readonly(),
                                Forms\Components\TextInput::make('longitude')
                                    ->label('Longitude'),
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->required(),
                                Forms\Components\TextInput::make('country')
                                    ->label('Country'),
                                TextEntry::make('Map')
                                    ->content(function () {
                                        return view('map');
                                    })->columnSpanFull(),
                            ])->columns(2),
                        Forms\Components\Select::make('admin_id')
                            ->label('Manager')
                            ->options(function () {
                                $section = auth()->user()?->section;
                                return Admin::where('role', 'sub_admin')
                                    ->where('section', $section)
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })->required()
                            ->rule(function () {
                                return Rule::unique('restaurants', 'admin_id');
                            }),
                        Forms\Components\Textarea::make('description')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
                Section::make('Contact & Media')
                                    ->schema([
                        Forms\Components\TextInput::make('logo')
                            ->label('Logo URL'),
                        Forms\Components\TextInput::make('website'),
                        Forms\Components\TextInput::make('phone'),
                        Forms\Components\TextInput::make('email'),


                    ])->columns(2),
                Section::make('Ratings & Status')
                                    ->schema([

                        Forms\Components\TextInput::make('average_rating')
                            ->label('Average Rating')
                            ->numeric()
                            ->default(0.00),
                        Forms\Components\TextInput::make('total_ratings')
                            ->label('Total Ratings')
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->required(),
                    ])->columns(2),
            ]);
    }
    public static function table(Table $table): Table {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('location.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('average_rating'),
                Tables\Columns\TextColumn::make('total_ratings'),
                Tables\Columns\TextColumn::make('logo'),
                Tables\Columns\TextColumn::make('website'),
                Tables\Columns\TextColumn::make('phone'),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('admin_id'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at'),
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
                ]),            ]);
    }
    public static function getRelations(): array
    {
        return [
            //
        ];
}
    public static function getPages(): array {
            return [ 'index' => \App\Filament\TravelAdmin\Resources\TravelAgencyResource\Pages\ListTravelAgencies::route('/'),
            'create' => \App\Filament\TravelAdmin\Resources\TravelAgencyResource\Pages\CreateTravelAgency::route('/create'),
            'view' => \App\Filament\TravelAdmin\Resources\TravelAgencyResource\Pages\ViewTravelAgency::route('/{record}'),
            'edit' => \App\Filament\TravelAdmin\Resources\TravelAgencyResource\Pages\EditTravelAgency::route('/{record}/edit'),
    ];}

}