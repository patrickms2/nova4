<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\BookingResource\Pages;
use App\Filament\Resources\BookingResource\RelationManagers;
use App\Models\Booking;
use App\Models\Hotel;
use App\Models\Villa;
use App\Models\Restaurant;
use App\Models\Tour;
use App\Models\TourSchedule;
use App\Models\User;
use BackedEnum;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Filament\Tables\Enums\FiltersLayout;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $recordTitleAttribute = 'booking_reference';

    protected static ?string $navigationLabel = 'Todas las reservas';

    protected static string|\UnitEnum|null $navigationGroup = 'Reservas';
    protected static ?string $navigationParentGroup = 'Reservas';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) cache()->remember(
                    static::class . '.navigation-badge',
                    now()->addMinute(),
                    fn () => static::getModel()::where('status', 'Pending')->count()
                );
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::where('status', 'Pending')->count() > 0
            ? 'warning'
            : 'primary';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([

                Section::make('Booking Information')
                    ->schema([
                        TextInput::make('booking_reference')
                            ->default(fn () => 'BK-'.strtoupper(Str::random(10)))
                            ->disabled(fn ($record) => $record !== null)
                            ->required(),
                        /*Select::make('user_id')
                            ->relationship('user', 'first_name')
                            ->getOptionLabelFromRecordUsing(fn (Model $record): string => (string) ($record->first_name ?: $record->name ?: $record->email ?: 'User #'.$record->getKey()))
                            ->searchable()
                            ->preload(),*/
                        Hidden::make('user_id')
                            ->default(auth()->id()),
                        Select::make('booking_type')
                            ->options([
                                'Tour' => 'Tour',
                                'Hotel' => 'Hotel',
                                'Villa' => 'Villa',
                                'Taxi' => 'Taxi',
                                'Restaurant' => 'Restaurant',
                                'Package' => 'Package',
                            ])
                            ->required()
                            ->live()
                            ->default('Tour')
                            ->afterStateUpdated(fn (Set $set) => $set('booking_details', null)),
                        Forms\Components\DateTimePicker::make('booking_date')
                            ->default(now()),
                    ])->columns(2),
                Section::make('Booking Details')
                    ->schema(fn (Get $get) => match ($get('booking_type')) {
                        'Tour' => [
                            Select::make('tour_booking.tour_id')
                                ->label('Tour')
                                ->options(fn (): array => Tour::query()
                                    ->orderBy('tour_name')
                                    ->get()
                                    ->mapWithKeys(fn (Tour $tour): array => [$tour->id => (string) ($tour->name ?: $tour->tour_name ?: 'Tour #'.$tour->id)])
                                    ->all())
                                ->searchable()
                                ->preload(),
                            Select::make('tour_booking.schedule_id')
                                ->label('Schedule')
                                ->options(fn (): array => TourSchedule::query()
                                    ->latest('start_date')
                                    ->get()
                                    ->mapWithKeys(fn (TourSchedule $schedule): array => [$schedule->id => (string) ($schedule->start_date?->format('Y-m-d') ?: 'Schedule #'.$schedule->id)])
                                    ->all())
                                ->searchable()
                                ->preload(),
                            TextInput::make('tour_booking.number_of_adults')
                                ->label('Number of Adults')
                                ->numeric()
                                ->default(1),
                            TextInput::make('tour_booking.number_of_children')
                                ->label('Number of Children')
                                ->default(0),
                            Select::make('tour_booking.guide_id')
                                ->label('Guide')
                                ->options(fn (): array => User::query()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (User $user): array => [$user->id => (string) ($user->name ?: $user->first_name ?: $user->email ?: 'User #'.$user->id)])
                                    ->all())
                                ->searchable()
                                ->preload(),
                        ],
                        'Villa' => [
                            Select::make('villa_booking.villa_id')
                                ->label('Villa')
                                ->options(fn (): array => Villa::query()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Villa $villa): array => [$villa->id => (string) ($villa->name ?: 'Villa #'.$villa->id)])
                                    ->all()),

                            TextInput::make('villa_booking.number_of_adults')
                                ->label('Number of Adults')
                                ->numeric()
                                ->default(1),
                            TextInput::make('villa_booking.number_of_children')
                                ->label('Number of Children')
                                ->default(0),
                        ],
                        'Hotel' => [
                            Select::make('hotel_booking.hotel_id')
                                ->label('Hotel')
                                 ->options(fn (): array => Hotel::query()
                                    ->orderBy('name')
                                    ->get()
                                    ->mapWithKeys(fn (Hotel $hotel): array => [$hotel->id => (string) ($hotel->name ?: $hotel->name ?: 'Hotel #'.$hotel->id)])
                                    ->all()),
                            Select::make('hotel_booking.room_type_id')
                                ->label('Room Type')
                                ->relationship('hotel_booking.roomType', 'name')->searchable(),
                            DatePicker::make('hotel_booking.check_in_date')
                                ->label('Check-in Date'),
                            DatePicker::make('hotel_booking.check_out_date')
                                ->label('Check-out Date')
                                ->required()
                                ->after('hotel_booking.check_in_date'),
                            TextInput::make('hotel_booking.number_of_rooms')
                                ->label('Number of Rooms'),
                            TextInput::make('hotel_booking.number_of_guests')
                                ->label('Number of Guests'),
                        ],
                        'Taxi' => [
                            Select::make('taxi_booking.taxi_service_id')
                                ->label('Taxi Service')
                                ->relationship('taxiBooking', 'name')->searchable(),
                            Select::make('taxi_booking.vehicle_type_id')
                                ->label('Vehicle Type')
                                ->relationship('taxiBooking.vehicleType', 'name')->searchable(),
                            Select::make('taxi_booking.pickup_location_id')
                                ->label('Pickup Location')
                                ->relationship('taxiBooking.pickupLocation', 'name')->searchable(),
                            Select::make('taxi_booking.dropoff_location_id')
                                ->label('Dropoff Location')
                                ->relationship('taxiBooking.dropoffLocation', 'name')->searchable(),
                            Forms\Components\DateTimePicker::make('taxi_booking.pickup_date_time')
                                ->label('Pickup Date & Time'),
                        ],
                        'Restaurant' => [
                            Select::make('restaurant_booking.restaurant_id')
                                ->label('Restaurant')
                                ->options(fn (): array => Restaurant::query()
                                    ->orderBy('restaurant_name')
                                    ->get()
                                    ->mapWithKeys(fn (Restaurant $restaurant): array => [$restaurant->id => (string) ($restaurant->restaurant_name ?: 'Restaurant #'.$restaurant->id)])
                                    ->all()),
                            DatePicker::make('restaurant_booking.reservation_date')
                                ->label('Reservation Date'),
                            Forms\Components\TimePicker::make('restaurant_booking.reservation_time')
                                ->label('Reservation Time'),
                            TextInput::make('restaurant_booking.number_of_guests'),
                            TextInput::make('restaurant_booking.duration')
                                ->label('Duration (minutes)')
                                ->default(120),
                        ],
                        'Package' => [
                            Select::make('package_booking.package_id')
                                ->label('Travel Package')
                                ->relationship('packageBooking.package', 'name')->searchable(),
                            DatePicker::make('package_booking.start_date')
                                ->label('Start Date'),
                            TextInput::make('package_booking.number_of_adults'),
                            TextInput::make('package_booking.number_of_children'),
                        ],
                    })
                    ->columns(2),
                Section::make('Payment Information')
                    ->schema([
                        TextInput::make('total_price')
                            ->numeric()
                            ->prefix('$'),
                        TextInput::make('discount_amount')
                            ->default(0),
                        Select::make('payment_status')
                            ->options([
                                1 => 'Pending',
                                2 => 'Paid',
                                3 => 'Refunded',
                                4 => 'Failed',
                            ])
                            ->default(1),
                    ])
                    ->columns(3),
                Section::make('Booking Status')
                    ->schema([
                        Select::make('status')
                            ->options([
                                'Pending' => 'Pending',
                                'Confirmed' => 'Confirmed',
                                'Cancelled' => 'Cancelled',
                                'Completed' => 'Completed',
                            ])
                            ->default('Pending')
                            ->live(),
                        Forms\Components\Textarea::make('cancellation_reason')
                            ->visible(fn (Get $get) => $get('status') === 'Cancelled'),
                    ]),
                Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('special_requests'),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_reference')
                    ->label('Ref.')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('booking_type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Tour' => 'Tour',
                        'Hotel' => 'Hotel',
                        'Taxi' => 'Taxi',
                        'Villa' => 'Villa',
                        'Restaurant' => 'Restaurant',
                        'Package' => 'Package',
                        default => 'Unknown',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Tour' => 'success',
                        'Hotel' => 'info',
                        'Taxi' => 'warning',
                        'Villa' => 'info',
                        'Restaurant' => 'danger',
                        'Package' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('booking_date')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->money('EUR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('payment_status')
                ->label('Estado Pago')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Refunded' => 'Refunded',
                        'Failed' => 'Failed',
                        default => 'Unknown',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'success',
                        'Paid' => 'info',
                        'Failed' => 'warning',
                        'Refunded' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Confirmed' => 'success',
                        'Cancelled' => 'danger',
                        'Completed' => 'info',
                        default => 'Unknown',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Confirmed' => 'success',
                        'Cancelled' => 'danger',
                        'Completed' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('last_updated')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('booking_type')
                    ->label('Tipo de Reserva')
                    ->options([
                        'Tour' => 'Tour',
                        'Hotel' => 'Hotel',
                        'Taxi' => 'Taxi',
                        'Villa' => 'Villa',
                        'Restaurant' => 'Restaurant',
                        'Package' => 'Package',
                    ])
                    ->indicateUsing(),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'Pending' => 'Pending',
                        'Confirmed' => 'Confirmed',
                        'Cancelled' => 'Cancelled',
                        'Completed' => 'Completed',
                    ])
                    ->indicateUsing(),
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Estado Pago')
                    ->options([
                        'Pending' => 'Pending',
                        'Paid' => 'Paid',
                        'Refunded' => 'Refunded',
                        'Failed' => 'Failed',
                    ])
                    ->indicateUsing(),
                Tables\Filters\Filter::make('booking_date')
                    ->schema([
                        DatePicker::make('booking_from')
                        ->label('Desde'),
                        DatePicker::make('booking_until')
                        ->label('Hasta'),
                    ])
                    ->indicateUsing()
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['booking_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('booking_date', '>=', $date),
                            )
                            ->when(
                                $data['booking_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('booking_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\ForceDeleteAction::make(),
                Actions\RestoreAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                    Actions\ForceDeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                    Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->groups([
                "booking_type",
                "status",
                "payment_status",
            ])
            ->filtersFormColumns(2)
            ->filtersLayout(FiltersLayout::AboveContentCollapsible);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\TourBookingRelationManager::class,
            RelationManagers\HotelBookingRelationManager::class,
            RelationManagers\TaxiBookingRelationManager::class,
            RelationManagers\RestaurantBookingRelationManager::class,
            RelationManagers\PackageBookingRelationManager::class,
            RelationManagers\VillaBookingRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
            'calendar-booking' => Pages\CalendarBooking::route('/calendar-booking'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
