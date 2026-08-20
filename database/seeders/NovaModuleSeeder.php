<?php

namespace Database\Seeders;

use App\Models\NovaModule;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NovaModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Restaurant Booking Module',
                'slug' => 'restaurant-booking',
                'description' => 'Módulo para gestión de reservas de restaurantes con integración Sirvo',
                'version' => '1.0.0',
                'requires' => ['nova-core', 'mcp-client'],
                'status' => 'active',
                'metadata' => [
                    'author' => 'Nova Team',
                    'category' => 'reservations',
                    'features' => ['booking_management', 'availability_check', 'menu_integration']
                ],
                'installed_at' => now(),
                'activated_at' => now(),
            ],
            [
                'name' => 'Winery Tour Module',
                'slug' => 'winery-tour',
                'description' => 'Módulo para gestión de visitas a bodegas con integración La Geria',
                'version' => '1.2.0',
                'requires' => ['nova-core', 'mcp-client'],
                'status' => 'active',
                'metadata' => [
                    'author' => 'Nova Team',
                    'category' => 'tours',
                    'features' => ['tour_booking', 'wine_tasting', 'availability_management']
                ],
                'installed_at' => now(),
                'activated_at' => now(),
            ],
            [
                'name' => 'Taxi Service Module',
                'slug' => 'taxi-service',
                'description' => 'Módulo para gestión de servicios de taxi con integración Taxilanz',
                'version' => '1.0.0',
                'requires' => ['nova-core', 'mcp-client'],
                'status' => 'inactive',
                'metadata' => [
                    'author' => 'Nova Team',
                    'category' => 'transportation',
                    'features' => ['route_planning', 'fare_calculation', 'driver_management']
                ],
                'installed_at' => now(),
                'activated_at' => null,
            ],
            [
                'name' => 'Hotel Booking Module',
                'slug' => 'hotel-booking',
                'description' => 'Módulo para gestión de reservas hoteleras',
                'version' => '2.0.0',
                'requires' => ['nova-core', 'payment-gateway'],
                'status' => 'inactive',
                'metadata' => [
                    'author' => 'Nova Team',
                    'category' => 'hospitality',
                    'features' => ['room_management', 'booking_system', 'guest_services']
                ],
                'installed_at' => null,
                'activated_at' => null,
            ],
        ];

        foreach ($modules as $module) {
            NovaModule::updateOrCreate(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}
