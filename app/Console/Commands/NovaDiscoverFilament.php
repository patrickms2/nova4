<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Nova\NovaRepresentationStatus;
use App\Support\Nova\FilamentResourceDiscovery;
use Illuminate\Console\Command;

final class NovaDiscoverFilament extends Command
{
    protected $signature = 'nova:discover-filament {--dry-run : Detecta sin persistir}';

    protected $description = 'Discover existing Filament Resources and map them to NOVA Resources and Capabilities.';

    public function handle(FilamentResourceDiscovery $discovery): int
    {
        $items = $discovery->discover(! $this->option('dry-run'));

        $this->table(
            ['Resource', 'Model', 'Panel', 'Status'],
            $items->map(function ($item): array {
                if (is_array($item)) {
                    return [
                        $item['class_name'],
                        $item['model_class'] ?? '—',
                        $item['panel_guess'] ?? '—',
                        'dry-run',
                    ];
                }

                return [
                    $item->class_name,
                    $item->model_class ?? '—',
                    $item->panel?->key ?? '—',
                    $item->status instanceof NovaRepresentationStatus ? $item->status->value : (string) $item->status,
                ];
            })->all()
        );

        $this->info($items->count().' Filament Resources detectados.');

        return self::SUCCESS;
    }
}
