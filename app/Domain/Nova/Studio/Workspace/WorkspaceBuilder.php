<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace;

use App\Domain\Nova\Studio\Workspace\Automations\AutomationCatalog;
use App\Domain\Nova\Studio\Workspace\Capabilities\CapabilityCatalog;
use App\Domain\Nova\Studio\Workspace\DataSources\DataSourceCatalog;
use App\Domain\Nova\Studio\Workspace\Interfaces\InterfaceCatalog;
use App\Domain\Nova\Studio\Workspace\Representations\RepresentationCatalog;
use Illuminate\Support\Str;

final readonly class WorkspaceBuilder
{
    public function __construct(
        private CapabilityCatalog $capabilities,
        private WorkspaceEvolution $evolution,
        private RepresentationCatalog $representations,
        private WorkspaceModel $model,
        private InterfaceCatalog $interfaces,
        private DataSourceCatalog $dataSources,
        private AutomationCatalog $automations,
    ) {}

    /**
     * @param  array<int, string>  $improvements
     * @param  string|array<int, string>|null  $professionalVariants
     * @param  array<int, string>  $objectives
     * @param  array<int, string>  $tools
     * @param  array<string, mixed>  $discoveredFacts
     * @return array<string, mixed>
     */
    public function build(
        string $businessType,
        array $improvements = [],
        ?string $website = null,
        string|array|null $professionalVariants = null,
        array $objectives = [],
        array $tools = [],
        array $discoveredFacts = [],
    ): array {
        $blueprintId = $this->capabilities->businessBlueprintId($businessType);
        $business = $this->capabilities->business($businessType);
        $variantIds = $businessType === 'professional'
            ? array_values(array_unique(is_array($professionalVariants)
                ? $professionalVariants
                : array_filter([$professionalVariants])))
            : [];
        $variants = array_values(array_filter(array_map(
            fn (string $id): ?array => $this->capabilities->professionalVariant($id),
            $variantIds,
        )));
        $variantIds = array_column($variants, 'id');
        $areaIds = $this->capabilities->defaultsFor($blueprintId, $variantIds);
        $improvements = array_values(array_filter(
            array_unique($improvements),
            fn (string $id): bool => ($this->capabilities->improvements()[$id]['area'] ?? null)
                && in_array($this->capabilities->improvements()[$id]['area'], $areaIds, true),
        ));

        $capabilityIds = array_values(array_unique([...$areaIds, ...$improvements]));
        $navigation = $this->evolution->navigation($areaIds, $improvements);
        $capabilityDetails = $this->capabilities->forIds($capabilityIds);
        $operationalModel = $this->model->build($capabilityIds);
        $sources = $this->dataSources->sourcesForTools($tools);

        $workspace = [
            'id' => (string) Str::uuid(),
            'business_type' => $businessType,
            'website' => $website,
            'discovered_facts' => $discoveredFacts,
            'business_name' => count($variants) === 1 ? $variants[0]['name'] : $business['name'],
            'business_icon' => count($variants) === 1 ? $variants[0]['icon'] : $business['icon'],
            'blueprint_id' => $variantIds === [] ? $blueprintId : implode('+', $variantIds),
            'professional_activity' => $variants[0]['activity'] ?? null,
            'professional_variants' => $variantIds,
            'professional_variant' => $variantIds[0] ?? null,
            'objectives' => $objectives,
            'tools' => $tools,
            'capability_ids' => $capabilityIds,
            'improvement_ids' => $improvements,
            'navigation' => $navigation,
            'capabilities' => $capabilityDetails,
            'operational_model' => [
                ...$operationalModel,
                'integrations' => $sources['connectors'],
                'sources' => $sources,
                'automations' => $this->automations->forCapabilities($capabilityIds),
                'indicators' => [],
                'permissions' => [],
                'artifacts' => [],
            ],
            'representations' => [],
            'interfaces' => $this->interfaces->forCapabilities($capabilityIds),
            'data_sources' => $this->dataSources->forTools($tools),
            'automations' => $this->automations->forCapabilities($capabilityIds),
            'created_at' => now()->toIso8601String(),
        ];

        $workspace['representations'] = $this->representations->fromWorkspace($workspace);

        return $workspace;
    }

}
