<?php

namespace App\Filament\App\Resources\TaxistaTickets\Pages;

use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Models\TaxistaTicket;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditTaxistaTicket extends EditRecord 
{

    protected static string $resource = TaxistaTicketResource::class;

    protected static ?string $title = 'Editar ticket taxi';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('request_screenshot')
                ->label('Solicitar captura')
                ->icon(Heroicon::OutlinedCamera)
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Solicitar captura de pantalla')
                ->modalDescription(fn (): string => "Se enviará una solicitud al taxista ID {$this->getRecord()->user_id} para capturar su pantalla actual.")
                ->modalSubmitActionLabel('Enviar solicitud')
                ->action(function (TaxistaTicket $record, array $data, $action) {
                    if (! ($record->user_id ?? $record->created_by_user_id)) {
                        $this->sendNotification('danger', 'No se encontró el usuario destino.');
                        return;
                    }

                    $record->is_screen_shot = true;
                    $record->save();
                    $record->forceFill(['is_screen_shot' => false])->saveQuietly();

                    $this->sendNotification('success', 'Solicitud de captura enviada. Esperando autorización...');
                })
                ->visible(fn (): bool => ! PortalTaxistaContext::isPortalPanel()),
            DeleteAction::make(),
        ];
    }

    private function sendNotification(string $status, string $body): void
    {
        \Filament\Notifications\Notification::make()
            ->title($status === 'success' ? 'Enviado' : 'Error')
            ->body($body)
            ->color($status)
            ->{$status}()
            ->send();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            $data['user_id'] = PortalTaxistaContext::taxistaUserId();
        }

        return $data;
    }
}
