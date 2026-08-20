<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;

final readonly class MenuEngine
{
    /**
     * @return array<int, array{id: string, label: string, icon?: string, capability?: string}>
     */
    public function mainMenu(ConversationContext $context): array
    {
        $workspace = $context->workspace;
        $capabilities = $this->normalizeCapabilities($workspace['capabilities'] ?? []);

        $menu = [
            [
                'id' => 'summary',
                'label' => 'Resumen',
                'icon' => '📊',
            ],
        ];

        foreach ($capabilities as $index => $capability) {
            $menu[] = [
                'id' => (string) ($capability['id'] ?? $index),
                'label' => (string) ($capability['name'] ?? $capability['id'] ?? 'Capability'),
                'icon' => (string) ($capability['icon'] ?? ''),
                'capability' => (string) ($capability['id'] ?? ''),
            ];
        }

        $menu[] = [
            'id' => 'settings',
            'label' => 'Configuración',
            'icon' => '⚙️',
        ];

        return $menu;
    }

    /**
     * @return array<int, array{id: string, label: string, operation: string}>
     */
    public function contextualMenu(ConversationContext $context): array
    {
        $capability = $context->activeCapability;

        if ($capability === null) {
            return $this->mainMenu($context);
        }

        $label = $this->capabilityLabel($capability);

        $menu = [
            [
                'id' => 'list',
                'label' => "Ver {$label}",
                'operation' => 'capability.list',
            ],
            [
                'id' => 'create',
                'label' => "Crear {$label}",
                'operation' => 'capability.create',
            ],
        ];

        if ($context->activeEntityId !== null) {
            $menu[] = [
                'id' => 'edit',
                'label' => 'Editar',
                'operation' => 'capability.edit',
            ];
            $menu[] = [
                'id' => 'delete',
                'label' => 'Eliminar',
                'operation' => 'capability.delete',
            ];
            $menu[] = [
                'id' => 'send',
                'label' => 'Enviar',
                'operation' => 'capability.send',
            ];
        }

        $menu[] = [
            'id' => 'back',
            'label' => 'Volver al menú principal',
            'operation' => 'copilot.menu.main',
        ];

        return $menu;
    }

    /**
     * @param  array<int, array<string, mixed>|string>  $capabilities
     * @return array<int, array<string, mixed>>
     */
    private function normalizeCapabilities(array $capabilities): array
    {
        $normalized = [];

        foreach ($capabilities as $capability) {
            if (is_array($capability)) {
                $normalized[] = $capability;

                continue;
            }

            if (is_string($capability) && $capability !== '') {
                $normalized[] = [
                    'id' => $capability,
                    'name' => $this->capabilityLabel($capability),
                ];
            }
        }

        return $normalized;
    }

    private function capabilityLabel(string $capability): string
    {
        $labels = [
            'reservations' => 'reservas',
            'customers' => 'clientes',
            'invoices' => 'facturas',
            'expenses' => 'gastos',
            'payments' => 'pagos',
            'documents' => 'documentos',
            'inventory' => 'inventario',
            'products' => 'productos',
            'issues' => 'incidencias',
            'tasks' => 'tareas',
            'employees' => 'empleados',
            'appointments' => 'citas',
            'restaurant-menu' => 'menú',
            'tours' => 'tours',
            'winery-catalog' => 'vinos',
        ];

        return $labels[$capability] ?? $capability;
    }
}
