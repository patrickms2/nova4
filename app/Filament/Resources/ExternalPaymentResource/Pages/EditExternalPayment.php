<?php

namespace App\Filament\Resources\ExternalPaymentResource\Pages;
use App\Filament\Resources\ExternalPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExternalPayment extends EditRecord
{
    protected static string $resource = ExternalPaymentResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
