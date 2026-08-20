<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create users for drivers first
        $driver1 = User::create([
            'email' => 'driver1@example.com',
            'password' => Hash::make('password'),

            'first_name' => 'Michael',
            'last_name' => 'Johnson',
            'phone' => '555-111-2222',
            'user_type' => 'driver',
            'registration_date' => now(),
            'status' => 1, // 1=Active, 0=Inactive
            'is_email_verified' => true,
            'is_phone_verified' => true,
        ]);

        $driver2 = User::create([
            'email' => 'driver2@example.com',
            'password' => Hash::make('password'),

            'first_name' => 'Sarah',
            'last_name' => 'Williams',
            'phone' => '555-333-4444',
            'user_type' => 'driver',
            'registration_date' => now(),
            'status' => 1, // 1=Active, 0=Inactive
            'is_email_verified' => true,
            'is_phone_verified' => true,
        ]);

        $driver3 = User::create([
            'email' => 'driver3@example.com',
            'password' => Hash::make('password'),

            'first_name' => 'David',
            'last_name' => 'Brown',
            'phone' => '555-555-6666',
            'user_type' => 'driver',
            'registration_date' => now(),
            'status' => 1, // 1=Active, 0=Inactive
            'is_email_verified' => true,
            'is_phone_verified' => true,
        ]);

        // Create drivers
        Driver::create([
            'user_id' => 2,
            'taxi_service_id' => 1, // City Cabs
            'license_number' => 'DL12345678',
            'experience_years' => 5,
            'rating' => 4.7,
            'is_active' => true,
        ]);

        Driver::create([
            'user_id' => 3,
            'taxi_service_id' => 2, // Express Taxi
            'license_number' => 'DL87654321',
            'experience_years' => 3,
            'rating' => 4.5,
            'is_active' => true,
        ]);

        Driver::create([
            'user_id' => 4,
            'taxi_service_id' => 3, // Luxury Rides
            'license_number' => 'DL11223344',
            'experience_years' => 7,
            'rating' => 4.9,
            'is_active' => true,
        ]);
    }
}
