<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class Files extends Model {
    protected static $unguarded = true;

    public function attachable()
    {
        return $this->morphTo();
    }
}
