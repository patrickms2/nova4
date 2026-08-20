<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Concerns\HasDateRange;
use App\Database\Factories\AvailabilitySlotFactory;

/**
 * Slot blokady dostepnosci dla zasobu Rentable.
 *
 * Adaptacja z blizne-art-cms/TimeSlot. Roznice:
 *  - jeden TimeSlot per dzien -> AvailabilitySlot z zakresem (start_date/end_date)
 *  - max_guests + booked_guests -> is_blocked + reason
 *    (slot zawsze oznacza "calkowita blokade" — wynajem motocykla
 *     to model "ekskluzywny", nie wieloosobowy jak wycieczka)
 *  - polimorficznie wiazany z Rentable (rentable_type, rentable_id)
 *
 * Use case:
 *  - Admin oznacza "motocykl Ducati w serwisie 5-10 maja" -> blokada
 *  - System rezerwacji sprawdza overlap z slotami i Rentalami
 */
class AvailabilitySlot extends Model
{
    use HasDateRange;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): AvailabilitySlotFactory
    {
        return AvailabilitySlotFactory::new();
    }

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_blocked' => 'boolean',
        'available_pers' => 'integer',
        'price' => 'decimal:2',
    ];

    public function getTable(): string
    {
        return $this->table ?? config('rental.tables.availability_slots', 'availability_slots');
    }

    public function rentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('is_blocked', true);
    }

    public function scopeOverlapping(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }
}
