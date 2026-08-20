<?php

declare(strict_types=1);

namespace App\Domain\Nova\Businesses;

final class ProfessionalCatalog
{
    /** @return array<string, array{icon: string, name: string, description: string}> */
    public function activities(): array
    {
        return [
            'sales' => ['icon' => '🛍', 'name' => 'Venta', 'description' => 'Catálogos preparados para vender.'],
            'services' => ['icon' => '🛎', 'name' => 'Servicios', 'description' => 'Organización de clientes, tiempo y trabajo.'],
        ];
    }

    /** @return array<string, array{activity: string, icon: string, name: string, description: string, areas: array<int, string>}> */
    public function variants(): array
    {
        return [
            'professional-restaurant-sales' => [
                'activity' => 'sales',
                'icon' => '🍽',
                'name' => 'Menú de restaurante',
                'description' => 'Organiza platos y la carta del restaurante.',
                'areas' => ['restaurant-menu'],
            ],
            'professional-store-sales' => [
                'activity' => 'sales',
                'icon' => '🛍',
                'name' => 'Catálogo de tienda',
                'description' => 'Organiza artículos y el catálogo de venta.',
                'areas' => ['store-catalog'],
            ],
            'professional-winery-sales' => [
                'activity' => 'sales',
                'icon' => '🍷',
                'name' => 'Catálogo de bodega',
                'description' => 'Organiza vinos y referencias de la bodega.',
                'areas' => ['winery-catalog'],
            ],
            'professional-tours' => [
                'activity' => 'services',
                'icon' => '🗺',
                'name' => 'Gestión de tours',
                'description' => 'Para bodegas, excursiones y servicios de transporte.',
                'areas' => ['tour-types', 'tours'],
            ],
            'professional-restaurant-reservations' => [
                'activity' => 'services',
                'icon' => '📅',
                'name' => 'Reservas de restaurante',
                'description' => 'Gestiona personas, salas, mesas y reservas por día.',
                'areas' => ['restaurants', 'dining-rooms', 'tables', 'customers', 'reservations'],
            ],
            'professional-appointments' => [
                'activity' => 'services',
                'icon' => '🕐',
                'name' => 'Gestión de citas',
                'description' => 'Para despachos, recursos humanos y equipos profesionales.',
                'areas' => ['company', 'departments', 'employees', 'slots', 'appointments'],
            ],
            'professional-documents' => [
                'activity' => 'services',
                'icon' => '📄',
                'name' => 'Gestión de documentos',
                'description' => 'Organiza documentos legales, económicos y financieros.',
                'areas' => ['document-types', 'templates', 'professional-documents'],
            ],
            'professional-tickets' => [
                'activity' => 'services',
                'icon' => '🎫',
                'name' => 'Gestión de tickets',
                'description' => 'Coordina solicitudes entre departamentos y empleados.',
                'areas' => ['ticket-types', 'departments', 'employees', 'support-tickets'],
            ],
        ];
    }

    /** @return array<string, array{icon: string, name: string, tools: array<int, string>}> */
    public function areas(): array
    {
        return [
            'restaurant-menu' => ['icon' => '🍽', 'name' => 'Platos', 'tools' => ['Crear plato', 'Actualizar plato', 'Organizar carta']],
            'store-catalog' => ['icon' => '🛍', 'name' => 'Artículos', 'tools' => ['Crear artículo', 'Actualizar artículo', 'Organizar catálogo']],
            'winery-catalog' => ['icon' => '🍷', 'name' => 'Vinos', 'tools' => ['Crear vino', 'Actualizar vino', 'Organizar catálogo']],
            'tour-types' => ['icon' => '🧭', 'name' => 'Tipos', 'tools' => ['Crear tipo de tour', 'Actualizar tipo de tour']],
            'tours' => ['icon' => '🗺', 'name' => 'Tours', 'tools' => ['Crear tour', 'Actualizar tour', 'Organizar salida']],
            'restaurants' => ['icon' => '🍽', 'name' => 'Restaurantes', 'tools' => ['Añadir restaurante', 'Actualizar restaurante']],
            'dining-rooms' => ['icon' => '▦', 'name' => 'Salas', 'tools' => ['Crear sala', 'Actualizar sala']],
            'tables' => ['icon' => '◫', 'name' => 'Mesas', 'tools' => ['Crear mesa', 'Actualizar disponibilidad']],
            'customers' => ['icon' => '👥', 'name' => 'Clientes', 'tools' => ['Añadir cliente', 'Actualizar información']],
            'reservations' => ['icon' => '📅', 'name' => 'Reservas', 'tools' => ['Crear reserva', 'Reprogramar', 'Cancelar']],
            'company' => ['icon' => '🏢', 'name' => 'Empresa', 'tools' => ['Actualizar empresa', 'Organizar estructura']],
            'departments' => ['icon' => '▤', 'name' => 'Departamentos', 'tools' => ['Crear departamento', 'Asignar responsable']],
            'employees' => ['icon' => '👤', 'name' => 'Empleados', 'tools' => ['Añadir empleado', 'Asignar departamento']],
            'slots' => ['icon' => '🕐', 'name' => 'Horarios', 'tools' => ['Crear horario', 'Bloquear horario']],
            'appointments' => ['icon' => '📆', 'name' => 'Citas', 'tools' => ['Crear cita', 'Reprogramar cita', 'Cancelar cita']],
            'document-types' => ['icon' => '🗂', 'name' => 'Tipos', 'tools' => ['Crear tipo de documento', 'Actualizar tipo']],
            'templates' => ['icon' => '📑', 'name' => 'Plantillas', 'tools' => ['Crear plantilla', 'Actualizar plantilla']],
            'professional-documents' => ['icon' => '📄', 'name' => 'Documentos', 'tools' => ['Crear documento', 'Generar desde plantilla', 'Archivar documento']],
            'ticket-types' => ['icon' => '🏷', 'name' => 'Tipos', 'tools' => ['Crear tipo de ticket', 'Definir prioridad']],
            'support-tickets' => ['icon' => '🎫', 'name' => 'Tickets', 'tools' => ['Crear ticket', 'Asignar ticket', 'Cerrar ticket']],
        ];
    }

    /** @return array<string, mixed>|null */
    public function variant(?string $id): ?array
    {
        if ($id === null || ! isset($this->variants()[$id])) {
            return null;
        }

        return ['id' => $id, ...$this->variants()[$id]];
    }

    public function capabilityId(string $areaId): string
    {
        return str_starts_with($areaId, 'professional-')
            ? $areaId
            : 'professional-'.$areaId;
    }

    /** @return array<int, array<string, mixed>> */
    public function variantsFor(string $activity): array
    {
        return array_values(array_map(
            static fn (string $id, array $variant): array => ['id' => $id, ...$variant],
            array_keys(array_filter(
                $this->variants(),
                static fn (array $variant): bool => $variant['activity'] === $activity,
            )),
            array_filter(
                $this->variants(),
                static fn (array $variant): bool => $variant['activity'] === $activity,
            ),
        ));
    }
}
