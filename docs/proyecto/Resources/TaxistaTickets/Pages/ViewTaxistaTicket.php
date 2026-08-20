<?php

namespace App\Filament\App\Resources\TaxistaTickets\Pages;

use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketInfolist;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use App\Models\TaxistaTicket;
use Filament\Actions\Action;
use App\Support\PortalTaxistaContext;
use App\Event\ScreenshotRequested;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

class ViewTaxistaTicket extends ViewRecord
{
    protected static string $resource = TaxistaTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            Action::make('request_screenshot')
                ->label('Solicitar captura')
                ->icon('heroicon-o-camera')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Solicitar captura de pantalla')
                ->modalDescription('Se enviará una solicitud al usuario para capturar su pantalla actual.')
                ->modalSubmitActionLabel('Enviar solicitud')
                ->action(function (TaxistaTicket $record): void {
                    if (!$record->id) {
                        $this->sendNotification('danger', 'Guarda el ticket antes de solicitar la captura.');
                        return;
                    }

                    if (!($record->user_id ?? $record->created_by_user_id)) {
                        $this->sendNotification($record->user_id, 'danger', 'No se encontró el usuario destino seleccionado.');
                        return;
                    }

                    $record->is_screen_shot = true;
                    $record->save();
                    $record->forceFill(['is_screen_shot' => false])->saveQuietly();

                    $this->sendNotification($record->user_id, 'success', 'Solicitud de captura enviada. Esperando autorización...');
                })
                ->visible(fn(): bool => !PortalTaxistaContext::isPortalPanel()),
        ];
    }

    private static function sendNotification($userId, $status, $message): void
    {
        $userCompleted = auth()->user()->name;
        $record = TaxistaTicket::find($userId);

        Notification::make()
            ->title('Solicitud finalizada')
            ->success()
            ->icon(Heroicon::CheckCircle)
            ->body("{$userCompleted} ha enviado solicitud de captura de pantalla a  ticket." . $message);
        //->sendToDatabase([$userId]);
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function infolist(Schema $schema): Schema
    {
        return TaxistaTicketInfolist::configure($schema);
    }
}
