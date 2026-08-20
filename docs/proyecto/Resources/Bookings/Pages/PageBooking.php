<?php

namespace App\Filament\App\Resources\Bookings\Pages;

// use Adultdate\FilamentBooking\Filament\Widgets\BookingCalendarWidget;
use App\Filament\App\Resources\Bookings\Widgets\BookingCalendar;
use App\Models\BookingCalendar as BookingCalendarModel;
use App\Models\BookingDepartment;
use App\UserRole;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Page as BasePage;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Widgets\Widget;
use UnitEnum;

class PageBooking extends BasePage
{
    use HasFiltersForm;

    protected string $view = 'filament-booking::pages.page';

    protected static ?string $navigationLabel = 'Booking';

    // protected static string $routePath = 'services';

    protected static string $routePath = 'page/booking';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $sort = 1;

    protected static string|UnitEnum|null $navigationGroup = '';

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $isDiscovered = false;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('booking_department_id')
                    ->label('Departamento')
                    ->options(fn () => BookingDepartment::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->placeholder('Selecciona un departamento')
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set) {
                        $set('booking_calendars', null);
                        $this->dispatch('refreshCalendar');
                    }),
                Select::make('booking_calendars')
                    ->label('Calendars')
                    ->options(fn (Get $get) => BookingCalendarModel::query()
                        ->forDepartment($get('booking_department_id'))
                        ->whereHas('owner', fn ($query) => $query->where('role', UserRole::SERVICE->value))
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->toArray()
                    )
                    ->placeholder('Select a calendar')
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function () {
                        $this->dispatch('refreshCalendar');
                    }),
            ]);
    }

    /**
     * Return header widgets for the page.
     *
     * @return array<class-string<Widget>>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            BookingCalendar::class,
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make('create-new-booking')::make()
                ->label('New schedule')
                ->icon('heroicon-o-calendar'), ];
    }
}
