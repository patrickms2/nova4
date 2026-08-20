<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class Position extends Model
{
    protected $fillable = [
        'traccar_id',
        'device_id',
        'protocol',
        'server_time',
        'device_time',
        'fix_time',
        'outdated',
        'valid',
        'latitude',
        'longitude',
        'altitude',
        'speed',
        'course',
        'address',
        'accuracy',
        'network',
        'attributes',
    ];

    protected $casts = [
        'traccar_id' => 'integer',
        'device_id' => 'integer',
        'server_time' => 'datetime',
        'device_time' => 'datetime',
        'fix_time' => 'datetime',
        'outdated' => 'boolean',
        'valid' => 'boolean',
        'latitude' => 'float',
        'longitude' => 'float',
        'altitude' => 'float',
        'speed' => 'float',
        'course' => 'float',
        'accuracy' => 'float',
        'attributes' => 'array',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_id', 'traccar_id');
    }
}
