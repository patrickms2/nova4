<?php

namespace App\Filament\App\Facturacion\Resources\FacturaResource\Pages;

use App\Filament\App\Facturacion\Resources\FacturaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFactura extends CreateRecord
{
    protected static string $resource = FacturaResource::class;

        public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }
 
        return [];
    }
}
