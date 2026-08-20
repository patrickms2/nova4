<?php

namespace App\Actions\Workflow;

use App\Models\Server;

class RegisterNovaDataAction
{
    /**
     * Called by the workflow "Action" node.
     *
     * Expected payload keys:
     *   - normalized_data : array (datos normalizados)
     *   - server_slug     : string (slug del server MCP)
     *   - record_type    : booking | order | transaction
     *   - intent_key     : string (opcional, para bookings)
     */
    public function __invoke(array $payload): array
    {
        $normalizedData = $payload['normalized_data'] ?? null;
        $serverSlug = $payload['server_slug'] ?? null;
        $recordType = $payload['record_type'] ?? 'booking';
        $intentKey = $payload['intent_key'] ?? null;

        if (! $normalizedData || ! $serverSlug) {
            return ['error' => 'normalized_data and server_slug are required.'];
        }

        $server = Server::where('slug', $serverSlug)
            ->where('is_active', true)
            ->first();

        if (! $server) {
            return ['error' => "Server [{$serverSlug}] not found or inactive."];
        }

        try {
            $recordId = $this->registerData($normalizedData, $server, $recordType, $intentKey);

            return [
                'success' => true,
                'record_id' => $recordId,
                'record_type' => $recordType,
                'source' => $server->name,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    private function registerData(array $normalizedData, Server $server, string $recordType, ?string $intentKey): int
    {
        // TODO: Implementar registro en tablas de Nova
        // Por ahora, simulamos el registro

        return match ($recordType) {
            'booking' => $this->registerBooking($normalizedData, $server, $intentKey),
            'order' => $this->registerOrder($normalizedData, $server),
            'transaction' => $this->registerTransaction($normalizedData, $server),
            default => 0,
        };
    }

    private function registerBooking(array $normalizedData, Server $server, ?string $intentKey): int
    {
        // TODO: Crear registro en nova_external_bookings
        // Por ahora, retornamos un ID simulado
        return rand(1000, 9999);
    }

    private function registerOrder(array $normalizedData, Server $server): int
    {
        // TODO: Crear registro en nova_external_orders
        // Por ahora, retornamos un ID simulado
        return rand(1000, 9999);
    }

    private function registerTransaction(array $normalizedData, Server $server): int
    {
        // TODO: Crear registro en nova_external_transactions
        // Por ahora, retornamos un ID simulado
        return rand(1000, 9999);
    }
}
