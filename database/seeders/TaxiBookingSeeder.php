<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\TaxiBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TaxiBookingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create bookings first
        $booking1 = Booking::create([
            'user_id' => 2, // Regular user (user1@example.com)
            'booking_reference' => 'TX-'.Str::random(8),
            'booking_date' => now()->subDays(5),
            'total_price' => 25.50,
            'status' => 'Completed', // Pending, Confirmed, Cancelled, Completed
            'payment_status' => 2, // 1=Pending, 2=Paid, 3=Refunded, 4=Failed
            'booking_type' => 3, // 3=Taxi
            'special_requests' => null,
        ]);

        $booking2 = Booking::create([
            'user_id' => 3, // Regular user (user2@example.com)
            'booking_reference' => 'TX-'.Str::random(8),
            'booking_date' => now()->subDays(2),
            'total_price' => 42.75,
            'status' => 'Confirmed', // Pending, Confirmed, Cancelled, Completed
            'payment_status' => 2, // 1=Pending, 2=Paid, 3=Refunded, 4=Failed
            'booking_type' => 3, // 3=Taxi
            'special_requests' => null,
        ]);

        $booking3 = Booking::create([
            'user_id' => 2, // Regular user (user1@example.com)
            'booking_reference' => 'TX-'.Str::random(8),
            'booking_date' => now(),
            'total_price' => 75.00,
            'status' => 'Pending', // Pending, Confirmed, Cancelled, Completed
            'payment_status' => 1, // 1=Pending, 2=Paid, 3=Refunded, 4=Failed
            'booking_type' => 3, // 3=Taxi
            'special_requests' => null,
        ]);

        // Create taxi bookings
        TaxiBooking::create([
            'booking_id' => 1,
            'taxi_service_id' => 1, // City Cabs
            'vehicle_type_id' => 1, // Economy
            'pickup_location_id' => 1, // Assuming location ID 1 exists
            'dropoff_location_id' => 2, // Assuming location ID 2 exists
            'pickup_date_time' => now()->subDays(5)->addHours(2),
            'estimated_distance' => 12.5,
            'driver_id' => 1, // Driver from City Cabs
            'vehicle_id' => 1, // Toyota Corolla
        ]);

        TaxiBooking::create([
            'booking_id' => 2,
            'taxi_service_id' => 2, // Express Taxi
            'vehicle_type_id' => 3, // Standard
            'pickup_location_id' => 3, // Assuming location ID 3 exists
            'dropoff_location_id' => 1, // Assuming location ID 1 exists
            'pickup_date_time' => now()->addDays(1),
            'estimated_distance' => 18.2,
            'driver_id' => 2, // Driver from Express Taxi
            'vehicle_id' => 4, // Nissan Altima
        ]);

        TaxiBooking::create([
            'booking_id' => 3,
            'taxi_service_id' => 3, // Luxury Rides
            'vehicle_type_id' => 5, // Premium
            'pickup_location_id' => 2, // Assuming location ID 2 exists
            'dropoff_location_id' => 3, // Assuming location ID 3 exists
            'pickup_date_time' => now()->addDays(3),
            'estimated_distance' => 15.0,
            'driver_id' => 3, // Driver from Luxury Rides
            'vehicle_id' => 6, // Mercedes-Benz E-Class
        ]);
    }
}
