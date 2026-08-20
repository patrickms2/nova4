<?php

declare(strict_types=1);

namespace App\Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Stub seedera demo. Implementacja zalezna od B-grupy (modele KML-0044).
 *
 * Konwencja: firstOrCreate / updateOrCreate — idempotentnie.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Implementacja w B-grupie. Skeleton tylko po to, by --seed
        // dzialal bez wyjatku class_not_found, kiedy ktos zechce go odpalic.
    }
}
