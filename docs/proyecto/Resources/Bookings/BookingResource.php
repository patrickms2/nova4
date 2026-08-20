<?php

namespace App\Filament\App\Resources\Bookings;

use Adultdate\FilamentBooking\Models\Booking\Booking;
use App\Filament\App\Resources\Bookings\Pages\CreateBooking;
use App\Filament\App\Resources\Bookings\Pages\EditBooking;
use App\Filament\App\Resources\Bookings\Pages\ListBookings;
use App\Filament\App\Resources\Bookings\Schemas\BookingForm;
use App\Filament\App\Resources\Bookings\Tables\BookingsTable;
use App\Filament\App\Resources\Bookings\Widgets\BookingStats;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static bool $isGloballySearchable = true;

    protected static ?string $slug = 'Bookings';

    protected static ?string $recordTitleAttribute = 'number';

    protected static string|UnitEnum|null $navigationGroup = 'Departamentos';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?int $navigationSort = 2;

    /**
     * Disable Filament tenant scoping for this resource to avoid
     * requiring a `team` relationship on the Booking model.
     */
    protected static bool $isScopedToTenant = false;

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            BookingStats::class,
        ];
    }

    /** @return Builder<Booking> */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['number'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        /** @var Booking $record */

        return [
            'Number' => $record->number,
        ];
    }

    /** @return Builder<Booking> */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['items']);
    }

    public static function getNavigationBadge(): ?string
    {
        $userId = Auth::id();

        if (!$userId) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = static::$model;

        $count = $modelClass::where('status', 'booked')
            ->where(function ($q) use ($userId) {
                $q->where('booking_user_id', $userId)
                    ->orWhere('service_user_id', $userId);
            })
            ->count();

        return (string)$count;
    }

    public static function mutateFormDataBeforeCreate(array $data): array
    {
        return static::calculateTotalPrice($data);
    }

    public static function mutateFormDataBeforeSave(array $data): array
    {
        return static::calculateTotalPrice($data);
    }

    protected static function calculateTotalPrice(array $data): array
    {
        $totalPrice = 0;

        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                $qty = isset($item['qty']) ? (int)$item['qty'] : 0;
                $unit = isset($item['unit_price']) ? (float)$item['unit_price'] : 0;
                $totalPrice += $qty * $unit;
            }
        }

        $data['total_price'] = $totalPrice;

        if (empty($data['currency'])) {
            $data['currency'] = 'SEK';
        }

        return $data;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }

    /** @return Builder<Booking> */
    public static function getTableQuery(): Builder
    {
        return parent::getTableQuery()->withoutGlobalScope(SoftDeletingScope::class);
    }
}
