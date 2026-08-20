<?php

namespace App\Services\ExternalSync;

use App\Models\ExternalSource;
use App\Models\Server;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ExternalSourceRegistrar
{
    public function registerForServer(Server $server): Collection
    {
        $sources = collect($this->definitionsFor($server))
            ->map(fn (array $definition): ExternalSource => ExternalSource::query()->updateOrCreate(
                [
                    'server_id' => $server->id,
                    'source_platform' => $definition['source_platform'],
                    'source_label' => $definition['source_label'],
                ],
                $definition,
            ));

        $this->pauseStaleRegisteredSources($server, $sources);

        return $sources;
    }

    public function registerAll(): Collection
    {
        return Server::query()
            ->get()
            ->flatMap(fn (Server $server): Collection => $this->registerForServer($server))
            ->values();
    }

    private function definitionsFor(Server $server): array
    {
        $metadata = $server->metadata ?? [];
        $stack = collect($metadata['source_stack'] ?? [])->map(fn (mixed $item): string => Str::lower((string) $item));
        $business = (string) ($metadata['business'] ?? $server->name);
        $baseUrl = $metadata['remote_endpoint'] ?? null;
        $syncTargets = $metadata['sync_targets'] ?? [];

        if (is_array($syncTargets) && $syncTargets !== []) {
            return collect($syncTargets)
                ->map(fn (array $target): array => $this->definitionFromSyncTarget($server, $business, $baseUrl, $target))
                ->all();
        }

        $definitions = [];

        if ($stack->contains('woocommerce')) {
            $definitions[] = $this->definition($server, $business, 'woo', 'Woo', 'api', $baseUrl);
        }

        if ($stack->contains('latepoint')) {
            $definitions[] = $this->definition($server, $business, 'latepoint', 'LatePoint', 'api', $baseUrl);
        }

        if ($stack->contains('magento')) {
            $definitions[] = $this->definition($server, $business, 'magento', 'Magento', 'api', $baseUrl);
        }

        if ($stack->contains('sirvo') || Str::contains(Str::lower($server->slug), 'sirvo')) {
            $definitions[] = $this->definition($server, $business, 'sirvo', 'Reservas', 'api', $baseUrl);
        }

        return $definitions;
    }

    private function definitionFromSyncTarget(Server $server, string $business, ?string $baseUrl, array $target): array
    {
        $platform = Str::lower((string) ($target['source_platform'] ?? 'mcp'));
        $platformLabel = match ($platform) {
            'woo' => 'Woo',
            'latepoint' => 'LatePoint',
            'magento' => 'Magento',
            'sirvo' => 'Reservas',
            default => Str::headline($platform),
        };
        $suffix = trim((string) ($target['source_label_suffix'] ?? ''));
        $sourceLabel = trim("{$business} · {$platformLabel}".($suffix !== '' ? " · {$suffix}" : ''));

        return $this->definition(
            $server,
            $business,
            $platform,
            $platformLabel,
            (string) ($target['connection_type'] ?? 'api'),
            $baseUrl,
            [
                'source_label' => $sourceLabel,
                'resource_type' => $target['resource_type'] ?? null,
                'target_model' => $target['target_model'] ?? null,
                'sync_direction' => $target['sync_direction'] ?? 'remote_to_local',
                'capability' => $target['capability'] ?? null,
                'status' => $target['status'] ?? 'active',
                'settings' => ['sync_target' => $target],
            ],
        );
    }

    private function definition(
        Server $server,
        string $business,
        string $platform,
        string $platformLabel,
        string $connectionType,
        ?string $baseUrl,
        array $overrides = [],
    ): array {
        $definition = [
            'server_id' => $server->id,
            'name' => "{$business} {$platformLabel}",
            'business_name' => $business,
            'source_platform' => $platform,
            'source_label' => "{$business} · {$platformLabel}",
            'connection_type' => $connectionType,
            'base_url' => $baseUrl,
            'api_url' => $baseUrl,
            'resource_type' => $this->defaultResourceType($platform),
            'target_model' => $this->defaultTargetModel($platform),
            'sync_direction' => 'remote_to_local',
            'status' => 'active',
            'settings' => [
                'registered_from' => 'server_metadata',
                'server_slug' => $server->slug,
            ],
        ];

        if (isset($overrides['settings']) && is_array($overrides['settings'])) {
            $overrides['settings'] = array_merge($definition['settings'], $overrides['settings']);
        }

        return array_merge($definition, $overrides);
    }

    private function pauseStaleRegisteredSources(Server $server, Collection $activeSources): void
    {
        $activeKeys = $activeSources
            ->map(fn (ExternalSource $source): string => $source->source_platform.'|'.$source->source_label)
            ->all();

        $server->externalSources()
            ->where('status', 'active')
            ->get()
            ->filter(fn (ExternalSource $source): bool => data_get($source->settings, 'registered_from') === 'server_metadata')
            ->reject(fn (ExternalSource $source): bool => in_array($source->source_platform.'|'.$source->source_label, $activeKeys, true))
            ->each(fn (ExternalSource $source): bool => $source->forceFill(['status' => 'paused'])->save());
    }

    private function defaultResourceType(string $platform): ?string
    {
        return match ($platform) {
            'woo', 'magento' => 'generic_product',
            'latepoint', 'sirvo' => 'restaurant_booking',
            default => null,
        };
    }

    private function defaultTargetModel(string $platform): ?string
    {
        return match ($platform) {
            'woo', 'magento' => 'external_catalog_item',
            'latepoint', 'sirvo' => 'restaurant_booking',
            default => null,
        };
    }
}
