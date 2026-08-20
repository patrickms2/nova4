<?php

namespace App\Models;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    /** @use HasFactory<\Database\Factories\DeviceFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_property_id',
        'name',
        'type',
        'identifier',
        'status',
        'meta',
        'last_seen_at',
    ];

    protected $casts = [
        'type' => DeviceType::class,
        'status' => DeviceStatus::class,
        'meta' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function accessPoints(): HasMany
    {
        return $this->hasMany(AccessPoint::class);
    }

    public function domoticsEvents(): HasMany
    {
        return $this->hasMany(DomoticsEvent::class);
    }
}
