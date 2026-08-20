<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

use App\Domain\Nova\Capabilities\Capability;

final readonly class MissionStep
{
    /**
     * @param  array<int, string>  $tasks
     */
    public function __construct(
        public string $capabilityId,
        public string $icon,
        public string $title,
        public string $description,
        public string $agent,
        public ?string $connector,
        public ?string $provider,
        public string $duration,
        public string $tool,
        public string $artifact,
        public array $tasks = [],
    ) {}

    public static function fromCapability(Capability $capability): self
    {
        return new self(
            capabilityId: $capability->id,
            icon: $capability->icon,
            title: $capability->name,
            description: $capability->description,
            agent: $capability->requiredAgents[0] ?? 'Planificador',
            connector: $capability->requiredConnectors[0] ?? null,
            provider: $capability->requiredProviders[0] ?? null,
            duration: $capability->estimatedDuration.' s',
            tool: $capability->tool,
            artifact: $capability->requiredArtifacts[0] ?? 'result.json',
            tasks: self::tasksFor($capability),
        );
    }

    /**
     * @return array<int, string>
     */
    private static function tasksFor(Capability $capability): array
    {
        return match ($capability->id) {
            'knowledge' => ['Buscando la información necesaria', 'Preparando el informe'],
            'reporting' => ['Recopilando datos del negocio', 'Generando el informe final'],
            'synchronization' => ['Coordinando el trabajo', 'Verificando conectores', 'Sincronizando estado'],
            'messaging' => ['Preparando la confirmación', 'Seleccionando el canal', 'Enviando el mensaje'],
            'crm' => ['Buscando al cliente', 'Actualizando el perfil'],
            'calendar' => ['Revisando el calendario', 'Bloqueando slots'],
            'availability' => ['Comprobando disponibilidad', 'Reservando inventario'],
            'hotel-reservations' => ['Preparando la reserva de habitación', 'Confirmando datos del huésped', 'Emitiendo documento'],
            'restaurant-reservations' => ['Preparando la reserva de mesa', 'Confirmando comensales', 'Notificando al restaurante'],
            'winery-reservations' => ['Preparando la visita a la bodega', 'Confirmando asistentes', 'Emitiendo entrada'],
            'payments' => ['Preparando el pago', 'Verificando el método', 'Procesando el cargo'],
            'invoices' => ['Preparando las facturas', 'Calculando totales', 'Generando PDF'],
            'rooms' => ['Gestionando el inventario de habitaciones', 'Actualizando disponibilidad'],
            'tables' => ['Gestionando el inventario de mesas', 'Actualizando disponibilidad'],
            'wine-tours' => ['Gestionando las visitas a bodega', 'Actualizando el catálogo de degustaciones'],
            'planning' => ['Analizando el objetivo', 'Ordenando capacidades', 'Preparando plan de ejecución'],
            default => self::fallbackTasks($capability),
        };
    }

    /**
     * @return array<int, string>
     */
    private static function fallbackTasks(Capability $capability): array
    {
        return [
            'Preparando '.mb_strtolower($capability->name),
            'Ejecutando '.mb_strtolower($capability->name),
            'Verificando '.mb_strtolower($capability->name),
        ];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'capability_id' => $this->capabilityId,
            'icon' => $this->icon,
            'title' => $this->title,
            'description' => $this->description,
            'agent' => $this->agent,
            'connector' => $this->connector,
            'provider' => $this->provider,
            'duration' => $this->duration,
            'tool' => $this->tool,
            'artifact' => $this->artifact,
            'tasks' => $this->tasks,
            'status' => 'waiting',
            'progress' => 0,
        ];
    }
}
