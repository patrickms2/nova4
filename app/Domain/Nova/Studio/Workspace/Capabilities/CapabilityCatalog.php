<?php

declare(strict_types=1);

namespace App\Domain\Nova\Studio\Workspace\Capabilities;

use App\Domain\Nova\Businesses\ProfessionalCatalog;

final readonly class CapabilityCatalog
{
    public function __construct(private readonly ProfessionalCatalog $professionals) {}

    /** @return array<int, array<string, string>> */
    public function activities(): array
    {
        return [
            ['id' => 'sales', 'icon' => '🛍', 'name' => 'Venta', 'description' => 'Vendes productos o servicios.'],
            ['id' => 'services', 'icon' => '🛎', 'name' => 'Servicios', 'description' => 'Prestas servicios, citas, reservas o experiencias.'],
            ['id' => 'management', 'icon' => '🏢', 'name' => 'Gestión', 'description' => 'Procesos internos, documentos y organización.'],
            ['id' => 'logistics', 'icon' => '🚚', 'name' => 'Logística', 'description' => 'Envíos, entregas o desplazamientos.'],
            ['id' => 'manufacturing', 'icon' => '🏭', 'name' => 'Fabricación', 'description' => 'Producción de bienes o artesanía.'],
            ['id' => 'customer-service', 'icon' => '🎧', 'name' => 'Atención al cliente', 'description' => 'Comunicación y soporte a clientes.'],
            ['id' => 'rentals', 'icon' => '🏠', 'name' => 'Alquileres', 'description' => 'Gestión de alquileres o propiedades.'],
            ['id' => 'reservations', 'icon' => '📅', 'name' => 'Reservas', 'description' => 'Reservas, ocupación y planificación.'],
            ['id' => 'invoicing', 'icon' => '🧾', 'name' => 'Facturación', 'description' => 'Emisión y control de facturas.'],
            ['id' => 'administration', 'icon' => '📂', 'name' => 'Administración', 'description' => 'Gastos, proveedores y contabilidad.'],
        ];
    }

    /** @return array<int, array<string, string>> */
    public function businessTypes(): array
    {
        return [
            ['id' => 'hotel', 'icon' => '🏨', 'name' => 'Hotel'],
            ['id' => 'vacation-rental', 'icon' => '�', 'name' => 'Vivienda Vacacional'],
            ['id' => 'restaurant', 'icon' => '🍽', 'name' => 'Restaurante'],
            ['id' => 'tours', 'icon' => '🎟', 'name' => 'Tours'],
            ['id' => 'winery', 'icon' => '�', 'name' => 'Bodega'],
            ['id' => 'taxi', 'icon' => '🚖', 'name' => 'Taxi'],
            ['id' => 'store', 'icon' => '🛍', 'name' => 'Tienda'],
            ['id' => 'clinic', 'icon' => '🏥', 'name' => 'Clínica'],
            ['id' => 'real-estate', 'icon' => '🏘', 'name' => 'Inmobiliaria'],
            ['id' => 'professional', 'icon' => '💼', 'name' => 'Profesionales'],
            ['id' => 'other', 'icon' => '✦', 'name' => 'Otro'],
        ];
    }

    /**
     * Business objectives map to candidate capabilities.
     *
     * @return array<string, array{icon: string, name: string, capabilities: array<int, string>}>
     */
    public function objectives(): array
    {
        return [
            'sell-online' => ['icon' => '🛒', 'name' => 'Quiero vender online', 'capabilities' => ['products', 'payments']],
            'accept-reservations' => ['icon' => '📅', 'name' => 'Quiero aceptar reservas', 'capabilities' => ['calendar', 'availability', 'crm']],
            'control-expenses' => ['icon' => '💸', 'name' => 'Quiero controlar gastos', 'capabilities' => ['knowledge', 'reporting']],
            'issue-invoices' => ['icon' => '🧾', 'name' => 'Quiero emitir facturas', 'capabilities' => ['invoices']],
            'automate-whatsapp' => ['icon' => '💬', 'name' => 'Quiero automatizar WhatsApp', 'capabilities' => ['messaging']],
            'know-profit' => ['icon' => '📈', 'name' => 'Quiero saber cuánto gano', 'capabilities' => ['knowledge', 'reporting']],
            'manage-documents' => ['icon' => '📄', 'name' => 'Quiero gestionar documentos', 'capabilities' => ['knowledge']],
            'automate-tasks' => ['icon' => '⚡', 'name' => 'Quiero automatizar tareas', 'capabilities' => ['synchronization']],
            'connect-ai' => ['icon' => '✨', 'name' => 'Quiero conectar IA', 'capabilities' => ['knowledge']],
            'sync-orders' => ['icon' => '🔄', 'name' => 'Quiero sincronizar pedidos', 'capabilities' => ['synchronization']],
        ];
    }

    /**
     * Resolve capability ids to human-readable details.
     *
     * @param  array<int, string>  $capabilityIds
     * @return array<int, array<string, mixed>>
     */
    public function forIds(array $capabilityIds): array
    {
        $areas = $this->businessAreas();
        $improvements = $this->improvements();

        return array_values(array_filter(array_map(
            static function (string $id) use ($areas, $improvements): ?array {
                if (isset($areas[$id])) {
                    return [
                        'id' => $id,
                        'name' => $areas[$id]['name'],
                        'icon' => $areas[$id]['icon'],
                        'description' => 'Capacidad operativa del negocio.',
                        'active' => true,
                        'tools' => $areas[$id]['tools'] ?? [],
                    ];
                }

                if (isset($improvements[$id])) {
                    return [
                        'id' => $id,
                        'name' => $improvements[$id]['name'],
                        'icon' => $improvements[$id]['icon'],
                        'description' => 'Mejora activada sobre una capacidad existente.',
                        'active' => true,
                        'tools' => $improvements[$id]['tools'] ?? [],
                    ];
                }

                return null;
            },
            array_values(array_unique($capabilityIds)),
        )));
    }

    /**
     * Resolve objective ids to capability ids.
     *
     * @param  array<int, string>  $ids
     * @return array<int, string>
     */
    public function capabilitiesForObjectives(array $ids): array
    {
        $objectives = $this->objectives();

        return array_values(array_unique(array_merge(
            ...array_map(
                static fn (string $id): array => $objectives[$id]['capabilities'] ?? [],
                $ids,
            )
        )));
    }

    /**
     * Map a business type to an existing blueprint id that the capability registry supports.
     */
    public function businessBlueprintId(string $businessType): string
    {
        return match ($businessType) {
            'vacation-rental' => 'hotel',
            'clinic', 'real-estate' => 'professional',
            'other' => 'winery',
            default => $businessType,
        };
    }

    /** @return array<string, array{icon: string, name: string, tools: array<int, string>}> */
    public function businessAreas(): array
    {
        return [
            'home' => ['icon' => '⌂', 'name' => 'Inicio', 'tools' => []],
            'products' => ['icon' => '🍷', 'name' => 'Productos', 'tools' => ['Crear producto', 'Actualizar catálogo']],
            'reservations' => ['icon' => '📅', 'name' => 'Reservas', 'tools' => ['Crear reserva', 'Reprogramar', 'Cancelar']],
            'customers' => ['icon' => '👥', 'name' => 'Clientes', 'tools' => ['Añadir cliente', 'Actualizar información']],
            'guests' => ['icon' => '👥', 'name' => 'Huéspedes', 'tools' => ['Registrar huésped', 'Preparar llegada']],
            'rooms' => ['icon' => '🛏', 'name' => 'Habitaciones', 'tools' => ['Preparar habitación', 'Actualizar disponibilidad']],
            'payments' => ['icon' => '💳', 'name' => 'Pagos', 'tools' => ['Registrar cobro', 'Preparar devolución']],
            'invoices' => ['icon' => '🧾', 'name' => 'Facturación', 'tools' => ['Crear factura', 'Enviar factura']],
            'orders' => ['icon' => '📦', 'name' => 'Pedidos', 'tools' => ['Crear pedido', 'Preparar cobro', 'Registrar pago']],
            'reports' => ['icon' => '▥', 'name' => 'Informes', 'tools' => ['Preparar informe']],
            'nova' => ['icon' => '✦', 'name' => 'NOVA', 'tools' => ['Dirigir un nuevo trabajo']],
            ...$this->professionals->areas(),
        ];
    }

    /** @return array<string, array{icon: string, name: string, description: string}> */
    public function professionalActivities(): array
    {
        return $this->professionals->activities();
    }

    /** @return array<int, array<string, mixed>> */
    public function professionalVariants(string $activity): array
    {
        return $this->professionals->variantsFor($activity);
    }

    /** @return array<string, array<string, mixed>> */
    public function improvements(): array
    {
        return [
            'whatsapp' => [
                'icon' => '💬',
                'name' => 'WhatsApp',
                'area' => 'reservations',
                'reason' => 'Muchas bodegas confirman sus reservas automáticamente por WhatsApp.',
                'reasons' => [
                    'hotel' => 'Muchos hoteles confirman estancias y llegadas automáticamente por WhatsApp.',
                    'restaurant' => 'Muchos restaurantes confirman sus mesas automáticamente por WhatsApp.',
                    'tours' => 'Muchas experiencias confirman horarios y puntos de encuentro por WhatsApp.',
                    'taxi' => 'Muchos servicios de transporte confirman recogidas automáticamente por WhatsApp.',
                ],
                'question' => '¿Quieres que NOVA también lo haga por ti?',
                'action' => 'Añadir a Reservas',
                'result' => 'Reservas ahora incluye WhatsApp.',
                'tools' => ['Confirmar por WhatsApp'],
                'steps' => ['Preparando conversaciones…', 'Creando mensajes útiles…', 'Organizando la comunicación…', 'Actualizando tu Workspace…'],
            ],
            'product-orders' => [
                'icon' => '📦',
                'name' => 'Pedidos',
                'area' => 'products',
                'unless_areas' => ['orders'],
                'reason' => 'Tus productos ya están organizados. NOVA puede ayudarte a convertirlos en pedidos.',
                'question' => '¿Quieres hacer crecer la gestión de Productos?',
                'action' => 'Añadir a Productos',
                'result' => 'Productos ahora también gestiona Pedidos.',
                'tools' => ['Crear pedido', 'Preparar cobro'],
                'steps' => ['Preparando pedidos…', 'Organizando el proceso de venta…', 'Uniendo productos y cobros…', 'Actualizando tu Workspace…'],
            ],
            'shipping' => [
                'icon' => '🚚',
                'name' => 'Seguimiento de envíos',
                'area' => 'orders',
                'reason' => 'Tus clientes pueden saber en todo momento cómo avanza cada entrega.',
                'question' => '¿Quieres ampliar Pedidos con seguimiento?',
                'action' => 'Añadir a Pedidos',
                'result' => 'Pedidos ahora incluye Seguimiento de envíos.',
                'tools' => ['Marcar como preparando', 'Marcar como enviado', 'Marcar como entregado'],
                'steps' => ['Preparando los estados…', 'Organizando las entregas…', 'Mejorando el seguimiento…', 'Actualizando tu Workspace…'],
            ],
            'documents' => [
                'icon' => '📄',
                'name' => 'Documentos',
                'area' => 'invoices',
                'reason' => 'NOVA puede preparar contratos y facturas automáticamente cuando los necesites.',
                'question' => '¿Quieres ampliar la forma en que trabajas con documentos?',
                'action' => 'Añadir a Facturación',
                'result' => 'Facturación ahora prepara Documentos.',
                'tools' => ['Generar contrato', 'Preparar documento'],
                'steps' => ['Preparando documentos…', 'Organizando tus plantillas…', 'Adaptando la facturación…', 'Actualizando tu Workspace…'],
            ],
            'loyalty' => [
                'icon' => '⭐',
                'name' => 'Fidelización',
                'area' => 'customers',
                'reason' => 'Tus mejores clientes pueden recibir una atención especial automáticamente.',
                'question' => '¿Quieres que NOVA reconozca cuándo vuelven?',
                'action' => 'Añadir a Clientes',
                'result' => 'Clientes ahora incluye Fidelización.',
                'tools' => ['Reconocer clientes frecuentes'],
                'steps' => ['Entendiendo a tus clientes…', 'Preparando reconocimientos…', 'Mejorando la atención…', 'Actualizando tu Workspace…'],
            ],
            'phone-ai' => [
                'icon' => '☎',
                'name' => 'Asistente de llamadas',
                'area' => 'customers',
                'reason' => 'Muchos negocios atienden las preguntas habituales de sus clientes automáticamente.',
                'question' => '¿Quieres que NOVA te ayude con las llamadas?',
                'action' => 'Añadir a Clientes',
                'result' => 'Clientes ahora incluye un Asistente de llamadas.',
                'tools' => ['Asistente de llamadas'],
                'steps' => ['Preparando la atención…', 'Aprendiendo las preguntas habituales…', 'Organizando las llamadas…', 'Actualizando tu Workspace…'],
            ],
            'crm' => [
                'icon' => '📈',
                'name' => 'Seguimiento inteligente',
                'area' => 'customers',
                'reason' => 'NOVA puede ayudarte a recordar cada oportunidad y cada próximo paso.',
                'question' => '¿Quieres mejorar el seguimiento de Clientes?',
                'action' => 'Añadir a Clientes',
                'result' => 'Clientes ahora incluye Seguimiento inteligente.',
                'tools' => ['Preparar próximo paso', 'Recordar oportunidad'],
                'steps' => ['Entendiendo tus relaciones…', 'Preparando próximos pasos…', 'Organizando el seguimiento…', 'Actualizando tu Workspace…'],
            ],
            'inventory' => [
                'icon' => '▦',
                'name' => 'Existencias',
                'area' => 'products',
                'reason' => 'NOVA puede avisarte antes de que un producto se agote.',
                'question' => '¿Quieres mejorar el control de Productos?',
                'action' => 'Añadir a Productos',
                'result' => 'Productos ahora incluye Existencias.',
                'tools' => ['Controlar existencias', 'Avisar de producto agotado'],
                'steps' => ['Revisando tus productos…', 'Preparando avisos…', 'Organizando existencias…', 'Actualizando tu Workspace…'],
            ],
            'tickets' => [
                'icon' => '🎫',
                'name' => 'Entradas',
                'area' => 'reservations',
                'reason' => 'Las experiencias con reserva pueden incluir una entrada lista para cada visitante.',
                'question' => '¿Quieres ampliar Reservas con entradas?',
                'action' => 'Añadir a Reservas',
                'result' => 'Reservas ahora incluye Entradas.',
                'tools' => ['Generar entrada', 'Validar llegada'],
                'steps' => ['Preparando entradas…', 'Organizando visitantes…', 'Mejorando las llegadas…', 'Actualizando tu Workspace…'],
            ],
        ];
    }

    /** @return array<string, array{icon: string, name: string, tools: array<int, string>}> */
    public function capabilities(): array
    {
        return $this->businessAreas();
    }

    /** @return array<int, string> */
    /** @param string|array<int, string>|null $professionalVariants */
    public function defaultsFor(string $businessType, string|array|null $professionalVariants = null): array
    {
        if ($businessType === 'professional') {
            $variantIds = is_array($professionalVariants)
                ? $professionalVariants
                : array_filter([$professionalVariants]);
            $areas = array_merge(...array_map(
                fn (string $id): array => $this->professionals->variant($id)['areas'] ?? [],
                $variantIds,
            ));

            return $areas === []
                ? ['home', 'reports', 'nova']
                : ['home', ...array_values(array_unique($areas)), 'reports', 'nova'];
        }

        return match ($businessType) {
            'hotel' => ['home', 'rooms', 'reservations', 'guests', 'payments', 'reports', 'nova'],
            'restaurant' => ['home', 'reservations', 'customers', 'payments', 'reports', 'nova'],
            'taxi' => ['home', 'reservations', 'customers', 'payments', 'reports', 'nova'],
            'store' => ['home', 'products', 'orders', 'customers', 'payments', 'invoices', 'reports', 'nova'],
            default => ['home', 'products', 'reservations', 'customers', 'payments', 'invoices', 'reports', 'nova'],
        };
    }

    /** @return array<int, string> */
    public function optionalIds(): array
    {
        return array_keys($this->improvements());
    }

    /** @return array{icon: string, name: string} */
    public function business(string $id): array
    {
        foreach ($this->businessTypes() as $business) {
            if ($business['id'] === $id) {
                return ['icon' => $business['icon'], 'name' => $business['name']];
            }
        }

        return ['icon' => '🏢', 'name' => 'Mi negocio'];
    }

    /** @return array<string, mixed>|null */
    public function professionalVariant(?string $id): ?array
    {
        return $this->professionals->variant($id);
    }
}
