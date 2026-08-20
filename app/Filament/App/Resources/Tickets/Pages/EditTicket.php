<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tickets\Pages;

use App\Filament\App\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\EditRecord;

class EditTicket extends EditRecord
{
    protected static string $resource = TicketResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Ticket actualizado correctamente';
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return 'Ticket eliminado correctamente';
    }
}
