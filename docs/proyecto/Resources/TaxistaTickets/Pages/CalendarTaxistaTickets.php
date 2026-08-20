<?php

namespace App\Filament\App\Resources\TaxistaTickets\Pages;

use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Filament\App\Resources\TaxistaTickets\Widgets\TaxistaTicketsCalendar;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Widgets\Widget;

class CalendarTaxistaTickets extends Page
{
    protected static string $resource = TaxistaTicketResource::class;

    protected static ?string $title = 'Calendario de tickets';

    protected string $view = 'filament.app.resources.taxista-tickets.pages.calendar-taxista-tickets';

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Calendario de Mis Tickets';
        }

        return 'Calendario de tickets';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('listado')
                ->label('Listado')
                ->icon('heroicon-o-list-bullet')
                ->url(TaxistaTicketResource::getUrl('index')),
            Action::make('nuevo')
                ->label('Nuevo ticket')
                ->icon('heroicon-o-plus')
                ->url(TaxistaTicketResource::getUrl('create')),
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            TaxistaTicketsCalendar::class,
        ];
    }
}
