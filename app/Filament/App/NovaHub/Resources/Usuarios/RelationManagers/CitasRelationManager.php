<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\RelationManagers;

use Filament\Support\Icons\Heroicon;

use App\Filament\App\NovaHub\Resources\Citas\CitaResource;
use App\Models\Cita;
use BackedEnum;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Actions\CreateAction;


class CitasRelationManager extends RelationManager
{
    /**
     * Nombre de la relación que queremos gestionar (debe coincidir con el método
     * de relación en el modelo `Usuario`).
     */
    protected static string $relationship = 'citas';

    /**
     * Atributo de título para los registros.
     */
    protected static ?string $recordTitleAttribute = 'nombre';

    /**
     * Personalización de la navegación
     */
    protected static ?string $title = 'Citas';
    protected static BackedEnum|string|null $icon = Heroicon::OutlinedCalendar;

    /**
     * Optimizar carga de datos
     */
    /* protected function getTableQuery(): Builder
     {
         return parent::getTableQuery()
             ->select([
                 'id', 'nombre', 'appointment_date', 'estado_id', 'usuario_id', 'created_at', 'updated_at'
             ])
             ->with([
                 'estado:id,nombre',
                 'usuario:id,nombre'
             ]);
     }*/

    public function form(Form $form): Form
    {
        return (new CitaResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new CitaResource())
            ->table($table)
            ->headerActions([ // Agregar acciones en el encabezado de la tabla
                CreateAction::make()
                    // Prellena el formulario para que el Select aparezca con el taxista actual seleccionado
                    ->fillForm(fn() => [
                        'usuario_id' => $this->getOwnerRecord()->id,
                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getOwnerRecord()->id;

                        return $data;
                    }),
            ])
            ->defaultSort('appointment_date', 'desc')
            ->paginated([
                10, 25, 50
            ]);
    }


}
