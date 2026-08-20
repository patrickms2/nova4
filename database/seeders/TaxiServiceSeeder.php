<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\TaxiService;
use Illuminate\Database\Seeder;

class TaxiServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create taxi services
        TaxiService::create([
            'name' => 'City Cabs',
            'description' => 'Reliable taxi service for city transportation',
            'location_id' => 1, // Assuming location ID 1 exists
            'average_rating' => 4.5,
            'total_ratings' => 120,
            'logo_url' => 'images/taxi/citycabs_logo.png',
            'website' => 'https://citycabs.example.com',
            'phone' => '123-456-7890',
            'email' => 'info@citycabs.example.com',
            'is_active' => true,
            'manager_id' => 1, // Assuming user ID 1 is admin
        ]);

        TaxiService::create([
            'name' => 'Express Taxi',
            'description' => 'Fast and efficient taxi service',
            'location_id' => 2, // Assuming location ID 2 exists
            'average_rating' => 4.2,
            'total_ratings' => 85,
            'logo_url' => 'images/taxi/expresstaxi_logo.png',
            'website' => 'https://expresstaxi.example.com',
            'phone' => '987-654-3210',
            'email' => 'info@expresstaxi.example.com',
            'is_active' => true,
            'manager_id' => 1,
        ]);

        TaxiService::create([
            'name' => 'Luxury Rides',
            'description' => 'Premium taxi service with luxury vehicles',
            'location_id' => 3, // Assuming location ID 3 exists
            'average_rating' => 4.8,
            'total_ratings' => 65,
            'logo_url' => 'images/taxi/luxuryrides_logo.png',
            'website' => 'https://luxuryrides.example.com',
            'phone' => '555-123-4567',
            'email' => 'info@luxuryrides.example.com',
            'is_active' => true,
            'manager_id' => 1,
        ]);
    }
}
