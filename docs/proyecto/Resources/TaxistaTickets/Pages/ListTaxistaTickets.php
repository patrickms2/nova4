<?php

namespace App\Filament\App\Resources\TaxistaTickets\Pages;

use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Models\TaxistaTicket;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;

class ListTaxistaTickets extends ListRecords
{
    
        use AdvancedTables;

    protected static string $resource = TaxistaTicketResource::class;

    protected static ?string $title = 'Tickets';

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Mis Tickets';
        }

        return 'Tickets';
    }

    public function getSubheading(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Incidencias y consultas del taxista.';
        }

        return null;
    }

    protected function getHeaderActions(): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return [
                CreateAction::make()
                    ->label('Nuevo ticket')
                    ->icon('heroicon-o-plus')
                    ->hiddenLabel(),
            ];
        }

        return [
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->url(TaxistaTicketResource::getUrl('calendar')),
            CreateAction::make()
                ->label('Nuevo ticket'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return TaxistaTicketResource::getWidgets();
    }
}
