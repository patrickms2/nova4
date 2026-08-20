<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Conductors\ConductorResource;
use App\Models\Taxi\Municipio;
use App\Models\Taxi\Taxista;
use App\Models\Taxis\Conductor;
use App\Models\Taxis\Taxi;
use App\Models\Taxis\TaxistaConductor;
use App\Models\Taxis\Turnos;
use App\Models\Taxis\Usuario;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;
use BackedEnum;


class ConductorRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'conductorTaxista';
    protected static ?string $label = "Conductor";
    protected static ?string $pluralLabel = "Conductores";
    protected static ?string $modelLabel = "Conductor";
    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /* protected function getTableQuery(): Builder
      {
          return parent::getTableQuery()
              ->select([
                  'id', 'nombre',
              ])
              ->with([
                  "taxistaConductor:conductor_id,taxista_id",
                  "taxistaConductor.conductor:id,nombre"
              ]);
      }*/
    /**
     * Configura la tabla que mostrará las ubicaciones relacionadas.
     */
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('nombre')->columns(1),
                TextInput::make('cif')
                    ->label('NIF'),
                TextInput::make('tel_fijo')
                    ->label("Telefono"),
                Forms\Components\Select::make('municipio_id')
                    ->label('Municipio')
                    ->options(function () {
                        // Exclude the current category if editing
                        $query = Municipio::query();
                        return $query->pluck('nombre', 'id');
                    })
                    ->nullable()
                    ->preload()
                    ->columns(2)
                    ->columnSpan('xs'),

                Forms\Components\Select::make('taxista_id')
                    ->label('Taxista')
                    ->options(function () {
                        // Exclude the current category if editing
                        $query = Taxista::query();
                        return $query->pluck('nombre', 'id');
                    })
                    ->searchable()
                    ->nullable()
                    ->preload()
                    ->columns(1)
                    ->columnSpan('xs'),
                TextInput::make('direccion')->columnSpan('xs'),
                Toggle::make('estado_id')
                    ->default(1),
                Hidden::make('tipo_id')
                    ->default(8),


            ]);
    }

    public function form2(Form $form): Form
    {
        return (new ConductorResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new ConductorResource())
            ->table($table)
            ->defaultSort('id', 'desc')
            ->recordActions([
                AttachAction::make(),
            ])
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                AttachAction::make(),

            ]);
    }


}
