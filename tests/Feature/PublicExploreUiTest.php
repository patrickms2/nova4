<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicExploreUiTest extends TestCase
{
    public function test_filters_are_boolean_toggles_and_map_uses_only_mappable_results(): void
    {
        $source = file_get_contents(resource_path('views/public/explore.blade.php'));

        $this->assertStringContainsString('role="checkbox"', $source);
        $this->assertStringContainsString('aria-checked="true"', $source);
        $this->assertStringContainsString('state.activeTypes.delete(type)', $source);
        $this->assertStringNotContainsString('state.activeTypes.size > 1', $source);
        $this->assertStringContainsString('mappablePlaces(places)', $source);
        $this->assertStringContainsString('place.has_coordinates', $source);
        $this->assertStringContainsString('data-filter="tour_visit"', $source);
        $this->assertStringContainsString('data-filter="taxi_route"', $source);
        $this->assertStringContainsString('Visit Tour', $source);
        $this->assertStringContainsString('Taxi Route', $source);
        $this->assertStringContainsString("new Set(['hotel', 'restaurant', 'taxi', 'tour_visit', 'taxi_route'])", $source);
    }
}
