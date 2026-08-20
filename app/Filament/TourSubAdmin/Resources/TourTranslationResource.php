<?php

namespace App\Filament\TourSubAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\TourSubAdmin\Resources\TourTranslationResource\Pages;
use App\Models\Tour;
use App\Models\TourTranslation;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TourTranslationResource extends Resource
{
    protected static ?string $model = TourTranslation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|\UnitEnum|null $navigationGroup = 'Tours';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?int $navigationSort = 11;

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
        return parent::getEloquentQuery()
            ->whereHas('tour', function ($query) {
                $query->where('admin_id', auth()->id());
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tour_id')
                    ->default(fn () => Tour::where('admin_id', auth()->id())->value('id')),

                Forms\Components\TextInput::make('language_code')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('translated_description')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tour.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('language_code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('translated_description'),
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
            'index' => Pages\ListTourTranslations::route('/'),
            'create' => Pages\CreateTourTranslation::route('/create'),
            'view' => Pages\ViewTourTranslation::route('/{record}'),
            'edit' => Pages\EditTourTranslation::route('/{record}/edit'),
        ];
    }
}
