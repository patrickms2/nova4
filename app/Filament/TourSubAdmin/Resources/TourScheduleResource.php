<?php

namespace App\Filament\TourSubAdmin\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\TourSubAdmin\Resources\TourScheduleResource\Pages;
use App\Models\Tour;
use App\Models\TourSchedule;
use BackedEnum;
use Filament\Actions;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TourScheduleResource extends Resource
{
    protected static ?string $model = TourSchedule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static string|\UnitEnum|null $navigationGroup = 'Tours';
    protected static ?string $navigationParentGroup = 'Catálogo';

    protected static ?int $navigationSort = 12;

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return true;

        if (! $user) {
            return false;
        }

        if ($user->role === 'super_admin' || ($user->role === 'admin' && $user->section === 'tour')) {

            $tour = Tour::where('admin_id', $user->id)->first();

            return $tour !== null;
        }

        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = Filament::auth()->user();

        return true;

        if (! $user) {
            return false;
        }

        if ($user->role === 'super_admin' || ($user->role === 'admin' && $user->section === 'tour')) {

            $tour = Tour::where('admin_id', $user->id)->first();

            return $tour !== null;
        }

        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('tour', function ($query) {
                // $query->where('admin_id', auth()->id());
            });
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('tour_id')
                    ->default(fn () => Tour::where('admin_id', auth()->id())->value('id')),

                Forms\Components\DatePicker::make('start_date')->required(),
                Forms\Components\DatePicker::make('end_date'),
                Forms\Components\TimePicker::make('start_time'),
                Forms\Components\TextInput::make('available_spots')->required()->numeric(),
                Forms\Components\TextInput::make('price')->numeric(),
                Forms\Components\Toggle::make('is_active')->required(),

                // Forms\Components\CheckboxList::make('activities')
                // ->relationship('activities', 'name')->searchable()
                // ->label('Available Activities'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tour.name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('start_time'),
                Tables\Columns\TextColumn::make('activities.name')
                    ->label('Activities')
                    ->formatStateUsing(fn ($state, $record) => $record->activities->pluck('name')->join(', ')
                    ),
                Tables\Columns\TextColumn::make('available_spots')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->numeric()
                    ->sortable(),
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
            'index' => Pages\ListTourSchedules::route('/'),
            'create' => Pages\CreateTourSchedule::route('/create'),
            'view' => Pages\ViewTourSchedule::route('/{record}'),
            'edit' => Pages\EditTourSchedule::route('/{record}/edit'),
        ];
    }
}
