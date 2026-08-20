<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Servicios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Servicios\Pagos\PagoResource;

use App\Models\Pago;
use Filament\Forms;
use Filament\Schemas\Schema as Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Filament\Actions;

class PagosRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'pagos';

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * Configura la tabla que mostrará las ubicaciones relacionadas.
     */
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->slideOver(),
        ];
    }
    public function form(Form $form): Form
    {
        return (new PagoResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new PagoResource())->table($table)
            ->defaultSort('id', 'desc')
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                CreateAction::make()
                    // Prellena el formulario para que el Select aparezca con el taxista actual seleccionado
                    ->fillForm(fn () => [
                        'usuario_id' => $this->getOwnerRecord()->id,
                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getOwnerRecord()->id;
                        return $data;
                    })
                    ->slideOver(),
            ]);
    }


}
