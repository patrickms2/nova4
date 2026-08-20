<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\Pages;

use App\Filament\App\Facturacion\Resources\FacturaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFactura extends EditRecord
{
    protected static string $resource = FacturaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
 
    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }
 
        return [];
    }
}
