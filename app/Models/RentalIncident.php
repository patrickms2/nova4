<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RentalIncident extends Model
{
    /** @use HasFactory<\Database\Factories\RentalIncidentFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_property_id',
        'rental_reservation_id',
        'title',
        'description',
        'status',
        'priority',
        'assignee_name',
        'estimated_cost',
        'final_cost',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'final_cost' => 'decimal:2',
    ];

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(RentalReservation::class, 'rental_reservation_id');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(RentalDocument::class, 'documentable');
    }

    public function timelineEvents(): MorphMany
    {
        return $this->morphMany(RentalTimelineEvent::class, 'subject');
    }
}
