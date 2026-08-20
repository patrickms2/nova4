<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\RelationManagers;

use Filament\Support\Icons\Heroicon;

use App\Filament\App\NovaHub\Resources\Taxistas\TaxistaResource;

use App\Models\Taxis\Usuario;
use Filament\Actions\AttachAction;
use Filament\Actions\CreateAction;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use BackedEnum;


class ChoferRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'conductorTaxista';
    protected static ?string $title = 'Conductores';
    protected static BackedEnum|string|null $icon = Heroicon::OutlinedUsers;

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * Configura la tabla que mostrará las ubicaciones relacionadas.
     */

    public function form(Form $form): Form
    {
        return (new TaxistaResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new TaxistaResource())->table($table)
            ->defaultSort('id', 'desc')
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                AttachAction::make()
                    ->label('Asociar')
                    ->action('attach')
                    ->modalHeading('Asociar Chofer')
                ,
                CreateAction::make()
                    // Prellena el formulario para que el Select aparezca con el taxista actual seleccionado
                    ->fillForm(fn() => [
                        'taxista_id' => $this->getOwnerRecord()->id,
                        'municipio_id' => $this->getOwnerRecord()->municipio_id,
                        'estado_id' => $this->getOwnerRecord()->estado_id,
                        'cif' => $this->getOwnerRecord()->cif,
                        'tel_fijo' => $this->getOwnerRecord()->tel_fijo,
                        'direccion' => $this->getOwnerRecord()->direccion,
                        'tipo_id' => 8,
                        'licencia' => $this->getOwnerRecord()->licencia,
                        'tipostaxi_id' => 1,

                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateDataUsing(function (array $data): array {
                        $data['taxista_id'] = $this->getOwnerRecord()->id;
                        $data['municipio_id'] = $this->getOwnerRecord()->municipio_id;
                        $data['estado_id'] = $this->getOwnerRecord()->estado_id;
                        $data['cif'] = $this->getOwnerRecord()->cif;
                        $data['tel_fijo'] = $this->getOwnerRecord()->tel_fijo;
                        $data['direccion'] = $this->getOwnerRecord()->direccion;
                        $data['tipo_id'] = 8;
                        $data['licencia'] = $this->getOwnerRecord()->licencia;


                        return $data;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([
                10, 25, 50
            ]);
    }


}
