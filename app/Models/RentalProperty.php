<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalProperty extends Model
{
    /** @use HasFactory<\Database\Factories\RentalPropertyFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'booking_id',
        'code',
        'name',
        'nickname',
        'address',
        'tourist_registry',
        'cadastral_reference',
        'settings',
        'financial_settings',
        'is_active',
    ];

    protected $casts = [
        'settings' => 'array',
        'financial_settings' => 'array',
        'is_active' => 'boolean',
    ];

    public function financialRules(): array
    {
        return $this->financial_settings ?? [];
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(RentalReservation::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(RentalExpense::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(RentalContact::class);
    }

    public function inventoryItems(): HasMany
    {
        return $this->hasMany(RentalInventoryItem::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(RentalIncident::class);
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
}
