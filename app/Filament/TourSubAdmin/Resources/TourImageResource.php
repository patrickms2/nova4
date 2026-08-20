<?php

namespace App\Filament\TourSubAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\TourSubAdmin\Resources\TourImageResource\Pages;
use App\Models\Tour;
use App\Models\TourImage;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TourImageResource extends Resource
{
    protected static ?string $model = TourImage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static string|\UnitEnum|null $navigationGroup = 'Tours';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?int $navigationSort = 13;

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

                FileUpload::make('image_url')
                    ->label('Tour Image')
                    ->image()
                    ->directory('tour_images')
                    ->visibility('public')->maxSize(2048),
                Forms\Components\TextInput::make('Display_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Forms\Components\TextInput::make('caption')
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
                Tables\Columns\TextColumn::make('tour.name')
                    ->sortable(),
                ImageColumn::make('image_url')
                    ->label('Image')
                    ->getStateUsing(fn ($record) => asset($record->image_url))
                    ->height(50)
                    ->width(50),
                Tables\Columns\TextColumn::make('display_order')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('caption')
                    ->searchable(),
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
            'index' => Pages\ListTourImages::route('/'),
            'create' => Pages\CreateTourImage::route('/create'),
            'view' => Pages\ViewTourImage::route('/{record}'),
            'edit' => Pages\EditTourImage::route('/{record}/edit'),
        ];
    }
}
