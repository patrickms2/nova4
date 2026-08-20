<?php

namespace App\Models;

use Database\Factories\RentalGuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RentalGuest extends Model
{
    /** @use HasFactory<RentalGuestFactory> */
    use HasFactory;

    protected $fillable = [
        'first_name',
        'person_id',
        'last_name',
        'email',
        'phone',
        'country',
        'document_number',
        'birth_date',
        'notes',
        'avatar_url',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function reservations(): HasMany
    {
        return $this->hasMany(RentalReservation::class, 'guest_id');
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
