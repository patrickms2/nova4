<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\RelationManagers;

use App\Filament\App\NovaHub\Resources\Pdfs\PdfResource;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    public function form(Form $form): Form
    {
        return (new PdfResource())->form($form);
    }

    public function table(Table $table): Table
    {
        return (new PdfResource())->table($table)
            ->headerActions([
                CreateAction::make()
                    ->fillForm(fn() => [
                        'usuario_id' => $this->getOwnerRecord()->id,
                        'usuario_tipo_id' => 4,
                        'departamento_id' => 4,

                    ])
                    // Asegura que siempre se guarde asociado al taxista actual
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getOwnerRecord()->id;
                        //$data['taxi_id'] = $this->getOwnerRecord()->taxis()->first()->id;
                        $data['usuario_tipo_id'] = 4;
                        $data['departamento_id'] = 4;


                        return $data;
                    })
                    ->slideOver(),
            ]);
    }
}
