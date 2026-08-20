<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalBookings;

use App\Filament\App\NovaHub\Resources\NovaExternalBookings\Pages\ListNovaExternalBookings;
use App\Filament\App\NovaHub\Resources\NovaExternalBookings\Pages\ViewNovaExternalBooking;
use App\Filament\App\NovaHub\Resources\NovaExternalBookings\Tables\NovaExternalBookingsTable;
use App\Models\NovaExternalBooking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaExternalBookingResource extends Resource
{
    protected static ?string $model = NovaExternalBooking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static \UnitEnum|string|null $navigationGroup = 'Nova Hub';
    protected static \UnitEnum|string|null $navigationParentGroup = 'Reservas';

    protected static ?string $navigationLabel = 'Booking Exports';

    protected static ?string $modelLabel = 'Booking Export';

    protected static ?string $pluralModelLabel = 'Booking Exports';

    protected static bool $isScopedToTenant = false;

    protected static ?int $navigationSort = 23;

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return NovaExternalBookingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNovaExternalBookings::route('/'),
            'view' => ViewNovaExternalBooking::route('/{record}'),
        ];
    }
}
