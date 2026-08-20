<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaRepresentation;
use App\Support\Nova\FilamentStructureImporter;
use App\Support\Nova\FilamentStructureScanner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class NovaDiscoverFilamentStructure extends Command
{
    protected $signature = 'nova:discover-filament-structure {--resource= : Solo una clase o nombre de Resource}';

    protected $description = 'Discover deep Filament UI structure: pages, tabs, widgets, actions, relations and view types.';

    public function handle(
        FilamentStructureScanner $scanner,
        FilamentStructureImporter $importer,
    ): int {
        $representations = NovaRepresentation::query()
            ->where('type', NovaRepresentationType::Filament)
            ->when(
                $this->option('resource'),
                fn ($query) => $query->where('class_name', 'like', '%'.$this->option('resource').'%')
            )
            ->orderBy('name')
            ->get();

        if ($representations->isEmpty()) {
            $this->warn('No hay representaciones Filament. Ejecuta primero nova:discover-filament.');

            return self::SUCCESS;
        }

        foreach ($representations as $representation) {
            $path = $representation->settings['path'] ?? null;

            if (! $path) {
                $this->warn('Sin path: '.$representation->class_name);
                continue;
            }

            $absolute = base_path($path);

            if (! File::exists($absolute)) {
                $this->warn('No existe: '.$absolute);
                continue;
            }

            $structure = $scanner->scan($absolute);
            $root = $importer->import($representation, $structure);

            $settings = $representation->settings ?? [];
            $settings['structure_discovered_at'] = now()->toIso8601String();
            $settings['structure_summary'] = [
                'pages' => count($structure['pages'] ?? []),
                'relations' => count($structure['relations'] ?? []),
                'widgets' => count($structure['widgets'] ?? []),
                'subnavigation' => count($structure['record_subnavigation'] ?? []),
            ];
            $representation->update(['settings' => $settings]);

            $this->line(sprintf(
                '✓ %s · pages:%d relations:%d widgets:%d subnav:%d',
                $representation->name,
                count($structure['pages'] ?? []),
                count($structure['relations'] ?? []),
                count($structure['widgets'] ?? []),
                count($structure['record_subnavigation'] ?? []),
            ));
        }

        $this->info('Filament Structure Discovery 2.0 completo.');

        return self::SUCCESS;
    }
}
