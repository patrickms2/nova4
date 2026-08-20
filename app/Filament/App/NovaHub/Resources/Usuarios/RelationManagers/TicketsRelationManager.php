<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Tickets\TicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;


class TicketsRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'tickets';

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * Configura la tabla que mostrará las ubicaciones relacionadas.
     */

    public function form(Form $form): Form
    {
        return (new TicketResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new TicketResource())->table($table)
            ->defaultSort('id', 'desc')
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                CreateAction::make()
                    // Prellena el formulario para que el Select aparezca con el taxista actual seleccionado
                    ->fillForm(fn() => [
                        'usuario_id' => $this->getOwnerRecord()->id,
                        'priority' => '2',
                        'status' => 'open',
                        'name' => 'Tema',
                        'start_date' => now(),
                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getOwnerRecord()->id;
                        $data['priority'] = '2';
                        $data['status'] = 'open';
                        $data['name'] = 'Tema';
                        $data['start_date'] = now();
                        return $data;
                    }),
            ]);
    }


}
