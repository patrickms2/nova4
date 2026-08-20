<?php

namespace App\Models;

use Database\Factories\RentalReservationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RentalReservation extends Model
{
    /** @use HasFactory<RentalReservationFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_property_id',
        'property_id',
        'guest_id',
        'person_id',
        'channel',
        'reference_code',
        'check_in',
        'check_out',
        'adults',
        'children',
        'amount',
        'channel_commission',
        'management_commission',
        'cleaning_fee',
        'payout',
        'status',
        'raw_payload',
        'parsed_at',
    ];

    protected $casts = [
        'check_in' => 'date',
        'check_out' => 'date',
        'amount' => 'decimal:2',
        'channel_commission' => 'decimal:2',
        'management_commission' => 'decimal:2',
        'cleaning_fee' => 'decimal:2',
        'payout' => 'decimal:2',
        'raw_payload' => 'array',
        'parsed_at' => 'datetime',
    ];

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function guest(): BelongsTo
    {
        return $this->belongsTo(RentalGuest::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function accessGrants(): MorphMany
    {
        return $this->morphMany(AccessGrant::class, 'source');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentalPayment::class);
    }

    public function incidents(): HasMany
    {
        return $this->hasMany(RentalIncident::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(RentalDocument::class, 'documentable');
    }

    public function timelineEvents(): MorphMany
    {
        return $this->morphMany(RentalTimelineEvent::class, 'subject');
    }

    public function components(): HasMany
    {
        return $this->hasMany(RentalComponent::class);
    }

    public function settlement(): HasOne
    {
        return $this->hasOne(RentalSettlement::class);
    }

    public function nights(): int
    {
        return $this->check_in->diffInDays($this->check_out);
    }

    public function netOwner(): float
    {
        return (float) $this->payout - (float) $this->cleaning_fee;
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('check_in', '>=', today());
    }
}
