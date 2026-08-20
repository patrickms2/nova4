<?php

namespace App\Models;

use Database\Factories\PropertyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Property extends Model
{
    /** @use HasFactory<PropertyFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'community_id',
        'unit_reference',
        'name',
        'address',
        'timezone',
        'owner_id',
        'settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function community(): BelongsTo
    {
        return $this->belongsTo(Community::class);
    }

    public function communityDocuments(): HasMany
    {
        return $this->hasMany(CommunityOwnerDocument::class);
    }

    public function communityAppointments(): HasMany
    {
        return $this->hasMany(CommunityAppointment::class);
    }

    public function communityTickets(): HasMany
    {
        return $this->hasMany(CommunityTicket::class);
    }

    public function communityFees(): HasMany
    {
        return $this->hasMany(CommunityFee::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'property_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function accessPoints(): HasMany
    {
        return $this->hasMany(AccessPoint::class);
    }

    public function accessGrants(): HasMany
    {
        return $this->hasMany(AccessGrant::class);
    }

    public function automations(): HasMany
    {
        return $this->hasMany(Automation::class);
    }

    public function domoticsEvents(): HasMany
    {
        return $this->hasMany(DomoticsEvent::class);
    }


    public function owners(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot(['role', 'metadata'])->withTimestamps();
    }
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class)->withPivot(['role', 'metadata'])->withTimestamps();
    }

    public function rentalProfile(): HasOne
    {
        return $this->hasOne(RentalProperty::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RentalReservation::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
