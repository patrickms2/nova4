<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\Representations;

final readonly class RepresentationCatalog
{
    /**
     * @param  array<string, mixed>  $workspace
     * @return array<string, mixed>
     */
    public function fromWorkspace(array $workspace): array
    {
        $navigation = $workspace['navigation'] ?? [];
        $capabilities = $workspace['capability_ids'] ?? [];
        $capabilityDetails = $workspace['capabilities'] ?? [];
        $businessName = $workspace['business_name'] ?? 'Mi negocio';

        return [
            'admin' => $this->adminRepresentation($navigation),
            'web' => $this->webRepresentation($capabilities, $businessName),
            'copilot' => $this->copilotRepresentation($capabilities, $businessName),
            'mcp' => $this->mcpRepresentation($capabilities),
            'ia' => $this->iaRepresentation($capabilityDetails, $businessName),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $navigation
     * @return array<string, mixed>
     */
    private function adminRepresentation(array $navigation): array
    {
        return [
            'title' => 'Panel de gestión',
            'description' => 'Administra tu negocio desde el Workspace.',
            'sidebar' => array_values(array_map(
                static fn (array $area): array => [
                    'id' => $area['id'],
                    'icon' => $area['icon'] ?? '✦',
                    'name' => $area['name'] ?? $area['id'],
                    'tools' => $area['tools'] ?? [],
                ],
                $navigation,
            )),
            'dashboards' => ['Resumen', 'Calendario', 'Kanban'],
            'cruds' => array_column($navigation, 'name'),
        ];
    }

    /**
     * @param  array<int, string>  $capabilities
     * @param  string  $businessName
     * @return array<string, mixed>
     */
    private function webRepresentation(array $capabilities, string $businessName): array
    {
        $has = fn (array $ids): bool => count(array_intersect($capabilities, $ids)) > 0;

        $sections = [
            ['id' => 'landing', 'name' => 'Inicio', 'description' => 'Página principal de '.$businessName],
        ];

        if ($has(['reservations', 'hotel-reservations', 'restaurant-reservations', 'winery-reservations', 'calendar', 'availability'])) {
            $sections[] = ['id' => 'reservations', 'name' => 'Reservas', 'description' => 'Motor de reservas online'];
        }

        if ($has(['products', 'store-catalog', 'winery-catalog', 'restaurant-menu', 'orders', 'product-orders'])) {
            $sections[] = ['id' => 'services', 'name' => 'Servicios', 'description' => 'Catálogo de productos y servicios'];
        }

        if ($has(['products', 'orders', 'product-orders', 'payments'])) {
            $sections[] = ['id' => 'shop', 'name' => 'Tienda', 'description' => 'Venta directa online'];
        }

        $sections[] = ['id' => 'contact', 'name' => 'Contacto', 'description' => 'Formulario y datos de contacto'];

        return [
            'title' => 'Web pública',
            'description' => 'Frontend generado automáticamente desde las capacidades.',
            'sections' => $sections,
        ];
    }

    /**
     * @param  array<int, string>  $capabilities
     * @param  string  $businessName
     * @return array<string, mixed>
     */
    private function copilotRepresentation(array $capabilities, string $businessName): array
    {
        $intents = [];
        foreach ($capabilities as $id) {
            $intent = $this->intentFor($id);
            if ($intent !== null) {
                $intents[$id] = $intent;
            }
        }

        return [
            'title' => 'Copilot',
            'description' => 'Conversación inteligente con el mismo núcleo del Workspace.',
            'welcome' => "Hola, soy NOVA. ¿Qué quieres conseguir hoy en {$businessName}?",
            'channels' => [
                ['id' => 'whatsapp', 'name' => 'WhatsApp', 'icon' => '💬'],
                ['id' => 'widget', 'name' => 'Widget Web', 'icon' => '🧩'],
                ['id' => 'voice', 'name' => 'Voice', 'icon' => '🎙'],
                ['id' => 'telegram', 'name' => 'Telegram', 'icon' => '✈'],
                ['id' => 'slack', 'name' => 'Slack', 'icon' => '💼'],
                ['id' => 'teams', 'name' => 'Teams', 'icon' => '🟦'],
            ],
            'intents' => array_values($intents),
        ];
    }

    /**
     * @param  array<int, string>  $capabilities
     * @return array<string, mixed>
     */
    private function mcpRepresentation(array $capabilities): array
    {
        $tools = [];
        foreach ($capabilities as $id) {
            $tool = $this->mcpToolFor($id);
            if ($tool !== null) {
                $tools[$tool['id']] = $tool;
            }
        }

        return [
            'title' => 'MCP',
            'description' => 'Capacidades publicadas como herramientas abiertas.',
            'status' => 'Publicando capacidades…',
            'tools' => array_values($tools),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $capabilityDetails
     * @param  string  $businessName
     * @return array<string, mixed>
     */
    private function iaRepresentation(array $capabilityDetails, string $businessName): array
    {
        return [
            'title' => 'IA',
            'description' => 'Configuración del Copilot para este Workspace.',
            'prompt' => "Eres NOVA, el copiloto operativo de {$businessName}. Entiendes el negocio, propones acciones y ejecutas tareas a través de las capacidades activas.",
            'suggestions' => [
                'Organizar las reservas de esta semana',
                'Preparar las facturas de este mes',
                'Sincronizar el catálogo de productos',
            ],
            'capabilities' => array_values(array_map(
                static fn (array $cap): array => [
                    'id' => $cap['id'],
                    'name' => $cap['name'],
                    'icon' => $cap['icon'] ?? '✦',
                ],
                $capabilityDetails,
            )),
        ];
    }

    /** @return array<string, string>|null */
    private function intentFor(string $capabilityId): ?array
    {
        return match ($capabilityId) {
            'hotel-reservations', 'restaurant-reservations', 'winery-reservations', 'reservations', 'calendar', 'availability' => [
                'id' => 'create-reservation',
                'label' => 'Crear una reserva',
                'example' => 'Quiero hacer una reserva para mañana',
            ],
            'invoices' => [
                'id' => 'send-invoice',
                'label' => 'Enviar una factura',
                'example' => 'Prepara y envía la factura del cliente',
            ],
            'payments' => [
                'id' => 'register-payment',
                'label' => 'Registrar un cobro',
                'example' => 'He recibido un pago',
            ],
            'reporting', 'knowledge' => [
                'id' => 'ask-report',
                'label' => 'Consultar un informe',
                'example' => '¿Cuánto he facturado este mes?',
            ],
            'messaging', 'whatsapp' => [
                'id' => 'send-message',
                'label' => 'Enviar un mensaje',
                'example' => 'Confirma la reserva por WhatsApp',
            ],
            'synchronization' => [
                'id' => 'sync-data',
                'label' => 'Sincronizar datos',
                'example' => 'Sincroniza el calendario con Booking',
            ],
            default => null,
        };
    }

    /** @return array<string, string>|null */
    private function mcpToolFor(string $capabilityId): ?array
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
}
