<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Database\Factories\RentalTypeFactory;

/**
 * Typ wynajmu / pakiet (port z blizne-art-cms/TicketType).
 *
 * Przyklady dla 2wheels:
 *  - "z kaskiem"
 *  - "tylko motocykl"
 *  - "z ubraniami ochronnymi"
 *
 * Roznice vs TicketType:
 *  - generyczne (typ wynajmu, nie biletu)
 *  - dodano meta (json) na elastyczne dane per-aplikacja
 */
class RentalType extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): RentalTypeFactory
    {
        return RentalTypeFactory::new();
    }

    protected $casts = [
        'price' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'available_from' => 'date',
        'available_until' => 'date',
        'meta' => 'array',
    ];

    public function getTable(): string
    {
        return $this->table ?? config('rental.tables.rental_types', 'rental_types');
    }

    public function rentals(): HasMany
    {
        return $this->hasMany(Rental::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailableOn(Builder $query, string $date): Builder
    {
        return $query->active()
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('available_from')->orWhere('available_from', '<=', $date);
            })
            ->where(function (Builder $q) use ($date) {
                $q->whereNull('available_until')->orWhere('available_until', '>=', $date);
            });
    }
}
