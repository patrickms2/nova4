<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace;

final readonly class WorkspaceModel
{
    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<string, mixed>
     */
    public function build(array $capabilityIds): array
    {
        $entities = array_values(array_filter(array_map(
            static fn (string $id): ?string => self::entityFor($id),
            array_values(array_unique($capabilityIds)),
        )));

        return [
            'entities' => $entities,
            'relations' => $this->relationsFor($entities),
            'processes' => $this->processesFor($capabilityIds),
        ];
    }

    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<string, string> Capability id => Entity name.
     */
    public function entitiesByCapability(array $capabilityIds): array
    {
        $map = [];

        foreach (array_unique($capabilityIds) as $id) {
            $entity = self::entityFor($id);

            if ($entity !== null) {
                $map[$id] = $entity;
            }
        }

        return $map;
    }

    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<string, string> Capability id => Process name.
     */
    public function processesByCapability(array $capabilityIds): array
    {
        $map = [];

        foreach (array_unique($capabilityIds) as $id) {
            $process = self::processFor($id);

            if ($process !== null) {
                $map[$id] = $process;
            }
        }

        return $map;
    }

    public static function entityFor(string $capabilityId): ?string
    {
        return match ($capabilityId) {
            'home' => null,
            'products', 'store-catalog', 'winery-catalog', 'restaurant-menu' => 'Producto',
            'reservations', 'hotel-reservations', 'restaurant-reservations', 'winery-reservations' => 'Reserva',
            'customers', 'guests', 'crm', 'loyalty' => 'Cliente',
            'rooms' => 'Habitación',
            'payments' => 'Pago',
            'invoices', 'documents' => 'Factura',
            'orders', 'product-orders', 'shipping' => 'Pedido',
            'reports', 'knowledge' => 'Informe',
            'messaging', 'whatsapp' => 'Mensaje',
            'synchronization' => 'Sincronización',
            'calendar', 'availability' => 'Calendario',
            'tickets' => 'Entrada',
            'phone-ai' => 'Llamada',
            'inventory', 'existencias' => 'Existencia',
            'nova' => 'NOVA',
            default => null,
        };
    }

    /**
     * @param  array<int, string>  $entities
     * @return array<int, array<string, string>>
     */
    private function relationsFor(array $entities): array
    {
        $relations = [];

        if (in_array('Cliente', $entities, true) && in_array('Reserva', $entities, true)) {
            $relations[] = ['from' => 'Cliente', 'to' => 'Reserva', 'type' => 'tiene'];
        }

        if (in_array('Reserva', $entities, true) && in_array('Pago', $entities, true)) {
            $relations[] = ['from' => 'Reserva', 'to' => 'Pago', 'type' => 'se cobra con'];
        }

        if (in_array('Pedido', $entities, true) && in_array('Producto', $entities, true)) {
            $relations[] = ['from' => 'Pedido', 'to' => 'Producto', 'type' => 'contiene'];
        }

        if (in_array('Factura', $entities, true) && in_array('Pago', $entities, true)) {
            $relations[] = ['from' => 'Factura', 'to' => 'Pago', 'type' => 'se liquida con'];
        }

        if (in_array('Producto', $entities, true) && in_array('Existencia', $entities, true)) {
            $relations[] = ['from' => 'Producto', 'to' => 'Existencia', 'type' => 'controla'];
        }

        return $relations;
    }

    /**
     * @param  array<int, string>  $capabilityIds
     * @return array<int, string>
     */
    private function processesFor(array $capabilityIds): array
    {
        $processes = array_map(
            static fn (string $id): ?string => self::processFor($id),
            $capabilityIds,
        );

        return array_values(array_unique(array_filter($processes)));
    }

    public static function processFor(string $capabilityId): ?string
    {
        return match ($capabilityId) {
            'reservations', 'hotel-reservations', 'restaurant-reservations', 'winery-reservations' => 'Recibir y gestionar reservas',
            'invoices' => 'Emitir facturas',
            'payments' => 'Registrar cobros',
            'orders', 'product-orders' => 'Gestionar pedidos',
            'messaging', 'whatsapp' => 'Comunicarse con clientes',
            'synchronization' => 'Sincronizar datos externos',
            'reports', 'knowledge' => 'Generar informes',
            'shipping' => 'Enviar y hacer seguimiento',
            'documents' => 'Preparar documentos',
            default => null,
        };
    }
}
