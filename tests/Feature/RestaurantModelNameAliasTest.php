<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantModelNameAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_assignment_is_persisted_to_restaurant_name_column(): void
    {
        $location = $this->location();

        $restaurant = Restaurant::query()->create([
            'name' => 'Visita Guiada ES',
            'location_id' => $location->id,
            'is_active' => true,
        ]);

        $this->assertSame('Visita Guiada ES', $restaurant->refresh()->restaurant_name);
        $this->assertSame('Visita Guiada ES', $restaurant->name);
    }

    public function test_updating_name_does_not_write_a_missing_name_column(): void
    {
        $restaurant = Restaurant::query()->create([
            'restaurant_name' => 'Original',
            'location_id' => $this->location()->id,
            'is_active' => true,
        ]);

        $restaurant->update(['name' => 'Visita Guiada ES']);

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'restaurant_name' => 'Visita Guiada ES',
        ]);
    }

    private function location(): Location
    {
        $country = Country::query()->create(['name' => 'Spain', 'code' => 'ES']);
        $city = City::query()->create(['country_id' => $country->id, 'name' => 'Arrecife']);

        return Location::query()->create([
            'city_id' => $city->id,
            'name' => 'Arrecife',
        ]);
    }
}
