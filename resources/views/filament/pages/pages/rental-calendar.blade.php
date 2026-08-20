<x-filament-panels::page>
    @push('styles')
        <style>
            /* KML-0043: kursor wskazujacy klikalnosc na kafelkach rezerwacji */
            /* KML-0054: tak samo dla pasków blokad — klik prowadzi do edycji slotu */
            .fc-event.fc-rental-event,
            .fc-event.fc-rental-event *,
            .fc-event.fc-slot-event,
            .fc-event.fc-slot-event * {
                cursor: pointer;
            }
        </style>
    @endpush

    @livewire(\App\Filament\Widgets\RentalCalendarWidget::class)
</x-filament-panels::page>
