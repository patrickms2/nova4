<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Contracts\PricingStrategyInterface;
use App\Exceptions\RentalConflictException;
use App\Mail\RentalConfirmed;
use App\Models\AvailabilitySlot;
use App\Models\Rental;
use App\Models\RentalType;
use App\Services\Pricing\GlobalPricingStrategy;

/**
 * RentalService — anti-double-booking + transakcyjne operacje na Rental.
 *
 * Wzorzec portu: blizne-art-cms ReservationController::store() (DB::transaction + lockForUpdate).
 *
 * Adaptacja:
 *  - lock na rentable row (Motorcycle/Sala/etc.) zamiast pojedynczego TimeSlot
 *  - walidacja overlap aktywnych Rentals + AvailabilitySlot blocked
 *  - model ekskluzywny (brak max_guests semantyki)
 *
 * @see KML-0047
 */
class RentalService
{
    /**
     * Strategia obliczania ceny — wstrzyknieta z container przez ServiceProvider.
     *
     * Backward compat: domyslnie GlobalPricingStrategy gdy DI nie zarejestrowany
     * (np. przy bezposrednim 'new RentalService()' poza Laravelem).
     */
    private PricingStrategyInterface $pricingStrategy;

    public function __construct(?PricingStrategyInterface $pricingStrategy = null)
    {
        $this->pricingStrategy = $pricingStrategy ?? new GlobalPricingStrategy;
    }

    /**
     * Atomowe utworzenie rezerwacji z zabezpieczeniem przed double-booking.
     *
     * Przebieg w transakcji:
     *  1. lockForUpdate na rentable row (synchronizacja procesow)
     *  2. lockForUpdate na overlapping rentals (gap lock InnoDB)
     *  3. sprawdzenie AvailabilitySlot blocked overlap
     *  4. Rental::create()
     *
     * @param  Model  $rentable  zasob (Motorcycle, Sala, Sprzet, ...)
     * @param  array<string,mixed>  $payload  dane klienta + amount + qty + ...
     *
     * @throws RentalConflictException gdy zasob niedostepny
     */
    public function book(
        Model $rentable,
        string $startDate,
        string $endDate,
        array $payload,
        ?int $rentalTypeId = null
    ): Rental {
        return DB::transaction(function () use ($rentable, $startDate, $endDate, $payload, $rentalTypeId) {
            // 1. Lock rentable row — synchronizuje rownolegle procesy bookingu
            DB::table($rentable->getTable())
                ->where($rentable->getKeyName(), $rentable->getKey())
                ->lockForUpdate()
                ->first();

            $morphClass = $rentable->getMorphClass();

            // 2. Lock overlapping aktywnych rentali (gap lock)
            $hasOverlap = Rental::query()
                ->where('rentable_type', $morphClass)
                ->where('rentable_id', $rentable->getKey())
                ->active()
                ->overlapping($startDate, $endDate)
                ->lockForUpdate()
                ->exists();

            if ($hasOverlap) {
                throw RentalConflictException::rentalOverlap($morphClass, $rentable->getKey(), $startDate, $endDate);
            }

            // 3. Sprawdzenie blokad dostepnosci
            $isBlocked = AvailabilitySlot::query()
                ->where('rentable_type', $morphClass)
                ->where('rentable_id', $rentable->getKey())
                ->blocked()
                ->overlapping($startDate, $endDate)
                ->exists();

            if ($isBlocked) {
                throw RentalConflictException::blocked($morphClass, $rentable->getKey(), $startDate, $endDate);
            }

            // 4. Wyznaczenie kwoty + statusu
            // Pricing model: payload['total_amount'] (klient explicit) > strategia.
            // Strategia wybierana w ServiceProvider na podstawie config('rental.pricing_strategy').
            $rentalType = $rentalTypeId !== null ? RentalType::find($rentalTypeId) : null;
            $qty = (int) ($payload['qty'] ?? 1);
            $totalAmount = $payload['total_amount']
                ?? $this->pricingStrategy->calculate($rentable, $rentalType, $qty, $startDate, $endDate, $payload);

            $status = ((int) $totalAmount) === 0 ? 'confirmed' : 'pending';

            // 5. Utworzenie
            return Rental::create(array_merge([
                'rentable_type' => $morphClass,
                'rentable_id' => $rentable->getKey(),
                'rental_type_id' => $rentalTypeId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'qty' => $qty,
                'total_amount' => (int) $totalAmount,
                'currency' => $payload['currency'] ?? config('rental.currency', 'PLN'),
                'locale' => $payload['locale'] ?? 'pl',
                'status' => $status,
                'gdpr_consent' => (bool) ($payload['gdpr_consent'] ?? false),
            ], array_intersect_key($payload, array_flip([
                'name', 'email', 'phone', 'message', 'meta',
            ]))));
        });
    }

    /**
     * Anuluje rezerwacje (status -> cancelled).
     */
    public function cancel(Rental $rental): Rental
    {
        $rental->update(['status' => 'cancelled']);

        return $rental->fresh();
    }

    /**
     * Oznacza rezerwacje jako oplacona (status -> paid).
     *
     * @param  string|null  $orderId  identyfikator zamowienia u dostawcy platnosci
     */
    public function markPaid(Rental $rental, ?string $orderId = null): Rental
    {
        $update = ['status' => 'paid'];

        if ($orderId !== null) {
            $update['payment_order_id'] = $orderId;
        }

        // KML-0047 ext: zapis kwoty faktycznie wplaconej online.
        // paid_amount == total_amount w momencie wplaty (P24 nie obsluguje
        // czesciowych platnosci). Pozwala pozniej rozliczyc roznice w biurze
        // gdy admin zmieni motocykl i total_amount sie zmieni.
        $update['paid_amount'] = (int) $rental->total_amount;

        $rental->update($update);
        $rental = $rental->fresh();

        $this->sendConfirmationEmail($rental);

        return $rental;
    }

    /**
     * Oznacza rezerwacje jako potwierdzona (status -> confirmed).
     */
    public function confirm(Rental $rental): Rental
    {
        $rental->update(['status' => 'confirmed']);
        $rental = $rental->fresh();

        $this->sendConfirmationEmail($rental);

        return $rental;
    }

    /**
     * Wysyla email RentalConfirmed gdy:
     *  - config('rental.send_confirmation_email') === true
     *  - rezerwacja ma email klienta
     *
     * Failure wysylki nie powinna wywrocic operacji biznesowej (markPaid/confirm
     * juz sie powiodlo) — logujemy blad i kontynuujemy.
     */
    private function sendConfirmationEmail(Rental $rental): void
    {
        if (! config('rental.send_confirmation_email', true)) {
            return;
        }

        if (empty($rental->email)) {
            return;
        }

        try {
            Mail::to($rental->email)->send(new RentalConfirmed($rental));
        } catch (\Throwable $e) {
            Log::warning('RentalConfirmed mail failed', [
                'rental_id' => $rental->id,
                'email' => $rental->email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Wygasa nieoplacone rezerwacje (status pending) starsze niz X minut.
     *
     * Uzywane przez komende rental:expire (KML-0050 / C4).
     *
     * @param  int|null  $minutes  null => uzyj config('rental.expire_unpaid_after_minutes', 30)
     * @return int liczba wygaszonych rezerwacji
     */
    public function expirePending(?int $minutes = null): int
    {
        $minutes = $minutes ?? (int) config('rental.expire_unpaid_after_minutes', 30);
        $cutoff = Carbon::now()->subMinutes($minutes);

        return Rental::query()
            ->where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->update(['status' => 'expired']);
    }
}
