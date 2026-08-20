<?php

namespace App\Filament\Resources\TaxiServiceResource\Pages;
use App\Filament\Resources\TaxiServiceResource;
use Filament\Resources\Pages\CreateRecord;
class CreateTaxiService extends CreateRecord
{
    protected static string $resource = TaxiServiceResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
