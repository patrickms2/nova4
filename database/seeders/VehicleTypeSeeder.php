<?php

namespace Database\Seeders;

use App\Models\VehicleType;
use Illuminate\Database\Seeder;

class VehicleTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create vehicle types for City Cabs (TaxiServiceID: 1)
        VehicleType::create([
            'taxi_service_id' => 1,
            'name' => 'Economy',
            'description' => 'Affordable sedan for up to 4 passengers',
            'max_passengers' => 4,
            'price_per_km' => 1.50,
            'base_price' => 5.00,
            'image_url' => 'images/vehicles/economy.png',
            'is_active' => true,
        ]);

        VehicleType::create([
            'taxi_service_id' => 1,
            'name' => 'SUV',
            'description' => 'Spacious SUV for up to 6 passengers',
            'max_passengers' => 6,
            'price_per_km' => 2.25,
            'base_price' => 7.50,
            'image_url' => 'images/vehicles/suv.png',
            'is_active' => true,
        ]);

        // Create vehicle types for Express Taxi (TaxiServiceID: 2)
        VehicleType::create([
            'taxi_service_id' => 2,
            'name' => 'Standard',
            'description' => 'Standard sedan for up to 4 passengers',
            'max_passengers' => 4,
            'price_per_km' => 1.75,
            'base_price' => 6.00,
            'image_url' => 'images/vehicles/standard.png',
            'is_active' => true,
        ]);

        VehicleType::create([
            'taxi_service_id' => 2,
            'name' => 'Minivan',
            'description' => 'Minivan for up to 7 passengers',
            'max_passengers' => 7,
            'price_per_km' => 2.50,
            'base_price' => 8.00,
            'image_url' => 'images/vehicles/minivan.png',
            'is_active' => true,
        ]);

        // Create vehicle types for Luxury Rides (TaxiServiceID: 3)
        VehicleType::create([
            'taxi_service_id' => 3,
            'name' => 'Premium',
            'description' => 'Luxury sedan for up to 4 passengers',
            'max_passengers' => 4,
            'price_per_km' => 3.00,
            'base_price' => 10.00,
            'image_url' => 'images/vehicles/premium.png',
            'is_active' => true,
        ]);

        VehicleType::create([
            'taxi_service_id' => 3,
            'name' => 'Executive',
            'description' => 'Executive SUV for up to 6 passengers',
            'max_passengers' => 6,
            'price_per_km' => 4.00,
            'base_price' => 15.00,
            'image_url' => 'images/vehicles/executive.png',
            'is_active' => true,
        ]);
    }
}
