<?php

namespace App\Filament\App\Community\Resources\CommunityTickets\Pages;

use App\Filament\App\Community\Resources\CommunityTickets\CommunityTicketResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityTicket extends CreateRecord
{
    protected static string $resource = CommunityTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'created_by' => auth()->id()];
    }
}
