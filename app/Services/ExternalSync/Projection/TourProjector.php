<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\Tour;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class TourProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External tour');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return Tour::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'tour_name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'short_description' => $payload->payload['short_description'] ?? $raw['short_description'] ?? $payload->resourceType(),
                'location_id' => $location?->id,
                'duration_hours' => $raw['duration_hours'] ?? null,
                'duration_days' => $raw['duration_days'] ?? null,
                'base_price' => $payload->payload['price'] ?? $raw['price'] ?? 0,
                'discount_percentage' => $raw['discount_percentage'] ?? 0,
                // LatePoint provides capacity_min/capacity_max; other sources may provide max_capacity.
                'max_capacity' => $raw['max_capacity'] ?? $raw['capacity_max'] ?? 1,
                'min_participants' => $raw['min_participants'] ?? $raw['capacity_min'] ?? 1,
                'difficulty_level' => $raw['difficulty_level'] ?? 'Easy',
                'average_rating' => $raw['average_rating'] ?? 0,
                'total_ratings' => $raw['total_ratings'] ?? 0,
                'main_image_url' => $raw['image'] ?? $raw['main_image_url'] ?? null,
                'is_active' => true,
                'is_featured' => (bool) ($raw['featured'] ?? false),
                'created_by' => $raw['created_by'] ?? $this->systemUserId(),
            ],
        );
    }
}
