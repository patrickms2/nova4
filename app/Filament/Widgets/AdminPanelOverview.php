<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Restaurant;
use App\Models\RestaurantBooking;
use App\Models\RestaurantTable;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminPanelOverview extends BaseWidget
{
    protected ?string $pollingInterval = null;
    public static function canView(): bool
    {
        $user = Filament::auth()->user();
        return $user
            && ($user->role === 'super_admin');
    }

    protected function getStats(): array
    {
        $userCount = User::count();
        $restaurantCount = Restaurant::count();
        $bookingCount = RestaurantBooking::count();
        $tableCount = RestaurantTable::count();
        $userID = Booking::all();
        // if ($user->role === 'admin' && $user->section === 'restaurant') {
        //     $restaurant_id = $userID->resta;
        //     $bookingIds = RestaurantBooking::where('restaurant_id', $restaurant_id)->pluck('user_id')->unique();
        //     $userCount = User::whereIn('id', $bookingIds)->count();
        //     $restaurantCount = 1;
        //     $bookingCount = RestaurantBooking::where('restaurant_id', $restaurant_id)->count();
        //     $tableCount = RestaurantTable::where('restaurant_id', $restaurant_id)->count();
        // }
        return [
            Stat::make('Users', $userCount)
                ->description('Last 7 days')
                ->color('success'),
            Stat::make('Cities', $restaurantCount)
                ->description('Total Cities')
                ->color('danger'),
            Stat::make('Employee Bookings', $bookingCount)
                ->description('Total Employees')
                ->color('primary'),
            Stat::make('Employee Tables', $tableCount)
        ];
    }
}
