<?php

namespace App\Filament\Resources\NovaWhatsappChannels\Pages;

use App\Filament\Resources\NovaWhatsappChannels\NovaWhatsappChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNovaWhatsappChannels extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = NovaWhatsappChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
