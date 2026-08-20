<?php

namespace Database\Seeders;

use App\Models\NovaBusiness;
use App\Models\NovaIntentToServerMapping;
use App\Models\Server;
use App\Models\Tool;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NovaIntentToServerMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get servers by slug
        $sirvo = Server::where('slug', 'sirvo-restaurants-mcp')->first();
        $lageria = Server::where('slug', 'la-geria-mcp')->first();
        $taxilanz = Server::where('name', 'like', '%taxilanz%')->first();
        $lanzaloe = Server::where('name', 'like', '%lanzaloe%')->first();

        // Get businesses
        $laGeria = NovaBusiness::where('slug', 'la-geria')->first();
        $sirvoBusiness = NovaBusiness::where('slug', 'sirvo')->first();
        $taxilanzBusiness = NovaBusiness::where('slug', 'taxilanz')->first();
        $lanzaloeBusiness = NovaBusiness::where('slug', 'lanzaloe')->first();

        // Global mappings (apply to all businesses)
        $globalMappings = [
            [
                'intent_key' => 'restaurant_booking',
                'server_id' => $sirvo?->id,
                'tool_id' => null,
                'priority' => 10,
                'conditions' => null,
                'nova_business_id' => null,
            ],
            [
                'intent_key' => 'winery_visit',
                'server_id' => $lageria?->id,
                'tool_id' => null,
                'priority' => 10,
                'conditions' => null,
                'nova_business_id' => null,
            ],
            [
                'intent_key' => 'taxi_booking',
                'server_id' => $taxilanz?->id,
                'tool_id' => null,
                'priority' => 10,
                'conditions' => null,
                'nova_business_id' => null,
            ],
            [
                'intent_key' => 'product_purchase',
                'server_id' => $lanzaloe?->id,
                'tool_id' => null,
                'priority' => 10,
                'conditions' => null,
                'nova_business_id' => null,
            ],
        ];

        // Business-specific mappings (overrides)
        $businessMappings = [];

        foreach ($globalMappings as $mapping) {
            if ($mapping['server_id']) {
                NovaIntentToServerMapping::firstOrCreate(
                    [
                        'intent_key' => $mapping['intent_key'],
                        'server_id' => $mapping['server_id'],
                        'nova_business_id' => $mapping['nova_business_id'],
                    ],
                    [
                        'tool_id' => $mapping['tool_id'],
                        'priority' => $mapping['priority'],
                        'conditions' => $mapping['conditions'],
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('Nova Intent to Server Mapping seeded successfully.');
    }
}
