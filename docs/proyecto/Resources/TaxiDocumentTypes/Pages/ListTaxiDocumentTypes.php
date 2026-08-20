<?php

namespace App\Filament\App\Resources\TaxiDocumentTypes\Pages;

use App\Filament\App\Resources\TaxiDocumentTypes\TaxiDocumentTypeResource;

use App\Filament\App\Resources\TaxistaDocuments\Pages\ListTaxistaDocuments;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTaxiDocumentTypes extends ListRecords
{
    use AdvancedTables;

    protected static ?string $title = 'Tipos de documento';

    protected static string $resource = TaxiDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->slideOver(false)->icon('heroicon-o-plus')
                ->color('danger')
                ->button()
                ->hiddenLabel(),
            Action::make('documentos')->url(ListTaxistaDocuments::getUrl())->label('Documentos')->icon('heroicon-o-table-cells')->color('danger')->tooltip('Documentos')->hiddenLabel(),
        ];
    }
}
