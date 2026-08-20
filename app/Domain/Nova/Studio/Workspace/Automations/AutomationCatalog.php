<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\Automations;

final readonly class AutomationCatalog
{
    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<int, array<string, string>>
     */
    public function forCapabilities(array $capabilityIds): array
    {
        return array_values(array_filter(array_map(
            static fn (string $id): ?array => match ($id) {
                'messaging', 'whatsapp' => [
                    'id' => 'auto-whatsapp',
                    'name' => 'Respuestas automáticas por WhatsApp',
                    'trigger' => 'Mensaje entrante',
                ],
                'synchronization' => [
                    'id' => 'auto-sync',
                    'name' => 'Sincronización de datos entre fuentes',
                    'trigger' => 'Cambio detectado',
                ],
                'invoices' => [
                    'id' => 'auto-invoice',
                    'name' => 'Generación automática de facturas',
                    'trigger' => 'Cobro completado',
                ],
                'reports', 'knowledge' => [
                    'id' => 'auto-report',
                    'name' => 'Informes periódicos',
                    'trigger' => 'Cada semana',
                ],
                'payments' => [
                    'id' => 'auto-payment',
                    'name' => 'Cobros programados',
                    'trigger' => 'Vencimiento de reserva',
                ],
                'shipping' => [
                    'id' => 'auto-shipping',
                    'name' => 'Alertas de envío',
                    'trigger' => 'Estado del pedido',
                ],
                default => null,
            },
            array_values(array_unique($capabilityIds)),
        )));
    }
}
