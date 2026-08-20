<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaRepresentation;
use App\Support\Nova\FilamentPresentationBuilder;
use Illuminate\Console\Command;

final class NovaBuildFilamentPresentations extends Command
{
    protected $signature = 'nova:build-filament-presentations {--force : Rebuild/update all detected Filament representation trees}';

    protected $description = 'Build Filament presentation trees for detected NOVA representations.';

    public function handle(FilamentPresentationBuilder $builder): int
    {
        $representations = NovaRepresentation::query()
            ->where('type', NovaRepresentationType::Filament)
            ->with(['resource', 'capability'])
            ->orderBy('name')
            ->get();

        if ($representations->isEmpty()) {
            $this->warn('No hay representaciones Filament. Ejecuta primero nova:discover-filament.');

            return self::SUCCESS;
        }

        foreach ($representations as $representation) {
            $root = $builder->build($representation);

            $this->line(sprintf(
                '✓ %s → %s (%d nodos)',
                $representation->name,
                $root->label,
                $root->children()->count(),
            ));
        }

        $this->info('Árboles de presentación Filament actualizados.');

        return self::SUCCESS;
    }
}
