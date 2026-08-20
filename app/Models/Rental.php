<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use App\Concerns\HasDateRange;
use App\Database\Factories\RentalFactory;

/**
 * Model rezerwacji (port z blizne-art-cms/Reservation z adaptacja).
 *
 * Roznice vs blizne-art-cms:
 *  - id: UUID (zachowane)
 *  - time_slot_id -> rentable polimorficznie + start_date/end_date
 *    (zakres dat zamiast pojedynczego slotu — KML-0046)
 *  - ticket_type_id -> rental_type_id (RentalType)
 *  - guests -> qty (generyczne, np. liczba sztuk sprzetu)
 *  - dodano: currency, locale, meta (json)
 *
 * Statusy: pending, confirmed, paid, cancelled, expired
 */
class Rental extends Model
{
    use HasDateRange;
    use HasFactory;
    use HasUuids;

    protected $guarded = [];

    protected static function newFactory(): RentalFactory
    {
        return RentalFactory::new();
    }

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'qty' => 'integer',
        'total_amount' => 'integer',
        'paid_amount' => 'integer',
        'gdpr_consent' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        // KML-0047: synchronizacja start_date/end_date z start_at/end_at
        // dla wstecznej kompatybilnosci (raporty filamentowe filtrujace po dacie).
        static::saving(function (Rental $rental): void {
            if ($rental->start_at instanceof Carbon) {
                $rental->start_date = $rental->start_at->toDateString();
            }
            if ($rental->end_at instanceof Carbon) {
                $rental->end_date = $rental->end_at->toDateString();
            }

            // KML-0061: symetryczna synchronizacja start_date -> start_at gdy
            // rezerwacja zostala utworzona bez datetime (np. przez stary flow
            // frontendowy zapisujacy tylko start_date/end_date). Bez tego
            // edycja w Filament pokazuje puste pole "Odbior (data + godzina)".
            $defaultHour = (int) config('rental.default_pickup_hour', 10);
            if ($rental->start_at === null && $rental->start_date !== null) {
                $startDate = $rental->start_date instanceof Carbon
                    ? $rental->start_date
                    : Carbon::parse((string) $rental->start_date);
                $rental->start_at = $startDate->copy()->setTime($defaultHour, 0, 0);
            }
            if ($rental->end_at === null && $rental->end_date !== null) {
                $endDate = $rental->end_date instanceof Carbon
                    ? $rental->end_date
                    : Carbon::parse((string) $rental->end_date);
                $rental->end_at = $endDate->copy()->setTime($defaultHour, 0, 0);
            }
        });
    }

    /**
     * KML-0052: liczba dob rozliczeniowych — floor(diffH/24), minimum 1.
     *
     * Zalozenie biznesowe: pelna doba = 24h. Niepelna doba (np. 25h = 1 doba + 1h)
     * NIE liczy sie jako kolejna doba — tylko pelne doby sa rozliczane.
     * KML-0047: zmiana ceil -> floor (zaokraglanie w gore powodowalo zawyzone ceny).
     *
     * Przyklady:
     *  - 24h dokladnie  -> 1 doba
     *  - 24h + 1 minuta -> 1 doba (floor, nie ceil)
     *  - 48h            -> 2 doby
     *  - 23h            -> 1 doba (minimum)
     *  - 7 dni + 1h     -> 7 dob
     *
     * Fallback: jesli start_at/end_at brak (legacy) - liczy z start_date/end_date.
     */
    public function computeBillingDays(): int
    {
        $start = $this->start_at ?: ($this->start_date ? Carbon::parse($this->start_date->format('Y-m-d').' 10:00:00') : null);
        $end = $this->end_at ?: ($this->end_date ? Carbon::parse($this->end_date->format('Y-m-d').' 10:00:00') : null);

        if (! $start || ! $end) {
            return 1;
        }

        $diffHours = max(0.0, (float) $start->diffInHours($end, true));
        if ($diffHours <= 0.0) {
            return 1;
        }

        return max(1, (int) floor($diffHours / 24.0));
    }

    public function getTable(): string
    {
        return $this->table ?? config('rental.tables.rentals', 'rentals');
    }

    public function rentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function rentalType(): BelongsTo
    {
        return $this->belongsTo(RentalType::class);
    }

    public function isActive(): bool
    {
        return ! in_array($this->status, ['cancelled', 'expired'], true);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', ['cancelled', 'expired']);
    }

    /**
     * Overlap scope - KML-0047 obsluguje datetime gdy podany format Y-m-d H:i:s,
     * fallback na date gdy podane Y-m-d (Carbon::parse rozumie oba).
     *
     * Uzywa start_at/end_at jesli kolumny istnieja i parametry zawieraja czas.
     */
    public function scopeOverlapping(Builder $query, string $startDate, string $endDate): Builder
    {
        $hasTime = preg_match('/\d{2}:\d{2}/', $startDate.$endDate) === 1;

        if ($hasTime && Schema::hasColumn($this->getTable(), 'start_at')) {
            return $query->where('start_at', '<', $endDate)
                ->where('end_at', '>', $startDate);
        }

        return $query->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }
}
