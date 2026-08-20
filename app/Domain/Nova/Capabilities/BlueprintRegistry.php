<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

use App\Domain\Nova\Businesses\ProfessionalCatalog;
use App\Domain\Nova\Studio\Workspace\Capabilities\CapabilityCatalog;
use InvalidArgumentException;

final class BlueprintRegistry
{
    /** @var array<string, BusinessBlueprint> */
    private array $blueprints = [];

    public function __construct(
        private CapabilityCatalog $capabilityCatalog,
        ProfessionalCatalog $professionals,
    ) {
        $this->register(new BusinessBlueprint('hotel', 'Hotel', [
            'hotel-reservations', 'rooms', 'payments', 'invoices', 'messaging', 'crm', 'knowledge', 'reporting', 'synchronization',
        ]));
        $this->register(new BusinessBlueprint('restaurant', 'Restaurante', [
            'restaurant-reservations', 'tables', 'payments', 'invoices', 'messaging', 'crm', 'knowledge', 'reporting', 'synchronization',
        ]));
        $this->register(new BusinessBlueprint('winery', 'Bodega', [
            'winery-reservations', 'wine-tours', 'payments', 'invoices', 'messaging', 'crm', 'knowledge', 'reporting', 'synchronization',
        ]));

        foreach ($professionals->variants() as $id => $variant) {
            $this->register(new BusinessBlueprint(
                $id,
                $variant['name'],
                [
                    ...array_map(
                        fn (string $area): string => $professionals->capabilityId($area),
                        $variant['areas'],
                    ),
                    'reporting',
                ],
            ));
        }
    }

    public function register(BusinessBlueprint $blueprint): void
    {
        $this->blueprints[$blueprint->id] = $blueprint;
    }

    public function get(string $businessType): BusinessBlueprint
    {
        $id = strtolower($businessType);
        $blueprintId = $this->capabilityCatalog->businessBlueprintId($id);

        if (isset($this->blueprints[$blueprintId])) {
            return $this->blueprints[$blueprintId];
        }

        $ids = array_values(array_filter(explode('+', $id)));

        if (count($ids) > 1 && array_all($ids, fn (string $blueprintId): bool => isset($this->blueprints[$blueprintId]))) {
            $blueprints = array_map(fn (string $blueprintId): BusinessBlueprint => $this->blueprints[$blueprintId], $ids);

            return new BusinessBlueprint(
                id: implode('+', $ids),
                name: implode(' + ', array_column($blueprints, 'name')),
                capabilityIds: array_values(array_unique(array_merge(
                    ...array_column($blueprints, 'capabilityIds'),
                ))),
                businessTypes: array_column($blueprints, 'name'),
            );
        }

        throw new InvalidArgumentException("El modelo de negocio [{$businessType}] no está registrado.");
    }

    /** @return array<int, BusinessBlueprint> */
    public function all(): array
    {
        return array_values($this->blueprints);
    }
}
