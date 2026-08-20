<?php

namespace App\Filament\App\Resources\Bookings\Pages;

use App\Filament\App\Resources\Bookings\Widgets\BookingCalendar;
use App\Models\BookingCalendar as BookingCalendarModel;
use App\Models\BookingDepartment;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class DashboardBooking extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static ?string $navigationLabel = 'Dash';

    protected static ?string $title = '';

    protected static string $routePath = 'service/booking';

    protected static ?string $slug = 'dashboard';

    //   protected string $view = 'filament-booking::pages.page';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function getWidgets(): array
    {
        return [
            BookingCalendar::class,
        ];
    }

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public static function getNavigationLabel(): string
    {
        return ''.Str::ucfirst('Booking') ?? 'User';
    }

    public static function getNavigationBadge(): ?string
    {
        return now()->timezone('Europe/Stockholm')->format('H:m');

    }

    public static function getNavigationIcon(): string
    {
        return 'heroicon-o-calendar-date-range';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    use HasFiltersForm;

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
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
                            ->options(fn (Get $get) => BookingCalendarModel::query()
                                ->forDepartment($get('booking_department_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->label('Tekninker')
                            ->placeholder('Select a calendar owner')
                            ->searchable()
                            ->reactive()
                            ->afterStateUpdated(function () {
                                $this->dispatch('refreshCalendar');
                            }),

                        DatePicker::make('startDate')
                            ->maxDate(fn (Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->minDate(fn (Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public function getPermissionCheckClosure(): \Closure
    {
        return fn (string $widgetClass) => true;
    }
}
