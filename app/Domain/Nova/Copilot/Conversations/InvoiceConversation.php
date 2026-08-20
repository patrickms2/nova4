<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Conversations;

use App\Domain\Nova\Copilot\ValueObjects\Step;

final readonly class InvoiceConversation implements CapabilityConversation
{
    public function capability(): string
    {
        return 'invoices';
    }

    /**
     * @return array<string, array<int, Step>>
     */
    public function definition(): array
    {
        return [
            'consult' => [
                new Step(
                    key: 'list',
                    prompt: 'Voy a consultar facturas.',
                    isFinal: true,
                ),
            ],
            'create' => [
                new Step(
                    key: 'client',
                    prompt: "¿Para qué cliente quieres crear la factura? Escribe el nombre, el número de cliente o 'ver' para ver la lista.",
                    branches: [
                        'ver' => 'client_list',
                        'lista' => 'client_list',
                        'listado' => 'client_list',
                        '_default' => 'confirm',
                    ],
                    fallbackPrompt: 'Indica el cliente o escribe "ver" para listar los clientes.',
                ),
                new Step(
                    key: 'client_list',
                    prompt: 'Mostrando clientes disponibles. Responde con el número del cliente.',
                    nextStep: 'confirm',
                    fallbackPrompt: 'Responde con el número del cliente.',
                ),
                new Step(
                    key: 'confirm',
                    prompt: '¿Confirmas que quieres crear la factura para este cliente?',
                    acceptedInputs: ['si', 'sí', 'yes', 'confirmar', 'ok'],
                    acceptedSynonyms: ['no' => 'cancel', 'cancelar' => 'cancel'],
                    branches: [
                        '_cancel' => 'cancelled',
                        '_default' => 'execute',
                    ],
                    fallbackPrompt: "Responde 'sí' para confirmar o 'cancelar' para descartar.",
                ),
                new Step(
                    key: 'execute',
                    prompt: 'Creando factura...',
                    isFinal: true,
                ),
                new Step(
                    key: 'cancelled',
                    prompt: 'Creación de factura cancelada. ¿En qué más puedo ayudarte?',
                    isCancel: true,
                ),
            ],
            'send' => [
                new Step(
                    key: 'invoice',
                    prompt: '¿Qué factura quieres enviar? Indica el número, código o cliente.',
                    nextStep: 'confirm',
                    fallbackPrompt: 'Indica la factura que quieres enviar.',
                ),
                new Step(
                    key: 'confirm',
                    prompt: '¿Confirmas que quieres enviar esta factura por email?',
                    acceptedInputs: ['si', 'sí', 'yes', 'confirmar', 'ok'],
                    acceptedSynonyms: ['no' => 'cancel', 'cancelar' => 'cancel'],
                    branches: [
                        '_cancel' => 'cancelled',
                        '_default' => 'execute',
                    ],
                    fallbackPrompt: "Responde 'sí' para enviar o 'cancelar' para descartar.",
                ),
                new Step(
                    key: 'execute',
                    prompt: 'Enviando factura...',
                    isFinal: true,
                ),
                new Step(
                    key: 'cancelled',
                    prompt: 'Envío cancelado. ¿En qué más puedo ayudarte?',
                    isCancel: true,
                ),
            ],
        ];
    }

    public function startStep(string $operation): ?string
    {
        return match ($operation) {
            'consult' => 'list',
            'create' => 'client',
            'send' => 'invoice',
            default => null,
        };
    }
}
