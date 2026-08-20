<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Hotel;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class HotelProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External hotel');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return Hotel::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'location_id' => $location?->id,
                'star_rating' => $raw['star_rating'] ?? null,
                'average_rating' => $raw['average_rating'] ?? 0,
                'total_ratings' => $raw['total_ratings'] ?? 0,
                'main_image_url' => $raw['image'] ?? $raw['main_image_url'] ?? null,
                'website' => $payload->payload['website'] ?? $raw['website'] ?? null,
                'phone' => $payload->payload['phone'] ?? $raw['phone'] ?? null,
                'email' => $payload->payload['email'] ?? $raw['email'] ?? null,
                'is_active' => true,
                'is_featured' => (bool) ($raw['featured'] ?? false),
            ],
        );
    }
}
