<?php

namespace Database\Seeders;

use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create vehicles for City Cabs (TaxiServiceID: 1)
        // Economy vehicles (VehicleTypeID: 1)
        Vehicle::create([
            'taxi_service_id' => 1,
            'vehicle_type_id' => 1,
            'registration_number' => 'ABC-1234',
            'model' => 'Toyota Corolla',
            'year' => 2020,
            'color' => 'White',
            'is_active' => true,
        ]);

        Vehicle::create([
            'taxi_service_id' => 1,
            'vehicle_type_id' => 1,
            'registration_number' => 'DEF-5678',
            'model' => 'Honda Civic',
            'year' => 2021,
            'color' => 'Silver',
            'is_active' => true,
        ]);

        // SUV vehicles (VehicleTypeID: 2)
        Vehicle::create([
            'taxi_service_id' => 1,
            'vehicle_type_id' => 2,
            'registration_number' => 'GHI-9012',
            'model' => 'Toyota RAV4',
            'year' => 2019,
            'color' => 'Black',
            'is_active' => true,
        ]);

        // Create vehicles for Express Taxi (TaxiServiceID: 2)
        // Standard vehicles (VehicleTypeID: 3)
        Vehicle::create([
            'taxi_service_id' => 2,
            'vehicle_type_id' => 3,
            'registration_number' => 'JKL-3456',
            'model' => 'Nissan Altima',
            'year' => 2020,
            'color' => 'Blue',
            'is_active' => true,
        ]);

        // Minivan vehicles (VehicleTypeID: 4)
        Vehicle::create([
            'taxi_service_id' => 2,
            'vehicle_type_id' => 4,
            'registration_number' => 'MNO-7890',
            'model' => 'Honda Odyssey',
            'year' => 2021,
            'color' => 'Gray',
            'is_active' => true,
        ]);

        // Create vehicles for Luxury Rides (TaxiServiceID: 3)
        // Premium vehicles (VehicleTypeID: 5)
        Vehicle::create([
            'taxi_service_id' => 3,
            'vehicle_type_id' => 5,
            'registration_number' => 'PQR-1234',
            'model' => 'Mercedes-Benz E-Class',
            'year' => 2022,
            'color' => 'Black',
            'is_active' => true,
        ]);

        // Executive vehicles (VehicleTypeID: 6)
        Vehicle::create([
            'taxi_service_id' => 3,
            'vehicle_type_id' => 6,
            'registration_number' => 'STU-5678',
            'model' => 'BMW X5',
            'year' => 2022,
            'color' => 'White',
            'is_active' => true,
        ]);
    }
}
