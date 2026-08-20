<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Conversations;

use App\Services\Nova\NovaOrchestratorService;

final readonly class NovaFactuOperationExecutor implements OperationExecutor
{
    public function execute(string $capability, string $operation, array $data, string $phone): array
    {
        $intent = $this->mapOperation($capability, $operation);

        if ($intent === null) {
            return [
                'success' => false,
                'reply' => "Operación '{$operation}' no soportada para {$capability}.",
            ];
        }

        $message = $this->buildMessage($operation, $data);

        return app(NovaOrchestratorService::class)->executeNovaFactuOperation($intent, $message, $phone);
    }

    public function supports(string $capability, string $operation): bool
    {
        return $this->mapOperation($capability, $operation) !== null;
    }

    private function mapOperation(string $capability, string $operation): ?string
    {
        return match ([$capability, $operation]) {
            ['reservations', 'consult'] => 'monthly_reservations_report',
            ['expenses', 'consult'] => 'list_expenses',
            ['expenses', 'create'] => 'create_expense',
            ['invoices', 'consult'] => 'list_invoices',
            ['invoices', 'create'] => 'create_invoice',
            ['invoices', 'send'] => 'send_invoice',
            ['customers', 'consult'] => 'list_clients',
            ['companies', 'consult'] => 'list_companies',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function buildMessage(string $operation, array $data): string
    {
        return match ($operation) {
            'create' => $data['details'] ?? $data['client'] ?? $data['input'] ?? '',
            'send' => $data['invoice'] ?? $data['input'] ?? '',
            default => $data['input'] ?? '',
        };
    }
}
