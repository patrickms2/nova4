<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Conductors;

use Filament\Support\Icons\Heroicon;

use App\AdminDashboardSidebarSorting;
use App\Filament\Clusters\Taxistas;
use App\Filament\App\NovaHub\Resources\Servicios\Conductors\Pages as PagesAlias;
use App\Filament\App\NovaHub\Resources\Servicios\Conductors\Pages\ListConductores;
use App\Filament\App\NovaHub\Resources\Servicios\Conductors\Pages\CreateConductor;
use App\Filament\App\NovaHub\Resources\Servicios\Conductors\Pages\EditConductor;
use App\Filament\App\NovaHub\Resources\Servicios\ServiciosCluster\ServiciosCluster;;

use App\Filament\App\NovaHub\Resources\Servicios\Conductors\RelationManagers;
use App\Filament\Support\baseresource;
use App\Models\Taxi\Municipio;
use App\Models\Taxi\Conductor;
use App\Models\Taxi\Taxista;

use Filament\Actions\AttachAction;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema as Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Actions\CreateAction;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;

use Filament\Forms\Components\Toggle;
use Filament\Pages\Enums\SubNavigationPosition;
use Archilex\AdvancedTables\Components\PresetView;
use Archilex\AdvancedTables\AdvancedTables;
use Archilex\AdvancedTables\Filters\SelectFilter;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
use Archilex\AdvancedTables\Filters\DateFilter;
use Archilex\AdvancedTables\Filters\UserSelectFilter;
use UnitEnum;
class ConductorResource extends baseresource
{


    protected static ?string $model = Conductor::class;
    //protected static string | UnitEnum | null $navigationGroup = 'Taxis';
    protected static ?string $navigationLabel = "Conductores";
    protected static ?string $breadcrumb = "Conductores";
    protected static ?string $slug = 'conductores';
    protected static ?int $navigationSort = 2;
    protected static string | UnitEnum | null $navigationGroup = 'Taxis';


    // protected static string | BackedEnum | null $navigationIcon  = Heroicon::OutlinedRectangleStack;
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $cluster = ServiciosCluster::class;
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function  form(Form $form): Form {
        return $form->schema([
TextInput::make('nombre')->columns(1),
TextInput::make('cif')
        ->label('NIF'),
TextInput::make('tel_fijo')
        ->label("Telefono"),
        Select::make('municipio_id')
            ->label('Municipio')
            ->options(function () {
                // Exclude the current category if editing
                $query = Municipio::query();
                if (request()->route('record')) {
                    $query->where('id', '!=', request()->route('record'));
                }
                return $query->pluck('nombre', 'id');
            })
            ->searchable()
            ->nullable()
            ->preload()
            ->columns(2)
            ->columnSpan('xs'),
            Select::make('taxista_id')
            ->label('Taxista')
            ->options(function () {
                // Exclude the current category if editing
                $query = Taxista::query();
                if (request()->route('record')) {
                    $query->where('id', '!=', request()->route('record'));
                }
                return $query->pluck('nombre', 'id');
            })
            ->searchable()
            ->nullable()
            ->preload()
            ->columns(1)
            ->columnSpan('xs'),
        TextInput::make('direccion')->columnSpan('xs'),
Toggle::make('estado_id')
    ->label('Estado')
        ->default(1),
        Hidden::make('tipo_id')
        ->default(8),


        ]); }

    public static function  table(Table $table): Table {
        return $table->columns([
TextColumn::make('nombre')->searchable(),
TextColumn::make('cif')
            ->label('NIF'),
TextColumn::make('telefono'),
TextColumn::make('direccion'),
])
            ->actions([
                EditAction::make(),
                ViewAction::make(),
            ])
            ->headerActions([
                CreateAction::make(),
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
            'index' => Pages\ListConductores::route('/'),
            /*'create' => Pages\CreateConductor::route('/create'),
            'edit' => Pages\EditConductor::route('/{record}/edit'),*/
        ];
    }
    public static function getNavigationSort(): int { return 1; }
}
