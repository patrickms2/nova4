<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Restaurant;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class RestaurantProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External restaurant');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return Restaurant::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'restaurant_name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'location_id' => $location?->id,
                'cuisine' => $raw['cuisine'] ?? null,
                'price_range' => $raw['price_range'] ?? null,
                'average_rating' => $raw['average_rating'] ?? 0,
                'total_ratings' => $raw['total_ratings'] ?? 0,
                'main_image_url' => $raw['image'] ?? $raw['main_image_url'] ?? null,
                'website' => $payload->payload['website'] ?? $raw['website'] ?? null,
                'phone' => $payload->payload['phone'] ?? $raw['phone'] ?? null,
                'email' => $payload->payload['email'] ?? $raw['email'] ?? null,
                'has_reservation' => true,
                'is_active' => true,
                'is_featured' => (bool) ($raw['featured'] ?? false),
            ],
        );
    }
}
