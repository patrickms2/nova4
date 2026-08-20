<?php

namespace App\Filament\App\Resources\Bookings\Pages;

use App\Filament\App\Resources\Bookings\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListBookings extends ListRecords
{
    use ExposesTableToWidgets;

    protected static string $resource = BookingResource::class;

    protected function getActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('Show All'),
            'booked' => Tab::make()->query(fn ($query) => $query->where('status', 'booked')),
            'confirmed' => Tab::make()->query(fn ($query) => $query->where('status', 'confirmed')),
            'processing' => Tab::make()->query(fn ($query) => $query->where('status', 'processing')),
            'cancelled' => Tab::make()->query(fn ($query) => $query->where('status', 'cancelled')),
            'updated' => Tab::make()->query(fn ($query) => $query->where('status', 'updated')),
            'completed' => Tab::make()->query(fn ($query) => $query->where('status', 'completed')),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return BookingResource::getWidgets();
    }
}
