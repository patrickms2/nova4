<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace;

use App\Domain\Nova\Studio\Workspace\Capabilities\CapabilityCatalog;

final readonly class WorkspaceEvolution
{
    public function __construct(private CapabilityCatalog $catalog) {}

    /**
     * @param  array<int, string>  $areaIds
     * @param  array<int, string>  $improvementIds
     * @return array<int, array<string, mixed>>
     */
    public function navigation(array $areaIds, array $improvementIds = []): array
    {
        $areas = $this->catalog->businessAreas();
        $improvements = $this->catalog->improvements();

        return array_values(array_map(
            static function (string $areaId) use ($areas, $improvements, $improvementIds): array {
                $areaImprovements = array_values(array_filter(
                    $improvementIds,
                    static fn (string $improvementId): bool => ($improvements[$improvementId]['area'] ?? null) === $areaId,
                ));
                $improvementTools = array_merge(...array_map(
                    static fn (string $improvementId): array => $improvements[$improvementId]['tools'],
                    $areaImprovements,
                ));

                return [
                    'id' => $areaId,
                    ...$areas[$areaId],
                    'tools' => array_values(array_unique([
                        ...$areas[$areaId]['tools'],
                        ...$improvementTools,
                    ])),
                    'improvements' => $areaImprovements,
                ];
            },
            array_values(array_filter($areaIds, static fn (string $id): bool => isset($areas[$id]))),
        ));
    }

    /** @param array<string, mixed> $workspace
     * @return array<string, mixed>
     */
    public function normalize(array $workspace): array
    {
        $businessType = $workspace['business_type'] ?? 'winery';
        $blueprintId = $this->catalog->businessBlueprintId($businessType);
        $professionalVariants = $businessType === 'professional'
            ? array_values(array_unique($workspace['professional_variants']
                ?? array_filter([$workspace['professional_variant'] ?? null])))
            : [];
        $knownImprovements = array_keys($this->catalog->improvements());
        $storedIds = array_map(
            static fn (string $id): string => $id === 'orders' && $businessType !== 'store' ? 'product-orders' : $id,
            $workspace['capability_ids'] ?? [],
        );
        $areaIds = $this->catalog->defaultsFor(
            $blueprintId,
            $professionalVariants,
        );

        $improvementIds = array_values(array_unique([
            ...array_map(
                static fn (string $id): string => $id === 'orders' && $businessType !== 'store' ? 'product-orders' : $id,
                $workspace['improvement_ids'] ?? [],
            ),
            ...array_values(array_intersect($storedIds, $knownImprovements)),
        ]));
        $improvementIds = $this->supportedImprovements($areaIds, $improvementIds);

        return [
            ...$workspace,
            'blueprint_id' => $professionalVariants === []
                ? ($workspace['blueprint_id'] ?? $businessType)
                : implode('+', $professionalVariants),
            'professional_variants' => $professionalVariants,
            'professional_variant' => $professionalVariants[0] ?? null,
            'capability_ids' => array_values(array_unique([...$areaIds, ...$improvementIds])),
            'improvement_ids' => $improvementIds,
            'navigation' => $this->navigation($areaIds, $improvementIds),
        ];
    }

    /** @param array<string, mixed> $workspace
     * @return array<int, array<string, mixed>>
     */
    public function recommendations(array $workspace): array
    {
        $workspace = $this->normalize($workspace);
        $areaIds = array_column($workspace['navigation'], 'id');
        $activeImprovements = $workspace['improvement_ids'];
        $businessType = $workspace['business_type'] ?? 'winery';

        return array_values(array_filter(
            array_map(
                static fn (string $id, array $improvement): array => [
                    'id' => $id,
                    ...$improvement,
                    'reason' => $improvement['reasons'][$businessType] ?? $improvement['reason'],
                ],
                array_keys($this->catalog->improvements()),
                $this->catalog->improvements(),
            ),
            static fn (array $improvement): bool => in_array($improvement['area'], $areaIds, true)
                && array_intersect($improvement['unless_areas'] ?? [], $areaIds) === []
                && ! in_array($improvement['id'], $activeImprovements, true),
        ));
    }

    /** @param array<string, mixed> $workspace
     * @return array<string, mixed>
     */
    public function improve(array $workspace, string $improvementId): array
    {
        $workspace = $this->normalize($workspace);
        $availableIds = array_column($this->recommendations($workspace), 'id');

        if (! in_array($improvementId, $availableIds, true)) {
            return $workspace;
        }

        $workspace['improvement_ids'][] = $improvementId;
        $workspace['capability_ids'][] = $improvementId;
        $workspace['navigation'] = $this->navigation(
            array_column($workspace['navigation'], 'id'),
            $workspace['improvement_ids'],
        );
        $workspace['updated_at'] = now()->toIso8601String();

        return $workspace;
    }

    /** @param array<string, mixed> $workspace
     * @return array<string, mixed>
     */
    public function removeCapability(array $workspace, string $capabilityId): array
    {
        $workspace = $this->normalize($workspace);

        $workspace['capability_ids'] = array_values(array_filter(
            $workspace['capability_ids'],
            static fn (string $id): bool => $id !== $capabilityId,
        ));
        $workspace['improvement_ids'] = array_values(array_filter(
            $workspace['improvement_ids'],
            static fn (string $id): bool => $id !== $capabilityId,
        ));
        $workspace['navigation'] = $this->navigation(
            array_column($workspace['navigation'], 'id'),
            $workspace['improvement_ids'],
        );
        $workspace['updated_at'] = now()->toIso8601String();

        return $workspace;
    }

    /** @return array<string, mixed>|null */
    public function improvement(string $id): ?array
    {
        $improvement = $this->catalog->improvements()[$id] ?? null;

        return $improvement === null ? null : ['id' => $id, ...$improvement];
    }

    /**
     * @param  array<int, string>  $areaIds
     * @param  array<int, string>  $improvementIds
     * @return array<int, string>
     */
    private function supportedImprovements(array $areaIds, array $improvementIds): array
    {
        $improvements = $this->catalog->improvements();

        return array_values(array_filter(
            array_unique($improvementIds),
            static fn (string $id): bool => isset($improvements[$id])
                && in_array($improvements[$id]['area'], $areaIds, true),
        ));
    }
}
