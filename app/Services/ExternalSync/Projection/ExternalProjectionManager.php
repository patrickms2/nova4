<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\ExternalSource;
use App\Models\ExternalSyncMapping;
use Illuminate\Database\Eloquent\Model;

class ExternalProjectionManager
{
    public function __construct(
        private readonly ProductProjector $productProjector,
        private readonly HotelProjector $hotelProjector,
        private readonly RestaurantProjector $restaurantProjector,
        private readonly TourProjector $tourProjector,
        private readonly TaxiServiceProjector $taxiServiceProjector,
        private readonly TourBookingProjector $tourBookingProjector,
    ) {}

    public function project(ExternalSource $source, Model $stagingRecord, array $payload): ExternalSyncMapping
    {
        $projectionPayload = new ExternalProjectionPayload($source, $stagingRecord, $payload);
        $projected = $this->projectorFor($projectionPayload)->project($projectionPayload);

        return ExternalSyncMapping::query()->updateOrCreate(
            [
                'external_source_id' => $source->id,
                'resource_type' => $projectionPayload->resourceType(),
                'external_id' => $projectionPayload->externalId(),
                'external_item_id' => $projectionPayload->externalItemId(),
            ],
            [
                'server_id' => $source->server_id,
                'business_name' => $source->business_name,
                'source_platform' => $source->source_platform,
                'source_label' => $source->source_label,
                'target_model' => $projectionPayload->targetModel(),
                'target_id' => $projected->getKey(),
                'payload_hash' => sha1(json_encode($projectionPayload->raw())),
                'last_synced_at' => now(),
            ],
        );
    }

    private function projectorFor(ExternalProjectionPayload $payload): Projector
    {
        return match ($payload->targetModel()) {
            'hotel' => $this->hotelProjector,
            'restaurant' => $this->restaurantProjector,
            'tour' => $this->tourProjector,
            'taxi_service' => $this->taxiServiceProjector,
            'tour_booking' => $this->tourBookingProjector,
            default => $this->productProjector,
        };
    }
}
