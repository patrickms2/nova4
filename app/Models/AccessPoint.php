<?php

namespace App\Models;

use App\Enums\AccessPointType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccessPoint extends Model
{
    /** @use HasFactory<\Database\Factories\AccessPointFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_property_id',
        'device_id',
        'name',
        'type',
        'location',
        'is_active',
    ];

    protected $casts = [
        'type' => AccessPointType::class,
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function accessGrants(): BelongsToMany
    {
        return $this->belongsToMany(AccessGrant::class, 'access_grant_access_point')
            ->withTimestamps();
    }

    public function domoticsEvents(): HasMany
    {
        return $this->hasMany(DomoticsEvent::class);
    }
}
