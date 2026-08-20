<?php

namespace App\Filament\App\NovaHub\Resources\Pagos\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\App\NovaHub\Resources\Pagos\PagoResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPagos extends ListRecords
{
    use AdvancedTables;
    protected static string $resource = PagoResource::class;
    protected static ?string $title = 'Pagos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo Servicio'),

           /* CreateAction::make()
                ->keyBindings(['mod+n'])
                ->label(
                    fn() => $this->getResourceUrl()
                )
                ->modalFooterActionsAlignment('right')
                ->modalFooterActions([


                    Action::make('pagar')
                        ->requiresConfirmation()

                        ->icon('euro')
                        ->label('Guardar y pagar')
                        ->keyBindings(['mod+p'])
                        ->postToUrl(
                            fn($record) => route('redsys.pay.fromPago', ['pago' => 1])

                        )
                        ->url(fn($record) => route('redsys.pay.fromPago', ['pago' =>  1]))
                        ->openUrlInNewTab(false),



                    Action::make('cancel')
                        ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                        ->label('Guardar')
                        ->color('secondary')
                        ->action(fn($record, $data) => $record->save($data))
                        ->successNotificationMessage("Pago guardado correctamente"),

                    Action::make('cancel')
                        ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                        ->label('Cerrar')
                        ->keyBindings(['mod+c'])
                        ->color('secondary')
                        ->close(true),

                    Action::make('Borrar')
                        ->icon(Heroicon::OutlinedTrash)
                        ->label('Borrar')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(fn($record, $data) => $record->delete($data))
                        ->keyBindings(['mod+d']),

                ]),*/
        ];
    }
}
