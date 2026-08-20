<?php

namespace App\Models;

use App\Enums\DomoticsEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomoticsEvent extends Model
{
    /** @use HasFactory<\Database\Factories\DomoticsEventFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_property_id',
        'device_id',
        'access_point_id',
        'access_grant_id',
        'user_id',
        'event_type',
        'payload',
        'created_at',
    ];

    protected $casts = [
        'event_type' => DomoticsEventType::class,
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public const UPDATED_AT = null;

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function accessPoint(): BelongsTo
    {
        return $this->belongsTo(AccessPoint::class);
    }

    public function accessGrant(): BelongsTo
    {
        return $this->belongsTo(AccessGrant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
