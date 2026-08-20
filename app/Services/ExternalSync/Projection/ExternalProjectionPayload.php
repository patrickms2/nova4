<?php

namespace App\Services\ExternalSync\Projection;

use App\Models\ExternalSource;
use Illuminate\Database\Eloquent\Model;

class ExternalProjectionPayload
{
    public function __construct(
        public readonly ExternalSource $source,
        public readonly Model $stagingRecord,
        public readonly array $payload,
    ) {}

    public function externalId(): string
    {
        return (string) ($this->payload['external_id'] ?? $this->stagingRecord->external_id);
    }

    public function externalItemId(): ?string
    {
        $value = $this->payload['external_item_id'] ?? $this->stagingRecord->external_item_id ?? null;

        return blank($value) ? null : (string) $value;
    }

    public function resourceType(): string
    {
        return (string) ($this->payload['resource_type'] ?? $this->source->resource_type);
    }

    public function targetModel(): string
    {
        return (string) ($this->payload['target_model'] ?? $this->source->target_model);
    }

    public function raw(): array
    {
        return $this->payload['metadata']['raw'] ?? $this->stagingRecord->metadata['raw'] ?? $this->payload;
    }
}
