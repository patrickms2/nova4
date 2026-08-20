<?php

namespace App\Services\ExternalSync\Projection;

use Illuminate\Database\Eloquent\Model;

class ProductProjector implements Projector
{
    public function project(ExternalProjectionPayload $payload): Model
    {
        return $payload->stagingRecord;
    }
}
