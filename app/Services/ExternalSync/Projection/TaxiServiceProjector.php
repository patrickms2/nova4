<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\TaxiService;
use App\Services\ExternalSync\Projection\Concerns\ResolvesProjectionLocation;
use Illuminate\Database\Eloquent\Model;

class TaxiServiceProjector implements Projector
{
    use ResolvesProjectionLocation;

    public function project(ExternalProjectionPayload $payload): Model
    {
        $raw = $payload->raw();
        $name = (string) ($payload->payload['name'] ?? $raw['name'] ?? 'External taxi service');
        $location = $this->resolveLocation($raw, $name);
        $existingId = $payload->source->syncMappings()
            ->where('resource_type', $payload->resourceType())
            ->where('external_id', $payload->externalId())
            ->value('target_id');

        return TaxiService::query()->updateOrCreate(
            ['id' => $existingId],
            [
                'name' => $name,
                'description' => $payload->payload['description'] ?? $raw['description'] ?? null,
                'location_id' => $location?->id,
                'logo_url' => $raw['image'] ?? $raw['logo_url'] ?? null,
                'website' => $payload->payload['website'] ?? $raw['website'] ?? null,
                'phone' => $payload->payload['phone'] ?? $raw['phone'] ?? null,
                'email' => $payload->payload['email'] ?? $raw['email'] ?? null,
                'is_active' => true,
            ],
        );
    }
}
