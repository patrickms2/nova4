<?php

namespace App\Filament\App\Resources\Pagos\Pages;

use App\Filament\App\Resources\Pagos\PagoResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions\CreateAction;
use Filament\Actions\Action;
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
                ->label('Nuevo Pago'),

             CreateAction::make()
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
                         ->icon('heroicon-o-arrow-right-on-rectangle')
                         ->label('Guardar')
                         ->color('secondary')
                         ->action(fn($record, $data) => $record->save($data))
                         ->successNotificationMessage("Pago guardado correctamente"),

                     Action::make('cancel')
                         ->icon('heroicon-o-arrow-right-on-rectangle')
                         ->label('Cerrar')
                         ->keyBindings(['mod+c'])
                         ->color('secondary')
                         ->close(true),

                     Action::make('Borrar')
                         ->icon('heroicon-o-trash')
                         ->label('Borrar')
                         ->requiresConfirmation()
                         ->color('danger')
                         ->action(fn($record, $data) => $record->delete($data))
                         ->keyBindings(['mod+d']),

                 ]),
        ];
    }
}
