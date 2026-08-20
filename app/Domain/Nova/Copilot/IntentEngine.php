<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Enums\Confidence;
use App\Domain\Nova\Copilot\Enums\IntentName;
use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use App\Domain\Nova\Copilot\ValueObjects\Intent;

final readonly class IntentEngine
{
    /**
     * @var array<string, IntentName>
     */
    private array $verbMap;

    /**
     * @var array<string, string>
     */
    private array $capabilityMap;

    public function __construct()
    {
        $this->verbMap = [
            'hola' => IntentName::GREETING,
            'buenos' => IntentName::GREETING,
            'buenas' => IntentName::GREETING,
            'hi' => IntentName::GREETING,
            'hello' => IntentName::GREETING,
            'hey' => IntentName::GREETING,
            'menu' => IntentName::MENU,
            'opciones' => IntentName::MENU,
            'power' => IntentName::MENU,
            'debug' => IntentName::MENU,
            'nvm' => IntentName::OPERATIONAL_MENU,
            'operativo' => IntentName::OPERATIONAL_MENU,
            'inicio' => IntentName::OPERATIONAL_MENU,
            'ver' => IntentName::CONSULT,
            'ver las' => IntentName::CONSULT,
            'mostrar' => IntentName::CONSULT,
            'muéstrame' => IntentName::CONSULT,
            'muestrame' => IntentName::CONSULT,
            'consultar' => IntentName::CONSULT,
            'consulta' => IntentName::CONSULT,
            'crear' => IntentName::CREATE,
            'nuevo' => IntentName::CREATE,
            'nueva' => IntentName::CREATE,
            'registrar' => IntentName::CREATE,
            'añadir' => IntentName::CREATE,
            'agregar' => IntentName::CREATE,
            'comprar' => IntentName::CREATE,
            'comprado' => IntentName::CREATE,
            'pagar' => IntentName::CREATE,
            'pagado' => IntentName::CREATE,
            'gastar' => IntentName::CREATE,
            'gastado' => IntentName::CREATE,
            'hecho' => IntentName::CREATE,
            'editar' => IntentName::EDIT,
            'cambiar' => IntentName::EDIT,
            'actualizar' => IntentName::EDIT,
            'modificar' => IntentName::EDIT,
            'eliminar' => IntentName::DELETE,
            'borrar' => IntentName::DELETE,
            'suprimir' => IntentName::DELETE,
            'enviar' => IntentName::SEND,
            'mandar' => IntentName::SEND,
            'importar' => IntentName::IMPORT,
            'analizar' => IntentName::ANALYZE,
            'detectar' => IntentName::ANALYZE,
            'comparar' => IntentName::COMPARE,
            'configurar' => IntentName::CONFIGURE,
            'responder' => IntentName::REPLY,
        ];

        $this->capabilityMap = [
            'reserva' => 'reservations',
            'reservas' => 'reservations',
            'reservación' => 'reservations',
            'cliente' => 'customers',
            'clientes' => 'customers',
            'factura' => 'invoices',
            'facturas' => 'invoices',
            'gasto' => 'expenses',
            'gastos' => 'expenses',
            'pago' => 'payments',
            'pagos' => 'payments',
            'documento' => 'documents',
            'documentos' => 'documents',
            'inventario' => 'inventory',
            'producto' => 'products',
            'productos' => 'products',
            'incidencia' => 'issues',
            'incidencias' => 'issues',
            'tarea' => 'tasks',
            'tareas' => 'tasks',
            'empleado' => 'employees',
            'empleados' => 'employees',
            'cita' => 'appointments',
            'citas' => 'appointments',
            'menu' => 'restaurant-menu',
            'plato' => 'restaurant-menu',
            'platos' => 'restaurant-menu',
            'tour' => 'tours',
            'tours' => 'tours',
            'vino' => 'winery-catalog',
            'vinos' => 'winery-catalog',
        ];
    }

    public function detect(Input $input, ConversationContext $context): Intent
    {
        $normalized = mb_strtolower(trim($input->text));

        if ($context->pendingOperation !== null) {
            if (in_array($normalized, ['si', 'sí', 'yes', 'confirmar'], true)) {
                return new Intent(IntentName::CONFIRM, Confidence::HIGH, ['pending_operation' => $context->pendingOperation], $context->activeCapability);
            }

            if (in_array($normalized, ['no', 'cancelar', 'nope'], true)) {
                return new Intent(IntentName::CANCEL, Confidence::HIGH, ['pending_operation' => $context->pendingOperation], $context->activeCapability);
            }
        }

        if ($input->isGreeting() || $this->hasAny($normalized, ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hi', 'hello', 'hey'])) {
            return new Intent(IntentName::GREETING, Confidence::HIGH, [], $context->activeCapability);
        }

        if ($input->isPowerMenuTrigger()) {
            return new Intent(IntentName::MENU, Confidence::HIGH, [], $context->activeCapability);
        }

        if ($context->lastMenuType === 'operational') {
            $operationalIntent = $this->resolveOperationalMenuSelection($normalized);

            if ($operationalIntent !== null) {
                return $operationalIntent->withTargetCapability($operationalIntent->targetCapability ?? $context->activeCapability);
            }
        }

        if ($context->activeCapability !== null) {
            $followUp = $this->resolveContextualFollowUp($normalized, $context->activeCapability);

            if ($followUp !== null) {
                return $followUp;
            }
        }

        $verb = $this->detectVerb($normalized);
        $capability = $this->detectCapability($normalized);

        if ($verb === IntentName::MENU || $verb === IntentName::OPERATIONAL_MENU) {
            return new Intent($verb, Confidence::HIGH, [], $context->activeCapability);
        }

        if ($capability === null && $verb === IntentName::CREATE) {
            if (str_contains($normalized, 'factura')) {
                $capability = 'invoices';
            } elseif (str_contains($normalized, 'pago') || str_contains($normalized, 'gasto') || $this->extractAmount($normalized) !== null) {
                $capability = 'expenses';
            }
        }

        $score = $this->calculateScore($normalized, $verb, $capability);
        $confidence = Confidence::fromScore($score);

        if ($verb === null && $capability === null) {
            return new Intent(IntentName::UNKNOWN, Confidence::LOW, [], $context->activeCapability);
        }

        $intentName = $verb ?? IntentName::REPLY;
        $entities = [];

        if ($capability !== null) {
            $entities['capability'] = $capability;
        }

        $entities['extracted_amount'] = $this->extractAmount($normalized);
        $entities['extracted_date'] = $this->extractDate($normalized);

        return new Intent($intentName, $confidence, $entities, $capability ?? $context->activeCapability);
    }

    private function resolveOperationalMenuSelection(string $normalized): ?Intent
    {
        $clean = preg_replace('/[^a-z0-9ñ\s]/u', '', $normalized);
        $clean = trim((string) $clean);

        $numericOption = is_numeric($clean) ? (int) $clean : null;

        $options = [
            0 => [IntentName::CONSULT, 'reservations'],
            1 => [IntentName::CONSULT, 'expenses'],
            2 => [IntentName::CONSULT, 'invoices'],
            3 => [IntentName::CONSULT, 'customers'],
            4 => [IntentName::CONSULT, 'companies'],
            5 => [IntentName::CREATE, 'invoices'],
            6 => [IntentName::CREATE, 'expenses'],
            7 => [IntentName::SEND, 'invoices'],
        ];

        if ($numericOption !== null && isset($options[$numericOption])) {
            return new Intent(
                $options[$numericOption][0],
                Confidence::HIGH,
                ['menu_option' => $numericOption],
                $options[$numericOption][1],
            );
        }

        if (str_contains($normalized, 'crear factura') || str_contains($normalized, 'nueva factura')) {
            return new Intent(IntentName::CREATE, Confidence::HIGH, ['menu_option' => 5], 'invoices');
        }

        if (str_contains($normalized, 'enviar factura') || str_contains($normalized, 'mandar factura')) {
            return new Intent(IntentName::SEND, Confidence::HIGH, ['menu_option' => 7], 'invoices');
        }

        if (str_contains($normalized, 'crear gasto') || str_contains($normalized, 'nuevo gasto')) {
            return new Intent(IntentName::CREATE, Confidence::HIGH, ['menu_option' => 6], 'expenses');
        }

        if (str_contains($normalized, 'resumen') || str_contains($normalized, 'reservas')) {
            return new Intent(IntentName::CONSULT, Confidence::HIGH, ['menu_option' => 0], 'reservations');
        }

        if (str_contains($normalized, 'gastos')) {
            return new Intent(IntentName::CONSULT, Confidence::HIGH, ['menu_option' => 1], 'expenses');
        }

        if (str_contains($normalized, 'facturas')) {
            return new Intent(IntentName::CONSULT, Confidence::HIGH, ['menu_option' => 2], 'invoices');
        }

        if (str_contains($normalized, 'clientes')) {
            return new Intent(IntentName::CONSULT, Confidence::HIGH, ['menu_option' => 3], 'customers');
        }

        if (str_contains($normalized, 'empresas')) {
            return new Intent(IntentName::CONSULT, Confidence::HIGH, ['menu_option' => 4], 'companies');
        }

        return null;
    }

    private function resolveContextualFollowUp(string $normalized, string $activeCapability): ?Intent
    {
        $consultTriggers = ['listado', 'lista', 'ver', 'mostrar', 'detalle', 'detalles', 'muéstrame', 'muestrame', 'consultar', 'consulta', 'buscar', 'search', 'find', 'búscame', 'buscame'];
        $createTriggers = ['crear', 'nuevo', 'nueva', 'registrar', 'añadir', 'agregar'];
        $editTriggers = ['editar', 'cambiar', 'actualizar', 'modificar'];
        $deleteTriggers = ['eliminar', 'borrar', 'suprimir'];
        $sendTriggers = ['enviar', 'mandar'];

        foreach ($consultTriggers as $trigger) {
            if (str_contains($normalized, $trigger)) {
                return new Intent(IntentName::CONSULT, Confidence::HIGH, ['contextual_follow_up' => true], $activeCapability);
            }
        }

        foreach ($createTriggers as $trigger) {
            if (str_contains($normalized, $trigger)) {
                return new Intent(IntentName::CREATE, Confidence::HIGH, ['contextual_follow_up' => true], $activeCapability);
            }
        }

        foreach ($editTriggers as $trigger) {
            if (str_contains($normalized, $trigger)) {
                return new Intent(IntentName::EDIT, Confidence::HIGH, ['contextual_follow_up' => true], $activeCapability);
            }
        }

        foreach ($deleteTriggers as $trigger) {
            if (str_contains($normalized, $trigger)) {
                return new Intent(IntentName::DELETE, Confidence::HIGH, ['contextual_follow_up' => true], $activeCapability);
            }
        }

        foreach ($sendTriggers as $trigger) {
            if (str_contains($normalized, $trigger)) {
                return new Intent(IntentName::SEND, Confidence::HIGH, ['contextual_follow_up' => true], $activeCapability);
            }
        }

        return null;
    }

    private function hasAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($text, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function detectVerb(string $normalized): ?IntentName
    {
        foreach ($this->verbMap as $needle => $intent) {
            if (str_contains($normalized, $needle)) {
                return $intent;
            }
        }

        return null;
    }

    private function detectCapability(string $normalized): ?string
    {
        foreach ($this->capabilityMap as $needle => $capability) {
            if (str_contains($normalized, $needle)) {
                return $capability;
            }
        }

        return null;
    }

    private function calculateScore(string $normalized, ?IntentName $verb, ?string $capability): int
    {
        $score = 0;

        if ($verb !== null) {
            $score += 40;
        }

        if ($capability !== null) {
            $score += 40;
        }

        if ($verb !== null && $capability !== null) {
            $score += 20;
        }

        if ($this->extractAmount($normalized) !== null) {
            $score += 10;
        }

        if ($this->extractDate($normalized) !== null) {
            $score += 10;
        }

        return min($score, 100);
    }

    private function extractAmount(string $normalized): ?float
    {
        if (preg_match('/(\d+(?:[.,]\d{1,2})?)\s*(?:€|eur|euros)/i', $normalized, $matches)) {
            return (float) str_replace(',', '.', $matches[1]);
        }

        return null;
    }

    private function extractDate(string $normalized): ?string
    {
        if (preg_match('/(\d{1,2})\/(\d{1,2})\/(\d{2,4})/', $normalized, $matches)) {
            return sprintf('%02d/%02d/%s', (int) $matches[1], (int) $matches[2], $matches[3]);
        }

        return null;
    }
}
