<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Tickets\Pages;

use App\Filament\App\Resources\Tickets\TicketResource;
use Filament\Resources\Pages\ViewRecord;

class ViewTicket extends ViewRecord
{
    protected static string $resource = TicketResource::class;
}
