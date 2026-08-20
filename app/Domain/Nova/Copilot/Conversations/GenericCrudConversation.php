<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Conversations;

use App\Domain\Nova\Copilot\ValueObjects\Step;

final readonly class GenericCrudConversation implements CapabilityConversation
{
    public function __construct(
        private string $capability,
    ) {}

    public function capability(): string
    {
        return $this->capability;
    }

    /**
     * @return array<string, array<int, Step>>
     */
    public function definition(): array
    {
        $label = $this->label();

        return [
            'consult' => [
                new Step(
                    key: 'list',
                    prompt: "Voy a consultar {$label}.",
                    isFinal: true,
                ),
            ],
            'create' => [
                new Step(
                    key: 'details',
                    prompt: "¿Qué quieres registrar en {$label}? Puedes describirlo, enviar una foto, un PDF o un audio.",
                    nextStep: 'confirm',
                    fallbackPrompt: "No entendí los detalles. Por favor describe lo que quieres registrar en {$label}.",
                ),
                new Step(
                    key: 'confirm',
                    prompt: "¿Confirmas que quieres crear el registro en {$label}?",
                    acceptedInputs: ['si', 'sí', 'yes', 'confirmar', 'ok'],
                    acceptedSynonyms: ['no' => 'cancel', 'cancelar' => 'cancel', 'nope' => 'cancel'],
                    branches: [
                        '_cancel' => 'cancelled',
                        '_default' => 'execute',
                    ],
                    fallbackPrompt: "Responde 'sí' para confirmar o 'cancelar' para descartar.",
                ),
                new Step(
                    key: 'execute',
                    prompt: "Creando registro en {$label}...",
                    isFinal: true,
                ),
                new Step(
                    key: 'cancelled',
                    prompt: 'Operación cancelada. ¿En qué más puedo ayudarte?',
                    isCancel: true,
                ),
            ],
        ];
    }

    public function startStep(string $operation): ?string
    {
        return match ($operation) {
            'consult' => 'list',
            'create' => 'details',
            default => null,
        };
    }

    private function label(): string
    {
        return match ($this->capability) {
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
            'hotels' => 'hoteles',
            'taxi' => 'taxis',
            'bookings' => 'reservas',
            default => $this->capability,
        };
    }
}
