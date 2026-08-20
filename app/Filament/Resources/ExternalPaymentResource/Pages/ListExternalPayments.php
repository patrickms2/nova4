<?php

namespace App\Filament\Resources\ExternalPaymentResource\Pages;

use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\ExternalPaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
class ListExternalPayments extends ListRecords
{
    use AdvancedTables;
    
    protected static string $resource = ExternalPaymentResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Calendar')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(static::getResource()::getUrl('calendar')),
            Actions\CreateAction::make(),
        ];
    }
}
