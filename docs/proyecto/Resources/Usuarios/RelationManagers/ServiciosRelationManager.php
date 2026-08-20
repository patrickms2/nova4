<?php

namespace App\Filament\App\Resources\Usuarios\RelationManagers;


use App\Filament\App\Resources\Servicios\ServicioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;


class ServiciosRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'servicios';

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * Configura la tabla que mostrará las ubicaciones relacionadas.
     */

    public function form(Form $form): Form
    {
        return (new ServicioResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new ServicioResource())
            ->table($table)
            ->defaultSort('id', 'desc')
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                CreateAction::make()
                    // Prellena el formulario para que el Select aparezca con el taxista actual seleccionado
                    ->fillForm(fn() => [
                        'operador_id' => 1,
                        'taxista_id' => $this->getOwnerRecord()->id,
                        'municipio_id' => $this->getOwnerRecord()->municipio_id,
                        'tipotaxi_id' => 1,
                        'personas' => 2,
                        'habitacion' => '1',
                        //'taxi_id' => $this->getOwnerRecord()->id,
                        'tipo_usuario_id' => 1,
                        'fecha_servicio' => now(),
                        'observaciones' => 'Sin observaciones',
                        'estado_id' => 1,

                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['operador_id'] = 1;
                        $data['taxista_id'] = $this->getOwnerRecord()->id;
                        //$data['taxi_id'] = $this->getOwnerRecord()->taxis()->first()->id;

                        $data['estado_id'] = 1;
                        $data['municipio_id'] = $this->getOwnerRecord()->municipio_id;
                        $data['tipotaxi_id'] = 1;
                        $data['personas'] = 2;
                        $data['observaciones'] = 'Sin observaciones';
                        $data['habitacion'] = '1';


                        return $data;
                    }),
            ]);
    }


}
