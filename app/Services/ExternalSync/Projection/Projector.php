<?php

namespace App\Services\ExternalSync\Projection;

use Illuminate\Database\Eloquent\Model;

interface Projector
{
    public function project(ExternalProjectionPayload $payload): Model;
}
