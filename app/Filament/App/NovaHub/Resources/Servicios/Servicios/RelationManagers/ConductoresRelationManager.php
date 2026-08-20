<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Servicios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Servicios\Servicios\Conductors\ConductorResource;

use App\Models\Taxis\Conductor;
use App\Models\Taxis\Municipio;
use App\Models\Taxis\Taxi;
use App\Models\Taxis\TaxistaConductor;
use App\Models\Taxis\Taxista;

use App\Models\Taxis\Turnos;
use App\Models\Taxis\Usuario;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema as Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Actions\AttachAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Actions\CreateAction;


class ConductoresRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'taxistaConductor';

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';


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

                Forms\Components\Select::make('taxista_id')
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
        return (new ConductorResource())->table($table);
          //  ->query()
        // ->where("id", 899)
            /*    ->with(
                    "taxistaConductor:conductor_id,taxista_id",
                    "taxistaConductor.conductor:id,nombre"
                )
                ->select("id", "nombre")*/

    }


}
