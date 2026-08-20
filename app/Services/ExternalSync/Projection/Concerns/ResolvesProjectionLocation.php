<?php

namespace App\Services\ExternalSync\Projection\Concerns;

use App\Models\City;
use App\Models\Country;
use App\Models\Location;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

trait ResolvesProjectionLocation
{
    protected function resolveLocation(array $raw, string $fallbackName): ?Location
    {
        $latitude = $raw['latitude'] ?? null;
        $longitude = $raw['longitude'] ?? null;

        $country = Country::query()->firstOrCreate(
            ['name' => (string) ($raw['country'] ?? 'Spain')],
            ['code' => 'ES', 'continent_code' => 'EU', 'phone_code' => '+34', 'is_active' => true],
        );

        $city = City::query()->firstOrCreate(
            ['country_id' => $country->id, 'name' => (string) ($raw['city'] ?? 'Lanzarote')],
            ['is_popular' => false],
        );

        return Location::query()->updateOrCreate(
            [
                'city_id' => $city->id,
                'name' => (string) ($raw['address'] ?? $fallbackName),
            ],
            [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'description' => $raw['description'] ?? null,
                'is_popular' => false,
            ],
        );
    }

    protected function systemUserId(): int
    {
        return User::query()->firstOrCreate(
            ['email' => 'external-sync@nova.local'],
            ['name' => 'External Sync', 'password' => Hash::make(str()->random(32))],
        )->id;
    }
}
