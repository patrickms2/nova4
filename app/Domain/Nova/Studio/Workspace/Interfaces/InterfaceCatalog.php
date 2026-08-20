<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\Interfaces;

final readonly class InterfaceCatalog
{
    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<string, mixed>
     */
    public function forCapabilities(array $capabilityIds): array
    {
        $tools = array_values(array_filter(array_map(
            static fn (string $id): ?array => self::mcpToolFor($id),
            array_values(array_unique($capabilityIds)),
        )));

        return [
            'mcp' => [
                'tools' => $tools,
            ],
            'rest' => [
                'endpoints' => array_map(
                    static fn (array $tool): string => '/api'.str_replace('.', '/', $tool['id']),
                    $tools,
                ),
            ],
            'graphql' => [
                'types' => array_map(
                    static fn (array $tool): string => self::pascalCase($tool['id']),
                    $tools,
                ),
            ],
            'webhooks' => [
                'events' => array_map(
                    static fn (array $tool): string => str_replace('.', ':', $tool['id']).':completed',
                    $tools,
                ),
            ],
            'sdk' => [
                'available' => true,
                'methods' => array_map(
                    static fn (array $tool): string => self::camelCase($tool['id']),
                    $tools,
                ),
            ],
        ];
    }

    /** @return array<string, string>|null */
    private static function mcpToolFor(string $capabilityId): ?array
    {
        return match ($capabilityId) {
            'reservations', 'hotel-reservations', 'restaurant-reservations', 'winery-reservations' => [
                'id' => 'reservation.create',
                'description' => 'Crea una nueva reserva',
            ],
            'invoices' => [
                'id' => 'invoice.generate',
                'description' => 'Genera una factura',
            ],
            'payments' => [
                'id' => 'payment.list',
                'description' => 'Lista los pagos registrados',
            ],
            'customers', 'guests', 'crm' => [
                'id' => 'customer.search',
                'description' => 'Busca un cliente',
            ],
            'products', 'store-catalog', 'winery-catalog', 'restaurant-menu' => [
                'id' => 'product.list',
                'description' => 'Lista productos disponibles',
            ],
            'orders', 'product-orders' => [
                'id' => 'order.create',
                'description' => 'Crea un pedido',
            ],
            'calendar', 'availability' => [
                'id' => 'calendar.availability',
                'description' => 'Consulta disponibilidad',
            ],
            'reports', 'knowledge' => [
                'id' => 'report.generate',
                'description' => 'Genera un informe',
            ],
            'messaging', 'whatsapp' => [
                'id' => 'message.send',
                'description' => 'Envía un mensaje',
            ],
            default => null,
        };
    }

    private static function pascalCase(string $id): string
    {
        return str_replace(' ', '', ucwords(str_replace('.', ' ', $id)));
    }

    private static function camelCase(string $id): string
    {
        $pascal = self::pascalCase($id);

        return lcfirst($pascal);
    }
}
