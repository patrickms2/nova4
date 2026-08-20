<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\NovaStudioSeeder;
use Illuminate\Console\Command;

final class NovaStudioSeed extends Command
{
    protected $signature = 'nova:studio-seed {--fresh : Vacía solo las tablas NOVA Studio antes de sembrar}';

    protected $description = 'Seed complete NOVA Studio workspaces, capabilities, resources, relations, tools, connectors and bindings.';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('db:wipe', ['--drop-views'=>false, '--drop-types'=>false]);
            $this->warn('ATENCIÓN: --fresh usa db:wipe. Úsalo solo en copia DEV.');
            $this->call('migrate');
        }

        $this->call('db:seed', ['--class'=>NovaStudioSeeder::class]);

        $this->info('NOVA Studio seed complete.');

        return self::SUCCESS;
    }
}
