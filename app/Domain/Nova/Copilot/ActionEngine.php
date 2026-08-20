<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Enums\Confidence;
use App\Domain\Nova\Copilot\Enums\IntentName;
use App\Domain\Nova\Copilot\ValueObjects\Action;
use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;
use App\Domain\Nova\Copilot\ValueObjects\Intent;

final readonly class ActionEngine
{
    /**
     * @return array<int, Action>
     */
    public function propose(Intent $intent, ConversationContext $context): array
    {
        $capability = $intent->targetCapability ?? $context->activeCapability;

        return match ($intent->name) {
            IntentName::GREETING => [],
            IntentName::MENU => [],
            IntentName::OPERATIONAL_MENU => [],
            IntentName::CONSULT => $this->consultActions($capability, $intent),
            IntentName::CREATE => $this->createActions($capability, $intent),
            IntentName::EDIT => $this->editActions($capability, $context),
            IntentName::DELETE => $this->deleteActions($capability, $context),
            IntentName::SEND => $this->sendActions($capability, $context),
            IntentName::IMPORT => $this->importActions($capability),
            IntentName::ANALYZE => $this->analyzeActions($capability, $context),
            IntentName::COMPARE => $this->compareActions($capability),
            IntentName::CONFIGURE => $this->configureActions(),
            IntentName::REPLY => $this->replyActions($context),
            IntentName::CONFIRM => $this->confirmActions($context),
            IntentName::CANCEL => $this->cancelActions($context),
            IntentName::UNKNOWN => [],
        };
    }

    /**
     * @return array<int, Action>
     */
    private function consultActions(?string $capability, Intent $intent): array
    {
        $name = $this->capabilityLabel($capability);

        return [
            new Action(
                id: 'list',
                label: "Ver {$name}",
                operation: 'capability.list',
                parameters: ['capability' => $capability],
                description: "Mostrar listado de {$name}",
            ),
            new Action(
                id: 'search',
                label: 'Buscar',
                operation: 'capability.search',
                parameters: [
                    'capability' => $capability,
                    'filters' => array_filter([
                        'amount' => $intent->entities['extracted_amount'] ?? null,
                        'date' => $intent->entities['extracted_date'] ?? null,
                    ]),
                ],
                description: 'Buscar con filtros detectados',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function createActions(?string $capability, Intent $intent): array
    {
        $name = $this->capabilityLabel($capability);

        return [
            new Action(
                id: 'create',
                label: "Crear {$name}",
                operation: 'capability.create',
                parameters: array_filter([
                    'capability' => $capability,
                    'amount' => $intent->entities['extracted_amount'] ?? null,
                    'date' => $intent->entities['extracted_date'] ?? null,
                ]),
                description: "Crear un nuevo registro de {$name}",
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function editActions(?string $capability, ConversationContext $context): array
    {
        $name = $this->capabilityLabel($capability);

        return [
            new Action(
                id: 'edit',
                label: "Editar {$name}",
                operation: 'capability.edit',
                parameters: [
                    'capability' => $capability,
                    'entity_type' => $context->activeEntityType,
                    'entity_id' => $context->activeEntityId,
                ],
                description: "Editar el {$name} activo",
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function deleteActions(?string $capability, ConversationContext $context): array
    {
        $name = $this->capabilityLabel($capability);

        return [
            new Action(
                id: 'delete',
                label: "Eliminar {$name}",
                operation: 'capability.delete',
                parameters: [
                    'capability' => $capability,
                    'entity_type' => $context->activeEntityType,
                    'entity_id' => $context->activeEntityId,
                ],
                requiresConfirmation: true,
                description: "Eliminar el {$name} activo",
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function sendActions(?string $capability, ConversationContext $context): array
    {
        return [
            new Action(
                id: 'send',
                label: 'Enviar',
                operation: 'capability.send',
                parameters: [
                    'capability' => $capability,
                    'entity_type' => $context->activeEntityType,
                    'entity_id' => $context->activeEntityId,
                ],
                requiresConfirmation: true,
                description: 'Enviar al contacto asociado',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function importActions(?string $capability): array
    {
        return [
            new Action(
                id: 'import',
                label: 'Importar',
                operation: 'capability.import',
                parameters: ['capability' => $capability],
                description: 'Importar datos externos',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function analyzeActions(?string $capability, ConversationContext $context): array
    {
        return [
            new Action(
                id: 'analyze_image',
                label: 'Analizar imagen',
                operation: 'media.analyze',
                parameters: [
                    'capability' => $capability,
                    'media_type' => 'image',
                    'context' => $context->activeEntityType,
                ],
                description: 'Extraer información de la imagen',
            ),
            new Action(
                id: 'analyze_document',
                label: 'Analizar documento',
                operation: 'media.analyze',
                parameters: [
                    'capability' => $capability,
                    'media_type' => 'document',
                    'context' => $context->activeEntityType,
                ],
                description: 'Extraer información del documento',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function compareActions(?string $capability): array
    {
        return [
            new Action(
                id: 'compare',
                label: 'Comparar',
                operation: 'capability.compare',
                parameters: ['capability' => $capability],
                description: 'Comparar registros',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function configureActions(): array
    {
        return [
            new Action(
                id: 'configure',
                label: 'Configurar',
                operation: 'workspace.configure',
                parameters: [],
                description: 'Abrir configuración del Workspace',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function replyActions(ConversationContext $context): array
    {
        return [
            new Action(
                id: 'continue',
                label: 'Continuar',
                operation: 'conversation.continue',
                parameters: [
                    'active_capability' => $context->activeCapability,
                    'active_entity_type' => $context->activeEntityType,
                    'active_entity_id' => $context->activeEntityId,
                ],
                description: 'Continuar con el contexto actual',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function confirmActions(ConversationContext $context): array
    {
        return [
            new Action(
                id: 'confirm',
                label: 'Confirmar',
                operation: 'operation.confirm',
                parameters: ['pending_operation' => $context->pendingOperation],
                description: 'Confirmar operación pendiente',
            ),
        ];
    }

    /**
     * @return array<int, Action>
     */
    private function cancelActions(ConversationContext $context): array
    {
        return [
            new Action(
                id: 'cancel',
                label: 'Cancelar',
                operation: 'operation.cancel',
                parameters: ['pending_operation' => $context->pendingOperation],
                description: 'Cancelar operación pendiente',
            ),
        ];
    }

    private function capabilityLabel(?string $capability): string
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
            'companies' => 'empresas',
            'employees' => 'empleados',
            'appointments' => 'citas',
            'restaurant-menu' => 'menú',
            'tours' => 'tours',
            'winery-catalog' => 'vinos',
        ];

        if ($capability === null) {
            return 'registro';
        }

        return $labels[$capability] ?? $capability;
    }
}
