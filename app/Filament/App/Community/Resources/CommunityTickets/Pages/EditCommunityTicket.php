<?php

namespace App\Filament\App\Community\Resources\CommunityTickets\Pages;

use App\Filament\App\Community\Resources\CommunityTickets\CommunityTicketResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityTicket extends EditRecord
{
    protected static string $resource = CommunityTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
