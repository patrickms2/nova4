<?php

namespace App\Filament\App\Resources\Bookings\Pages;

use App\Filament\App\Resources\Bookings\Widgets\BokningCalendar;
use App\Models\BookingCalendar as BookingCalendarModel;
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

class DashboardBokning extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    //    protected static ?string $navigationLabel = 'Dash';

    protected static ?string $title = '';

    protected static string $routePath = 'service/bokning';

    protected static ?string $slug = 'dashboard';

    //  protected string $view = 'filament-booking::pages.page';

    public static function shouldRegisterNavigation(): bool
    {
        return true;
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
                        Select::make('booking_calendars')
                            ->options(fn () => BookingCalendarModel::pluck('name', 'id')->toArray())
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

    public function getMaxContentWidth(): Width
    {
        return Width::Full;
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 3;
    }

    public function getWidgetsColumns(): int|array
    {
        return 2;
    }

    public function getColumns(): int|array
    {
        return 2;
    }

    public function getHeaderWidgets(): array
    {
        return [
            BokningCalendar::class,
            BokningCalendar::class,
        ];
    }
}
