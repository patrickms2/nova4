<?php

namespace App\Filament\App\Community\Resources\CommunityTickets\Pages;

use App\Filament\App\Community\Resources\CommunityTickets\CommunityTicketResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityTicket extends ViewRecord
{
    protected static string $resource = CommunityTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
