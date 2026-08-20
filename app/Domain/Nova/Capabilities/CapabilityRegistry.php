<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

use App\Domain\Nova\Businesses\ProfessionalCatalog;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CapabilityRegistry
{
    /** @var array<string, Capability> */
    private array $capabilities = [];

    public function __construct(private readonly ProfessionalCatalog $professionals)
    {
        foreach ($this->defaults() as $capability) {
            $this->register($capability);
        }

        foreach ($this->professionalCapabilities() as $capability) {
            $this->register($capability);
        }
    }

    public function register(Capability $capability): void
    {
        $this->capabilities[$capability->id] = $capability;
    }

    public function get(string $id): Capability
    {
        return $this->capabilities[$id]
            ?? throw new InvalidArgumentException("La capacidad [{$id}] no está registrada.");
    }

    /** @return array<int, Capability> */
    public function all(): array
    {
        return array_values($this->capabilities);
    }

    /** @return array<int, Capability> */
    private function defaults(): array
    {
        $shared = ['Hotel', 'Restaurante', 'Bodega'];

        return [
            new Capability('planning', 'Planificación', 'Transforma las capacidades resueltas en un orden de ejecución.', 'planning', ['Planificador'], [], [], ['plan-de-mision.json'], [], 100, 3, $shared, 'active', [], 'sparkles', 'plan.runtime'),
            new Capability('synchronization', 'Sincronización', 'Coordina el estado entre los endpoints del motor.', 'synchronization', ['Sincronización'], ['Sincronización del motor'], ['Motor NOVA'], ['resultado-sincronizacion.json'], ['planning'], 80, 4, $shared, 'active', ['sync', 'sincronizar', 'connect', 'conectar', 'integrate', 'integrar'], 'arrows', 'runtime.synchronise'),
            new Capability('messaging', 'Mensajería', 'Prepara la comunicación con clientes en los canales disponibles.', 'messaging', ['Mensajería'], ['Conector de WhatsApp'], ['Meta'], ['vista-previa-whatsapp.png'], ['synchronization'], 70, 5, $shared, 'active', ['whatsapp', 'message', 'mensaje', 'notify', 'notificar'], 'message', 'messaging.prepare'),
            new Capability('payments', 'Pagos', 'Prepara la ejecución de pagos con aprobación humana.', 'payments', ['Pagos'], ['Conector de pagos'], ['Stripe'], ['informe-pago.pdf'], ['planning'], 70, 6, $shared, 'active', ['payment', 'pago', 'pay', 'pagar', 'charge', 'cobrar'], 'credit-card', 'payments.prepare', true),
            new Capability('invoices', 'Facturas', 'Genera facturas desde el contexto empresarial resuelto.', 'invoices', ['Informes'], ['Conector de pagos'], ['Stripe'], ['factura.pdf'], ['payments'], 75, 6, $shared, 'active', ['invoice', 'factura'], 'document', 'invoice.generate', true),
            new Capability('crm', 'CRM', 'Coordina el contexto de clientes durante la ejecución.', 'crm', ['CRM'], ['Conector CRM'], ['NOVA CRM'], ['clientes.json'], ['planning'], 45, 4, $shared, 'active', ['customer', 'cliente', 'client', 'crm'], 'users', 'crm.resolve'),
            new Capability('knowledge', 'Conocimiento', 'Resuelve el conocimiento del espacio de trabajo que necesitan los agentes.', 'knowledge', ['Conocimiento'], ['Conector de conocimiento'], ['Conocimiento NOVA'], ['conocimiento.md'], ['planning'], 40, 4, $shared, 'active', ['knowledge', 'conocimiento', 'search', 'buscar', 'information', 'información'], 'book', 'knowledge.search'),
            new Capability('reporting', 'Informes', 'Produce un informe duradero de la ejecución de la misión.', 'reporting', ['Informes'], [], [], ['informe.pdf'], ['planning'], 10, 4, $shared, 'active', ['report', 'informe', 'summary', 'resumen'], 'document', 'report.generate'),
            new Capability('calendar', 'Calendario', 'Resuelve las restricciones del calendario empresarial.', 'calendar', ['Calendario'], ['Conector de calendario'], ['Calendario NOVA'], ['calendario.json'], ['planning'], 65, 3, $shared, 'active', ['calendar', 'calendario', 'date', 'fecha'], 'calendar', 'calendar.resolve'),
            new Capability('availability', 'Disponibilidad', 'Comprueba la disponibilidad del inventario reservable.', 'availability', ['Disponibilidad'], ['Conector de disponibilidad'], ['Disponibilidad NOVA'], ['disponibilidad.json'], ['calendar'], 70, 4, $shared, 'active', ['availability', 'disponibilidad', 'available', 'disponible'], 'clock', 'availability.check'),
            new Capability('hotel-reservations', 'Reserva de habitación', 'Crea una reserva de habitación para una estancia hotelera.', 'reservations', ['Reservas'], ['Conector de reservas de hotel'], ['Motor hotelero'], ['reserva-habitacion.pdf'], ['availability', 'crm'], 90, 6, ['Hotel'], 'active', ['reservation', 'reserva', 'booking', 'room', 'habitación'], 'building', 'hotel.reserve'),
            new Capability('restaurant-reservations', 'Reserva de mesa', 'Crea una reserva de mesa en un restaurante.', 'reservations', ['Reservas'], ['Conector de reservas de mesa'], ['Motor de restaurante'], ['reserva-mesa.pdf'], ['availability', 'crm'], 90, 5, ['Restaurante'], 'active', ['reservation', 'reserva', 'booking', 'table', 'mesa'], 'table', 'restaurant.reserve'),
            new Capability('winery-reservations', 'Reserva de visita a bodega', 'Crea una reserva para una visita guiada y degustación.', 'reservations', ['Reservas'], ['Conector de reservas de visitas'], ['Motor de bodega'], ['reserva-visita-bodega.pdf'], ['availability', 'crm'], 90, 5, ['Bodega'], 'active', ['reservation', 'reserva', 'booking', 'tour', 'visita', 'wine', 'vino'], 'map', 'winery.reserve'),
            new Capability('rooms', 'Habitaciones', 'Gestiona el contexto del inventario de habitaciones.', 'inventory', ['Inventario'], ['Conector de inventario hotelero'], ['Motor hotelero'], ['habitaciones.json'], ['planning'], 50, 4, ['Hotel'], 'active', ['room', 'habitación'], 'building', 'rooms.resolve'),
            new Capability('tables', 'Mesas', 'Gestiona el contexto del inventario de mesas.', 'inventory', ['Inventario'], ['Conector de inventario de mesas'], ['Motor de restaurante'], ['mesas.json'], ['planning'], 50, 4, ['Restaurante'], 'active', ['table', 'mesa'], 'table', 'tables.resolve'),
            new Capability('wine-tours', 'Visitas a bodega', 'Gestiona el contexto de visitas y degustaciones.', 'inventory', ['Conocimiento'], ['Conector del catálogo de visitas'], ['Motor de bodega'], ['visitas-bodega.json'], ['planning'], 50, 4, ['Bodega'], 'active', ['tour', 'visita', 'tasting', 'degustación', 'wine', 'vino'], 'map', 'tours.resolve'),
        ];
    }

    /** @return array<int, Capability> */
    private function professionalCapabilities(): array
    {
        $variants = $this->professionals->variants();

        return array_values(array_map(
            function (string $areaId, array $area) use ($variants): Capability {
                $businessTypes = array_values(array_map(
                    static fn (array $variant): string => $variant['name'],
                    array_filter(
                        $variants,
                        static fn (array $variant): bool => in_array($areaId, $variant['areas'], true),
                    ),
                ));

                return new Capability(
                    id: $this->professionals->capabilityId($areaId),
                    name: $area['name'],
                    description: 'Gestiona '.$area['name'].' dentro del Workspace profesional.',
                    category: 'professional',
                    requiredAgents: [$area['name']],
                    requiredConnectors: [],
                    requiredProviders: [],
                    requiredArtifacts: [Str::slug($area['name']).'.json'],
                    dependencies: $this->professionalDependencies($areaId),
                    priority: 80,
                    estimatedDuration: 4,
                    supportedBusinessTypes: $businessTypes,
                    status: 'active',
                    intentTerms: $this->professionalIntentTerms($area),
                    icon: $area['icon'],
                    tool: 'professional.'.str_replace('-', '.', $areaId),
                );
            },
            array_keys($this->professionals->areas()),
            $this->professionals->areas(),
        ));
    }

    /** @return array<int, string> */
    private function professionalDependencies(string $areaId): array
    {
        return match ($areaId) {
            'tours' => ['planning', 'professional-tour-types'],
            'reservations' => ['planning', 'professional-tables', 'professional-customers'],
            'appointments' => ['planning', 'professional-slots', 'professional-employees'],
            'professional-documents' => ['planning', 'professional-templates'],
            'support-tickets' => [
                'planning',
                'professional-ticket-types',
                'professional-departments',
                'professional-employees',
            ],
            default => ['planning'],
        };
    }

    /**
     * @param  array{icon: string, name: string, tools: array<int, string>}  $area
     * @return array<int, string>
     */
    private function professionalIntentTerms(array $area): array
    {
        $phrases = [$area['name'], ...$area['tools']];
        $words = array_merge(...array_map(
            static fn (string $phrase): array => preg_split(
                '/[^\p{L}\p{N}]+/u',
                mb_strtolower($phrase),
                flags: PREG_SPLIT_NO_EMPTY,
            ) ?: [],
            $phrases,
        ));

        return array_values(array_unique([
            ...array_map('mb_strtolower', $phrases),
            ...array_filter($words, static fn (string $word): bool => mb_strlen($word) >= 4),
        ]));
    }
}
