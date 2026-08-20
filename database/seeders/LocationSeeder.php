<?php

namespace Database\Seeders;

use App\Models\City;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create cities first if they don't exist
        $city1 = City::firstOrCreate(
            ['name' => 'New York'],
            [
                'country_id' => 1, // Assuming USA
                'is_popular' => true,
            ]
        );

        $city2 = City::firstOrCreate(
            ['name' => 'Los Angeles'],
            [
                'country_id' => 1, // Assuming USA
                'is_popular' => true,
            ]
        );

        $city3 = City::firstOrCreate(
            ['name' => 'Chicago'],
            [
                'country_id' => 1, // Assuming USA
                'is_popular' => true,
            ]
        );

        // Create locations
        Location::create([
            'name' => 'Downtown',
            'city_id' => 1,
            'description' => 'Downtown area with major attractions',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'is_popular' => true,
        ]);

        Location::create([
            'name' => 'Airport',
            'city_id' => 1,
            'description' => 'International Airport',
            'latitude' => 40.6413,
            'longitude' => -73.7781,
            'is_popular' => true,
        ]);

        Location::create([
            'name' => 'Beach Area',
            'city_id' => 2,
            'description' => 'Popular beach destination',
            'latitude' => 34.0522,
            'longitude' => -118.2437,
            'is_popular' => true,
        ]);
    }
}
