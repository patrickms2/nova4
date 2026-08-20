<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Idempotentna komenda instalacyjna pakietu reservation-system.
 *
 * Krok po kroku:
 *  1. publikuje config/rental.php (tag: rental-config)
 *  2. publikuje migracje (tag: rental-migrations)
 *  3. uruchamia php artisan migrate --force
 *  4. opcjonalnie uruchamia DemoSeeder (--seed)
 *
 * Idempotentnosc:
 *  - vendor:publish bez --force nie nadpisuje istniejacych plikow
 *  - migrate ignoruje juz wykonane migracje
 *  - DemoSeeder powinien byc napisany w stylu firstOrCreate
 */
class InstallCommand extends Command
{
    protected $signature = 'rental:install
        {--force : Nadpisz istniejace pliki konfiguracji i migracji}
        {--seed : Uruchom DemoSeeder (dane demonstracyjne)}
        {--skip-migrate : Pomin krok migrate (np. dla srodowiska CI)}';

    protected $description = 'Instaluje pakiet octadecimalhq/reservation-system: publikuje config + migracje, uruchamia migrate, opcjonalny seeder demo.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $this->info('==> Octadecimal Rental — instalacja pakietu');

        // 1. Publikacja configa
        $this->components->task('Publikacja config/rental.php', function () use ($force) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'rental-config',
                '--force' => $force,
            ]);

            return true;
        });

        // 2. Publikacja migracji
        $this->components->task('Publikacja migracji pakietu', function () use ($force) {
            $this->callSilently('vendor:publish', [
                '--tag' => 'rental-migrations',
                '--force' => $force,
            ]);

            return true;
        });

        // 3. Migracje
        if ($this->option('skip-migrate')) {
            $this->components->warn('Pomijam krok migrate (--skip-migrate).');
        } else {
            $this->components->task('Uruchomienie migrate', function () {
                $this->call('migrate', ['--force' => true]);

                return true;
            });
        }

        // 4. Opcjonalny seeder demo
        if ($this->option('seed')) {
            $seederClass = 'Octadecimal\\Rental\\Database\\Seeders\\DemoSeeder';

            if (! class_exists($seederClass)) {
                $this->components->warn("Seeder {$seederClass} nie jest jeszcze dostepny — pomijam.");
            } else {
                $this->components->task('Uruchomienie DemoSeeder', function () use ($seederClass) {
                    $this->call('db:seed', [
                        '--class' => $seederClass,
                        '--force' => true,
                    ]);

                    return true;
                });
            }
        }

        $this->newLine();
        $this->components->info('Pakiet octadecimalhq/reservation-system zainstalowany.');
        $this->components->bulletList([
            'Konfiguracja: config/rental.php',
            'Migracje: opublikowane + zaaplikowane',
            'Filament: zarejestruj plugin w PanelProviderze: ->plugin(\\Octadecimal\\Rental\\RentalPlugin::make())',
        ]);

        return self::SUCCESS;
    }
}
