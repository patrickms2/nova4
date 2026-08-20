<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

final class MissionResultBuilder
{
    /**
     * @param  array<string, mixed>  $mission
     * @param  array<int, array<string, mixed>>  $navigation
     */
    public function build(array $mission, array $navigation): MissionResult
    {
        $area = $this->targetArea($mission, $navigation);
        $outcomes = array_values(array_unique(array_map(
            fn (array $capability): string => $this->outcome(
                (string) $capability['id'],
                (string) $capability['name'],
            ),
            array_filter(
                $mission['capabilities'] ?? [],
                static fn (array $capability): bool => ($capability['id'] ?? null) !== 'planning',
            ),
        )));

        if ($outcomes === []) {
            $outcomes[] = 'El objetivo se ha completado.';
        }

        $files = array_values(array_map(
            static fn (array $artifact): array => [
                'id' => (string) $artifact['id'],
                'name' => (string) $artifact['name'],
                'type' => (string) $artifact['type'],
                'path' => (string) $artifact['path'],
            ],
            $mission['artifacts'] ?? [],
        ));

        return new MissionResult(
            missionId: (string) $mission['id'],
            goal: (string) $mission['goal'],
            summary: $outcomes[0],
            targetAreaId: (string) $area['id'],
            targetAreaName: (string) $area['name'],
            targetAreaIcon: (string) $area['icon'],
            outcomes: $outcomes,
            impact: [
                $area['name'].' está al día.',
                'El Workspace refleja el trabajo realizado.',
                $files === [] ? 'Todo está preparado para continuar.' : count($files).' archivos están disponibles para revisar.',
                'Puedes continuar exactamente donde NOVA lo dejó.',
            ],
            files: $files,
            suggestedGoal: $this->suggestedGoal((string) $area['id'], (string) $area['name']),
            completedAt: (string) ($mission['completed_at'] ?? now()->toIso8601String()),
        );
    }

    /**
     * @param  array<string, mixed>  $mission
     * @param  array<int, array<string, mixed>>  $navigation
     * @return array<string, mixed>
     */
    private function targetArea(array $mission, array $navigation): array
    {
        $areaIds = array_column($navigation, 'id');
        $capabilityIds = array_reverse(array_column($mission['capabilities'] ?? [], 'id'));

        foreach ($capabilityIds as $capabilityId) {
            if (in_array($capabilityId, ['planning', 'reporting', 'synchronization'], true)) {
                continue;
            }

            $candidate = $this->areaForCapability((string) $capabilityId);

            if ($candidate !== null && in_array($candidate, $areaIds, true)) {
                return collect($navigation)->firstWhere('id', $candidate);
            }
        }

        $goalArea = $this->areaForGoal((string) ($mission['goal'] ?? ''));

        if ($goalArea !== null && in_array($goalArea, $areaIds, true)) {
            return collect($navigation)->firstWhere('id', $goalArea);
        }

        return collect($navigation)->firstWhere('id', 'reports')
            ?? collect($navigation)->firstWhere('id', 'nova')
            ?? $navigation[0]
            ?? ['id' => 'nova', 'name' => 'NOVA', 'icon' => '✦'];
    }

    private function areaForCapability(string $id): ?string
    {
        if ($id === 'professional-documents') {
            return 'professional-documents';
        }

        if (str_starts_with($id, 'professional-')) {
            return substr($id, strlen('professional-'));
        }

        return match ($id) {
            'hotel-reservations', 'restaurant-reservations', 'winery-reservations',
            'availability', 'calendar', 'messaging' => 'reservations',
            'crm' => 'customers',
            'payments' => 'payments',
            'invoices' => 'invoices',
            'rooms' => 'rooms',
            'tables' => 'tables',
            'wine-tours' => 'tours',
            'reporting', 'synchronization' => 'reports',
            default => null,
        };
    }

    private function outcome(string $id, string $name): string
    {
        return match ($id) {
            'synchronization' => 'La información del negocio se ha sincronizado.',
            'messaging' => 'La comunicación con el cliente está preparada.',
            'crm' => 'Los datos del cliente se han actualizado.',
            'calendar' => 'El calendario se ha actualizado.',
            'availability' => 'La disponibilidad ha quedado confirmada.',
            'hotel-reservations', 'restaurant-reservations', 'winery-reservations' => 'La reserva se ha creado correctamente.',
            'payments' => 'Los pagos pendientes se han revisado.',
            'invoices' => 'Las facturas están preparadas.',
            'reporting' => 'El resumen del trabajo está disponible.',
            default => $name.' se ha actualizado.',
        };
    }

    private function areaForGoal(string $goal): ?string
    {
        $goal = mb_strtolower($goal);

        return match (true) {
            str_contains($goal, 'factura') => 'invoices',
            str_contains($goal, 'reserva') => 'reservations',
            str_contains($goal, 'cita') => 'appointments',
            str_contains($goal, 'ticket') => 'support-tickets',
            str_contains($goal, 'document') => 'professional-documents',
            str_contains($goal, 'catálogo'), str_contains($goal, 'catalogo'),
            str_contains($goal, 'producto') => 'products',
            str_contains($goal, 'cliente') => 'customers',
            str_contains($goal, 'pago'), str_contains($goal, 'cobro') => 'payments',
            default => null,
        };
    }

    private function suggestedGoal(string $areaId, string $areaName): string
    {
        return match ($areaId) {
            'reservations', 'appointments' => 'Notificar al cliente',
            'invoices' => 'Enviar las facturas a los clientes',
            'products', 'restaurant-menu', 'store-catalog', 'winery-catalog' => 'Publicar los cambios del catálogo',
            'support-tickets' => 'Revisar los tickets pendientes',
            'professional-documents' => 'Enviar los documentos preparados',
            default => 'Revisar el trabajo completado en '.$areaName,
        };
    }
}
