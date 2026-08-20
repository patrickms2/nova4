<?php

namespace Tests\Feature;

use App\Models\NovaBusiness;
use App\Models\NovaCrossSellingRule;
use App\Services\Nova\NovaCrossSellingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovaCrossSellingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_prefers_filament_configured_cross_selling_rules(): void
    {
        $from = NovaBusiness::query()->create([
            'name' => 'Bodega La Geria',
            'slug' => 'la-geria',
            'business_type' => 'winery_tour',
            'status' => 'active',
        ]);

        $to = NovaBusiness::query()->create([
            'name' => 'Taxilanz',
            'slug' => 'taxilanz',
            'business_type' => 'taxi_hotel',
            'status' => 'active',
        ]);

        NovaCrossSellingRule::query()->create([
            'from_business_id' => $from->id,
            'to_business_id' => $to->id,
            'trigger_intent' => 'winery_visit',
            'message' => '¿Quieres añadir un taxi para llegar a la visita?',
            'cta_label' => 'Añadir taxi',
            'priority' => 0,
            'is_active' => true,
        ]);

        $suggestions = app(NovaCrossSellingService::class)
            ->suggestCrossSelling('la-geria', 'winery_visit');

        $this->assertCount(1, $suggestions);
        $this->assertEquals('filament', $suggestions[0]['source']);
        $this->assertEquals('taxilanz', $suggestions[0]['target']);
        $this->assertEquals('taxi_booking', $suggestions[0]['intent']);
        $this->assertEquals('Añadir taxi', $suggestions[0]['option_label']);
    }
}
