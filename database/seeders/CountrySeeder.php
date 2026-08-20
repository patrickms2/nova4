<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create countries
        Country::create([
            'name' => 'United States',
            'code' => 'US',
            'is_active' => true,
        ]);

        Country::create([
            'name' => 'Canada',
            'code' => 'CA',
            'is_active' => true,
        ]);

        Country::create([
            'name' => 'United Kingdom',
            'code' => 'GB',
            'is_active' => true,
        ]);

        Country::create([
            'name' => 'France',
            'code' => 'FR',
            'is_active' => true,
        ]);

        Country::create([
            'name' => 'Japan',
            'code' => 'JP',
            'is_active' => true,
        ]);
    }
}
