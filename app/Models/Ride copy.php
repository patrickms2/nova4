<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ride extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'pickup_label',
        'pickup_lat',
        'pickup_lng',
        'destination_label',
        'destination_lat',
        'destination_lng',
        'scheduled_for',
        'status',
        'eta_minutes',
        'source_channel',
        'context_zone',
        'interest_type',
        'locale',
    ];

    protected $appends = [
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:7',
        'pickup_lng' => 'decimal:7',
        'destination_lat' => 'decimal:7',
        'destination_lng' => 'decimal:7',
        'scheduled_for' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Ride $ride) {
            if (blank($ride->uuid)) {
                $ride->uuid = (string) Str::uuid();
            }
        });
    }

    /*function setLocationAttribute(mixed $location): void
    {
        if (is_string($location)) {
            $decoded = json_decode($location, true);
            $location = is_array($decoded) ? $decoded : null;
        }

        if (is_array($location) && isset($location['destination_lat'], $location['destination_lng'])) {
            $this->attributes['destination_lat'] = $location['destination_lat'];
            $this->attributes['destination_lng'] = $location['destination_lng'];
            unset($this->attributes['destination_location']);
        }
    }

    public static function getLatLngAttributes(): array
    {
        return [
            'destination_lat' => 'destination_lat',
            'destination_lng' => 'destination_lng',
        ];
    }

    public static function getComputedLocation(): string
    {
        return 'destination_location';
    }*/

    public function recommendations(): HasMany
    {
        return $this->hasMany(RideRecommendation::class)->orderBy('position');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRide::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
