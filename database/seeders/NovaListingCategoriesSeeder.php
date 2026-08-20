<?php

namespace Database\Seeders;

use App\Models\NovaListingCategory;
use Illuminate\Database\Seeder;

class NovaListingCategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Business IDs: 1=sirvo, 2=la-geria  (taxilanz has no NovaBusiness yet)
     * Server IDs:   2=sirvo-restaurants-mcp, 3=la-geria-mcp, 5=taxilanz-hoteles-laravel
     * Tool IDs:    12=sirvo-restaurantes, 37=lageria-latepoint-list-services, 41=hotel_list
     */
    public function run(): void
    {
        $categories = [
            [
                'nova_business_id' => 1,
                'server_id' => 2,
                'tool_id' => 12,
                'slug' => 'restaurant',
                'keywords' => ['restaurante', 'restaurantes', 'bodega', 'bodegas', 'comer', 'cenar', 'mesa', 'reservar'],
                'system_names' => ['sirvo'],
                'intro_text' => 'Estos son los negocios gestionados por Sirvo:',
                'cta_text' => '¿En cuál te gustaría reservar? Dime nombre, día, hora y número de personas.',
                'count_label' => 'restaurantes',
                'tool_params' => null,
                'item_fields' => ['name' => 'name', 'group' => 'group', 'price' => 'precio', 'address' => 'address'],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'nova_business_id' => 2,
                'server_id' => 3,
                'tool_id' => 37,
                'slug' => 'visit',
                'keywords' => ['visita', 'visitas', 'tour', 'tours', 'excursion', 'excursión', 'actividad', 'actividades', 'ruta', 'rutas'],
                'system_names' => ['lageria', 'geria', 'la geria'],
                'intro_text' => 'Visitas disponibles en La Geria',
                'cta_text' => '¿Cuál te interesa? Dime día y personas.',
                'count_label' => 'visitas',
                'tool_params' => ['input' => ['per_page' => 8]],
                'item_fields' => ['name' => 'title', 'duration' => 'duration', 'price' => 'price'],
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'nova_business_id' => 2,
                'server_id' => 3,
                'tool_id' => null,
                'slug' => 'product',
                'keywords' => ['vino', 'vinos', 'producto', 'productos', 'tienda', 'comprar', 'aloe', 'vinoterapia'],
                'system_names' => ['lageria', 'geria'],
                'intro_text' => 'Productos disponibles en La Geria:',
                'cta_text' => '¿Cuál te interesa? Te indico cómo adquirirlo.',
                'count_label' => 'productos',
                'tool_params' => null,
                'item_fields' => ['name' => 'name', 'price' => 'price'],
                'is_active' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($categories as $data) {
            NovaListingCategory::updateOrCreate(
                ['nova_business_id' => $data['nova_business_id'], 'slug' => $data['slug']],
                $data
            );
        }

        $this->command->info('NovaListingCategories seeded (sirvo + la-geria). Add Taxilanz as NovaBusiness to seed hotel category.');
    }
}
