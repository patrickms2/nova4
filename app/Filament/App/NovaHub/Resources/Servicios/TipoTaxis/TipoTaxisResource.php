<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis;

use Filament\Support\Icons\Heroicon;

use App\Filament\App\NovaHub\Resources\Servicios\ServiciosCluster\ServiciosCluster;
use App\Filament\App\NovaHub\Resources\Servicios\TipoTaxis\Pages;

use App\Filament\Support\baseresource;
use App\Models\Taxi\Documento;
use App\Models\Taxi\TipoTaxis;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema as Form;
use Filament\Resources\Resource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use App\Filament\Clusters\Settings;
use Filament\Pages\Enums\SubNavigationPosition;
use UnitEnum;
use BackedEnum;
use Filament\Tables\Filters\Filter;
use Archilex\AdvancedTables\Filters\SelectFilter;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
class TipoTaxisResource extends baseresource
{


    protected static ?string $model = TipoTaxis::class;
    protected static ?string $navigationLabel = "Tipo Taxis";
    protected static ?int $navigationSort = -2;
    protected static string | UnitEnum | null $navigationGroup = 'Taxis';


    // protected static string | BackedEnum | null $navigationIcon  = Heroicon::OutlinedRectangleStack;
    protected static ?string $cluster = ServiciosCluster::class;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    public static function getNavigationBadge(): ?string
    {
        return (string) cache()->remember(
                    static::class . '.navigation-badge',
                    now()->addMinute(),
                    fn () => static::getModel()::count()
                );
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        TextInput::make('nombre')
                            ->required(),

                        ColorPicker::make('color'),

                        TextInput::make('capacidad')
                            ->integer(),

                        TextInput::make('preferenceId')
                        ->label('ID Preferencia'),

                        ToggleButtons::make('estado')
                            ->inline()
                            ->boolean(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TipoTaxis::where("version",1)
            )
            ->columns([
                TextColumn::make('id'),

                TextColumn::make('nombre')->searchable(),

                ColorColumn::make('color'),

                ToggleColumn::make('estado'),

                TextColumn::make('capacidad'),

                TextColumn::make('preferenceId')
                ->label('ID Preferencia'),

            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTipoTaxis::route('/'),
            /*'create' => Pages\CreateTipoTaxis::route('/create'),
            'edit' => Pages\EditTipoTaxis::route('/{record}/edit'),*/
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }
}
