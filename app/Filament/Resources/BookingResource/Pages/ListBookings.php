<?php

namespace App\Filament\Resources\BookingResource\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\BookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBookings extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Calendar')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(static::getResource()::getUrl('calendar-booking')),
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('All')
                ->badge(fn(): int => BookingResource::getEloquentQuery()->count()),
            'pending' => $this->makeStatusTab('pending'),
            'approved' => $this->makeStatusTab('approved'),
            'cancelled' => $this->makeStatusTab('cancelled'),
        ];
    }

    private function makeStatusTab(string $status): Tab
    {
        return Tab::make()
            ->label($status)
            ->badge(fn(): int => BookingResource::getEloquentQuery()->where('status', $status)->count())
            ->badgeColor($status === 'pending' ? 'warning' : ($status === 'approved' ? 'success' : 'danger'))
            ->icon($status === 'pending' ? Heroicon::OutlinedClock : ($status === 'approved' ? Heroicon::OutlinedCheckCircle : Heroicon::OutlinedXCircle))
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', $status));
    }
}
