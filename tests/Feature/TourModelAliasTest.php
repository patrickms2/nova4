<?php

namespace Tests\Feature;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TourModelAliasTest extends TestCase
{
    use RefreshDatabase;

    public function test_name_assignment_is_persisted_to_tour_name_column(): void
    {
        $tour = Tour::query()->create($this->payload([
            'name' => 'Ruta Norte',
        ]));

        $this->assertSame('Ruta Norte', $tour->refresh()->tour_name);
        $this->assertSame('Ruta Norte', $tour->name);
    }

    public function test_updating_name_does_not_write_a_missing_name_column(): void
    {
        $tour = Tour::query()->create($this->payload([
            'tour_name' => 'Original',
        ]));

        $tour->update(['name' => 'Visita Guiada ES']);

        $this->assertDatabaseHas('tours', [
            'id' => $tour->id,
            'tour_name' => 'Visita Guiada ES',
        ]);
    }

    public function test_admin_assignment_is_persisted_to_created_by_column(): void
    {
        $manager = User::query()->create([
            'name' => 'Tour Manager',
            'email' => 'tour-manager@example.com',
            'password' => bcrypt('password'),
        ]);

        $tour = Tour::query()->create([
            'name' => 'Tour Manager Alias',
            'admin_id' => $manager->id,
            'location_id' => $this->location()->id,
            'base_price' => 25,
            'max_capacity' => 8,
        ]);

        $this->assertSame($manager->id, $tour->refresh()->created_by);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'location_id' => $this->location()->id,
            'base_price' => 25,
            'max_capacity' => 8,
            'created_by' => User::query()->create([
                'name' => 'External Sync',
                'email' => fake()->unique()->safeEmail(),
                'password' => bcrypt('password'),
            ])->id,
        ];
    }

    private function location(): Location
    {
        $country = Country::query()->create(['name' => 'Spain', 'code' => fake()->unique()->lexify('??')]);
        $city = City::query()->create(['country_id' => $country->id, 'name' => fake()->city()]);

        return Location::query()->create([
            'city_id' => $city->id,
            'name' => fake()->streetName(),
        ]);
    }
}
