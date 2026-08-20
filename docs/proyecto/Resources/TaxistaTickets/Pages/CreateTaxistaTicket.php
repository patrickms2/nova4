<?php

namespace App\Filament\App\Resources\TaxistaTickets\Pages;

use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Support\PortalTaxistaContext;   
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use App\Models\TaxistaTicket;

class CreateTaxistaTicket extends CreateRecord
{
    protected static string $resource = TaxistaTicketResource::class;

    protected static ?string $title = 'Nuevo ticket taxi';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = $data['created_by_user_id'] ?? auth()->id();
        $data['user_id'] = $data['user_id'] ?? auth()->id();

        if (PortalTaxistaContext::isPortalPanel()) {
            $data['user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('request_screenshot')
                ->label('Solicitar captura')
                ->icon('heroicon-o-camera')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Solicitar captura de pantalla')
                ->modalDescription('Se enviará una solicitud al usuario para capturar su pantalla actual.')
                ->modalSubmitActionLabel('Enviar solicitud')
                ->action(function (TaxistaTicket $record): void {
                    if (! $record->id) {
                        $this->sendNotification('danger', 'Guarda el ticket antes de solicitar la captura.');
                        return;
                    }

                    if (! ($record->user_id ?? $record->created_by_user_id)) {
                        $this->sendNotification('danger', 'No se encontró el usuario destino seleccionado.');
                        return;
                    }

                    $record->is_screen_shot = true;
                    $record->save();
                    $record->forceFill(['is_screen_shot' => false])->saveQuietly();

                    $this->sendNotification('success', 'Solicitud de captura enviada. Esperando autorización...');
                })
                ->visible(fn (): bool => ! PortalTaxistaContext::isPortalPanel()),
        ];
    }

    public function getMaxContentWidth():Width|string|null
    {
        return Width::Full;
    }
}
