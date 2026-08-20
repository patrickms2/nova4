<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Taxis;

use Filament\Support\Icons\Heroicon;

use App\AdminDashboardSidebarSorting;
use App\Enums\Semana;
use App\Filament\Clusters\Settings;
use App\Filament\Clusters\Taxistas;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\RelationManagers;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\RelationManagers\ChoferRelationManager;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\RelationManagers\UsuariosRelationManager;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\RelationManagers\ServiciosRelationManager;

use App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages\ListTaxis;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages\CreateTaxi;
use App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages\EditTaxi;
use App\Filament\App\NovaHub\Resources\Servicios\ServiciosCluster\ServiciosCluster;


use App\Filament\Support\baseresource;
use App\Models\Taxi\Conductor;
use App\Models\Taxi\Documento;
use App\Models\Taxi\Taxi;
use App\Models\Taxi\Taxista;
use App\Models\Taxi\TipoDoc;
use App\Models\Taxi\TipoTaxis;
use App\Models\Taxi\Usuario;
use App\Models\Taxi\Extra;

use Archilex\AdvancedTables\AdvancedTables;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Support\Concerns\HasLineClamp;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Support\Facades\Hash;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Actions\ActionGroup;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Actions\BulkActionGroup;
use App\Models\Taxi\EstadosUsuario;
use App\Models\Taxi\Municipio;
use UnitEnum;
use BackedEnum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Filters\Filter;
use Archilex\AdvancedTables\Filters\SelectFilter;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
use Filament\Tables\Filters\TernaryFilter;

class TaxiResource extends baseresource
{


    protected static ?string $model = Taxi::class;
    //protected static string | UnitEnum | null $navigationGroup = 'Taxis';
    protected static ?string $navigationLabel = "Taxis";
    protected static ?string $breadcrumb = "Taxis";
    protected static string | UnitEnum | null $navigationGroup = 'Taxis';

    protected static ?string $slug = 'taxis';
    protected static bool $shouldRegisterNavigation = true;
    protected static ?string $cluster = ServiciosCluster::class;
    protected static ?int $navigationSort = 2;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

// protected static string | BackedEnum | null $navigationIcon  = Heroicon::OutlinedRectangleStack;

    /*public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
ListTaxis::class,
CreateTaxi::class,
EditTaxi::class,

        ]);
    }*/

    public static function  form(Form $form): Form {
        return $form->schema([
            TextInput::make('matricula'),
            TextInput::make('licencia'),
            TextInput::make('modelo'),
            TextInput::make('anio')->label("Año"),
            Select::make('tipotaxi')
                ->label('Tipo')
                ->options(function () {
                    // Exclude the current category if editing
                    $query = TipoTaxis::query();
                    return $query->pluck('nombre', 'preferenceId');
                })
                ->searchable()
                ->nullable()
                ->preload()
                ->multiple()
                ->columnSpan('xs'),
            Select::make('extras')
                ->label('Extras')
                ->options(function () {
                    // Exclude the current category if editing
                    $query = Extra::query();
                    return $query->pluck('nombre', 'id');
                })
                ->multiple(),
            Select::make('usuario_id')
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
                ->columnSpan('xs'),
            Select::make('chofer_id')
                ->label('Chofer')
                ->options(function () {
                    // Exclude the current category if editing
                    $query = Conductor::query();
                    if (request()->route('record')) {
                        $query->where('id', '!=', request()->route('record'));
                    }
                    return $query->pluck('nombre', 'id');
                })
                ->searchable()
                ->nullable()
                ->preload()
                ->columnSpan('xs'),
        ]); }

    public static function  table(Table $table): Table {
        return $table
                ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('matricula')
                 ->label('Matrícula')
                    ->sortable()
                    ->extraAttributes(['style' => 'font-weight: bold']),
                TextColumn::make('modelo')
                ->label('Modelo')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('licencia')
                ->label('Licencia')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('anio')->label("Año")
                    ->label('Año')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tipotaxi')
                    ->label('Tipo Taxi')
                    ->tooltip(function () {
                        return TipoTaxi::query()->select(['nombre','id','preferenceId'])
                            ->where("estado",1)
                            ->pluck('nombre','id')
                            ->implode(',','nombre');}
                    )
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('extras')
                    ->label('Extras')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('usuario.nombre')->label('Taxista')
                ->label('Taxista')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conductor.nombre')->label('Chofer')
                ->label('Chofer')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('estado_id')
                    ->label('Estado de Usuario')
                    ->options(function () {
                        return EstadosUsuario::query()
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id');
                    })
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('bloqueado'),
                TernaryFilter::make('estado'),
                SelectFilter::make('municipio_id')
                    ->label('Municipio')
                    ->options(function () {
                        return Municipio::query()
                            ->orderBy('nombre')
                            ->pluck('nombre', 'id');
                    })
                    ->searchable()
                    ->preload(),
        ])
            ->headerActions([
                //CreateAction::make(),
            ])
        ->actions([
            EditAction::make(),
            DeleteAction::make(),
        ])
        ->defaultSort('id', 'desc')
        ->paginated([
            10, 25, 50, 100
        ])
        ->toolbarActions([
            BulkActionGroup::make([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                BulkAction::make('Cambiar Dpto.')
                    ->icon(Heroicon::PencilSquare)
                    ->schema([
                       Select::make('departamento_id')
                            ->label('Departamento')
                            ->default(1)
                            ->relationship('departamento', 'nombre')->searchable()
                            ->required(),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                        $records->each(function ($record) use ($data) {
                            $record->update(['departamento_id' => $data['departamento_id']]);
                        });

                        Notification::make()
                            ->title('Departamento actualizado')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('activate')
                    ->label('Activar Taxi')
                    ->icon(Heroicon::CheckCircle)
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each(function ($record) {
                            $record->update(['estado_id' => 1]);
                        });

                        Notification::make()
                            ->title('Taxis activados')
                            ->success()
                            ->send();
                    }),

                BulkAction::make('deactivate')
                    ->label('Desactivar Taxi')
                    ->icon(Heroicon::XCircle)
                    ->requiresConfirmation()
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                        $records->each(function ($record) {
                            $record->update(['estado_id' => 0]);
                        });

                        Notification::make()
                            ->title('Taxis desactivados')
                            ->success()
                            ->send();
                    }),

            ]),

    ]); }

    public static function getRelations(): array
    {
        return [
            UsuariosRelationManager::class,
            ServiciosRelationManager::class,
            ChoferRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxis::route('/'),
            'create' => CreateTaxi::route('/create'),
            'edit' => EditTaxi::route('/{record}/edit'),
        ];
    }
    public static function getNavigationSort(): int { return 2; }
}
