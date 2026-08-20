<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Rental;
use App\Services\RentalService;

/**
 * Wygasza nieoplacone rezerwacje (status=pending) starsze niz X minut.
 *
 * Port: blizne-art-cms ExpireReservations command.
 *
 * Uzycie:
 *   php artisan rental:expire                  # uses config('rental.expire_unpaid_after_minutes')
 *   php artisan rental:expire --minutes=60     # custom timeout
 *   php artisan rental:expire --dry-run        # liczba bez UPDATE
 *
 * Schedule (App\Console\Kernel):
 *   $schedule->command('rental:expire')->everyFiveMinutes();
 *
 * @see KML-0050 (C4)
 */
class ExpireRentals extends Command
{
    protected $signature = 'rental:expire
        {--minutes= : Liczba minut po ktorej pending rental wygasa (domyslnie config rental.expire_unpaid_after_minutes)}
        {--dry-run : Pokaz liczbe rezerwacji do wygaszenia, bez zapisu}';

    protected $description = 'Oznacza nieoplacone rezerwacje (pending) starsze niz X minut jako expired.';

    public function handle(RentalService $service): int
    {
        $minutes = $this->option('minutes');
        $minutes = $minutes !== null ? (int) $minutes : null;

        $effective = $minutes ?? (int) config('rental.expire_unpaid_after_minutes', 30);

        if ($this->option('dry-run')) {
            $count = Rental::query()
                ->where('status', 'pending')
                ->where('created_at', '<', now()->subMinutes($effective))
                ->count();

            $this->components->info("[dry-run] Wygaslo by: {$count} rezerwacji (cutoff={$effective} min).");

            return self::SUCCESS;
        }

        $expired = $service->expirePending($minutes);

        if ($expired === 0) {
            $this->components->info("Brak rezerwacji do wygaszenia (cutoff={$effective} min).");
        } else {
            $this->components->info("Wygaszono: {$expired} rezerwacji (cutoff={$effective} min).");
        }

        return self::SUCCESS;
    }
}
