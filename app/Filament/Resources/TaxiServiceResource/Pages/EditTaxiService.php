<?php

namespace App\Filament\Resources\TaxiServiceResource\Pages;
use App\Filament\Resources\TaxiServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditTaxiService extends EditRecord
{
    protected static string $resource = TaxiServiceResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
