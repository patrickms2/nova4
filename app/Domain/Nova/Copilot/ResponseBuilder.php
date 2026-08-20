<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Enums\Confidence;
use App\Domain\Nova\Copilot\Enums\IntentName;
use App\Domain\Nova\Copilot\ValueObjects\Action;
use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use App\Domain\Nova\Copilot\ValueObjects\Intent;
use App\Domain\Nova\Copilot\ValueObjects\Response;

final readonly class ResponseBuilder
{
    public function __construct(private MenuEngine $menuEngine) {}

    /**
     * @param  array<int, Action>  $actions
     */
    public function build(
        Input $input,
        Intent $intent,
        array $actions,
        ConversationContext $context,
    ): Response {
        if ($intent->name === IntentName::GREETING) {
            return $this->greetingResponse($context);
        }

        if ($intent->name === IntentName::MENU) {
            return $this->menuResponse($context);
        }

        if ($intent->name === IntentName::OPERATIONAL_MENU) {
            return $this->operationalMenuResponse();
        }

        if ($intent->name === IntentName::UNKNOWN || $intent->confidence === Confidence::LOW) {
            return $this->clarificationResponse($context);
        }

        if (in_array($intent->name, [IntentName::CONFIRM, IntentName::CANCEL], true)) {
            return new Response(
                text: $intent->name === IntentName::CONFIRM
                    ? 'Perfecto, procesando la operación.'
                    : 'Operación cancelada. ¿En qué más puedo ayudarte?',
                actions: array_map(
                    static fn (Action $action): Action => $action,
                    $actions,
                ),
            );
        }

        $capabilityLabel = $this->capabilityLabel($intent->targetCapability ?? $context->activeCapability);

        return match ($intent->name) {
            IntentName::CONSULT => new Response(
                text: "He entendido que quieres consultar {$capabilityLabel}. Puedes ver el listado o buscar con filtros.",
                actions: $actions,
            ),
            IntentName::CREATE => new Response(
                text: "Voy a crear un nuevo registro de {$capabilityLabel}. ¿Es correcto?",
                actions: $actions,
                requiresConfirmation: true,
            ),
            IntentName::EDIT => new Response(
                text: "Quieres editar el {$capabilityLabel} activo.",
                actions: $actions,
            ),
            IntentName::DELETE => new Response(
                text: "Vas a eliminar el {$capabilityLabel} activo. ¿Confirmas?",
                actions: $actions,
                requiresConfirmation: true,
            ),
            IntentName::SEND => new Response(
                text: "Voy a enviar el {$capabilityLabel} activo. ¿Confirmas?",
                actions: $actions,
                requiresConfirmation: true,
            ),
            IntentName::IMPORT => new Response(
                text: "Voy a importar datos para {$capabilityLabel}.",
                actions: $actions,
            ),
            IntentName::ANALYZE => new Response(
                text: "Voy a analizar el contenido recibido.",
                actions: $actions,
            ),
            IntentName::COMPARE => new Response(
                text: "Voy a comparar los registros de {$capabilityLabel}.",
                actions: $actions,
            ),
            IntentName::CONFIGURE => new Response(
                text: "Abriendo configuración del Workspace.",
                actions: $actions,
            ),
            IntentName::REPLY => new Response(
                text: 'He recibido tu mensaje. ¿Qué acción quieres realizar?',
                actions: $actions,
            ),
            default => $this->clarificationResponse($context),
        };
    }

    private function greetingResponse(ConversationContext $context): Response
    {
        $workspaceName = $context->workspace['business_name'] ?? 'tu Workspace';
        $menu = $this->menuEngine->mainMenu($context);

        return new Response(
            text: "Hola 👋\n\nSoy el Copiloto de {$workspaceName}. ¿Qué necesitas?\n\nPuedes escribirme, enviar una foto, un PDF o un audio.",
            actions: [
                new Action('show_menu', 'Ver menú', 'copilot.menu', []),
            ],
            menu: $menu,
        );
    }

    private function menuResponse(ConversationContext $context): Response
    {
        $menu = $context->activeCapability === null
            ? $this->menuEngine->mainMenu($context)
            : $this->menuEngine->contextualMenu($context);

        $title = $context->activeCapability === null
            ? 'Menú principal'
            : 'Menú contextual';

        return new Response(
            text: "{$title}. Elige una opción:",
            menu: $menu,
        );
    }

    private function operationalMenuResponse(): Response
    {
        $text = <<<TEXT
👋 ¡Hola! Soy tu asistente de facturación.

¿Qué necesitas hacer hoy? Puedo ayudarte con:

0. 💰 Resumen Mensual - Reservas
1. 💰 Ver gastos - Revisa tus gastos recientes
2. 📋 Ver facturas - Lista tus facturas recientes
3. 👥 Ver clientes - Consulta tu cartera de clientes
4. 🏢 Ver empresas - Consulta las empresas registradas
5. 📝 Crear factura - Solo nombre del cliente (usa sus conceptos)
6. 💸 Crear gasto - Registra un nuevo gasto
7. 📧 Enviar factura - Envía una factura por email

Escribe el número o el nombre de lo que te interesa.
TEXT;

        return new Response(
            text: $text,
            menu: [
                ['id' => 'resumen', 'label' => 'Resumen Mensual - Reservas'],
                ['id' => 'gastos', 'label' => 'Ver gastos'],
                ['id' => 'facturas', 'label' => 'Ver facturas'],
                ['id' => 'clientes', 'label' => 'Ver clientes'],
                ['id' => 'empresas', 'label' => 'Ver empresas'],
                ['id' => 'crear_factura', 'label' => 'Crear factura'],
                ['id' => 'crear_gasto', 'label' => 'Crear gasto'],
                ['id' => 'enviar_factura', 'label' => 'Enviar factura'],
            ],
        );
    }

    private function clarificationResponse(ConversationContext $context): Response
    {
        return new Response(
            text: "No estoy seguro de lo que necesitas. Puedo ayudarte con reservas, gastos, facturas, clientes, documentos y más.\n\nEscribe lo que quieras hacer o envía 'menu' para ver opciones.",
            actions: [
                new Action('show_menu', 'Ver menú', 'copilot.menu', []),
            ],
            menu: $this->menuEngine->mainMenu($context),
        );
    }

    private function capabilityLabel(?string $capability): string
    {
        $labels = [
            'reservations' => 'reservas',
            'customers' => 'clientes',
            'invoices' => 'facturas',
            'expenses' => 'gastos',
            'payments' => 'pagos',
            'documents' => 'documentos',
            'inventory' => 'inventario',
            'products' => 'productos',
            'issues' => 'incidencias',
            'tasks' => 'tareas',
            'companies' => 'empresas',
            'employees' => 'empleados',
            'appointments' => 'citas',
            'restaurant-menu' => 'menú',
            'tours' => 'tours',
            'winery-catalog' => 'vinos',
        ];

        return $labels[$capability] ?? ($capability ?? 'registro');
    }
}
