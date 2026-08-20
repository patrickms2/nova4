<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Actions\Booking\CreatePackageBookingRequest;
use App\Actions\Booking\CreateWineryVisitBookingRequestFromNovaConversation;
use App\Actions\Taxi\CreateTransferBookingRequestFromNovaConversation;
use App\Models\Note;
use App\Models\NovaBusiness;
use App\Models\NovaIntentToServerMapping;
use App\Models\NovaRequest;
use App\Models\NovaService;
use App\Models\Prompt;
use App\Models\Restaurant;
use App\Models\Server;
use App\Models\Tool;
use App\Models\Tour;
use App\Mcp\Tools\CasaElPatio\MonthlyReservationsReportTool;
use App\Mcp\Tools\NovaFactu\CreateInvoiceTool;
use App\Mcp\Tools\NovaFactu\SendInvoicePdfTool;
use App\Services\NovaPromptLoader;
use App\Services\ToolExecutor;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Laravel\Mcp\Request as McpRequest;
use Carbon\CarbonImmutable;
use Heiner\FilamentAgenticChatbot\Models\AgentWorkflow;
use Heiner\FilamentAgenticChatbot\Models\RagBot;
use Heiner\FilamentAgenticChatbot\Models\RagConversation;
use Heiner\FilamentAgenticChatbot\Services\WorkflowRunner;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Response;

final class NovaOrchestratorService
{
    public function __construct(
        private readonly SirvoReservationClient $sirvoReservationClient,
        private readonly NovaKnowledgeService $knowledgeService,
        private readonly NovaMcpCreationService $mcpCreationService,
        private readonly NovaConversationContextService $contextService,
        private readonly NovaCrossSellingService $crossSellingService,
        private readonly NovaMcpClient $mcpClient,
        private readonly NovaAiService $aiService,
        private readonly NovaConversationDataExtractor $dataExtractor,
        private readonly ToolExecutor $toolExecutor,
        private readonly NovaLanzaloePurchaseService $lanzaloePurchase,
        private readonly NovaWhatsAppCloudService $whatsappService,
        private readonly WorkflowRunner $workflowRunner,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function runLocalTourismScenario(
        string $message,
        string $touristPhone = '+34646426442',
        bool $debug = true,
        string $channel = 'api',
        ?string $conversationId = null,
        array $channelContext = [],
    ): array {
        // Get Nova MCP Operator bot
        $bot = RagBot::where('name', 'Nova MCP Operator')->first();
        if (!$bot) {
            return [
                'success' => false,
                'error' => 'Nova MCP Operator bot not found. Please configure in Filament.',
            ];
        }

        // Get La Geria Workflow
        $workflow = AgentWorkflow::find(82);
        if (!$workflow) {
            return [
                'success' => false,
                'error' => 'La Geria workflow not found. Please import workflows.',
            ];
        }
        // Get or create conversation
        $sessionId = $conversationId ?? $touristPhone;
        $ragConversation = RagConversation::firstOrCreate(
            [
                'rag_bot_id' => $bot->id,
                'session_id' => $sessionId,
            ],
            [
                'context_area' => $channel,
                'meta' => [
                    'tourist_phone' => $touristPhone,
                    'channel' => $channel,
                    'source_context' => $channelContext,
                ],
            ]
        );

        // Prepare initial variables for workflow
        $initialVariables = [
            'user_message' => $message,
            'tourist_phone' => $touristPhone,
            'channel' => $channel,
            'conversation_id' => $sessionId,
        ];

        try {
            // Check if there's a halted workflow run to resume
            $existingRun = $ragConversation->workflowRuns()
                ->whereNotNull('halt_reason')
                ->latest()
                ->first();

            // Execute workflow (start or resume)
            if ($existingRun) {
                $state = $this->workflowRunner->resume(
                    workflowRun: $existingRun,
                    userInput: $message,
                );
            } else {
                $state = $this->workflowRunner->start(
                    workflow: $workflow,
                    conversation: $ragConversation,
                    userInput: $message,
                    initialVariables: $initialVariables,
                );
            }

            // Create NovaRequest record for tracking
            $request = NovaRequest::create([
                'type' => 'tourism_orchestration',
                'status' => $state->halted ? 'collecting_details' : 'completed',
                'title' => 'Workflow execution: ' . $workflow->name,
                'summary' => $state->output ?? '',
                'context' => [
                    'tourist_phone' => $touristPhone,
                    'channel' => $channel,
                    'conversation_id' => $sessionId,
                    'source_context' => $channelContext,
                    'message' => $message,
                    'workflow_id' => $workflow->id,
                    'workflow_run_id' => $ragConversation->workflowRuns()->latest()->first()?->id,
                    'workflow_state' => [
                        'halted' => $state->halted,
                        'halt_reason' => $state->haltReason,
                        'variables' => $state->variables,
                    ],
                ],
            ]);

            // Resolve template placeholders (e.g. {{normalized_result.choices}}) in output and meta
            $state->output = $this->resolveWorkflowPlaceholders((string) ($state->output ?? ''), $state);
            if (! empty($state->meta['collectInput'])) {
                $state->meta['collectInput']['prompt'] = $this->resolveWorkflowPlaceholders(
                    (string) ($state->meta['collectInput']['prompt'] ?? ''),
                    $state
                );
                if (isset($state->meta['collectInput']['choices'])) {
                    $state->meta['collectInput']['choices'] = $this->resolveWorkflowPlaceholders(
                        is_scalar($state->meta['collectInput']['choices'])
                            ? (string) $state->meta['collectInput']['choices']
                            : json_encode($state->meta['collectInput']['choices']),
                        $state
                    );
                }
            }

            // Extract choices / input type from collectInput meta if present
            $choices = null;
            $inputType = null;
            $collectInputMeta = null;
            if ($state->halted && $state->haltReason === 'waiting_for_input') {
                $collectInputMeta = $state->meta['collectInput'] ?? null;
                if ($collectInputMeta) {
                    $inputType = $collectInputMeta['inputType'] ?? null;
                    if (! empty($collectInputMeta['choices'])) {
                        // If choices is already an array (e.g., for service inputType), use it directly
                        if (is_array($collectInputMeta['choices'])) {
                            $choices = $collectInputMeta['choices'];
                        } else {
                            $raw = trim((string) $collectInputMeta['choices']);

                            // If it's a JSON array/object, parse it properly
                            if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
                                try {
                                    $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
                                    $choices = is_array($decoded) ? $decoded : [$decoded];
                                } catch (\JsonException) {
                                    $choices = [$raw];
                                }
                            } else {
                                // Otherwise, parse as comma-separated string
                                $choices = array_values(array_filter(array_map(
                                    'trim',
                                    explode(',', $raw)
                                ), static fn (string $choice): bool => $choice !== ''));
                            }
                        }
                    }
                }
            }

            // If halted but no output, check if there's a message in the last node
            $reply = $state->output ?? '';
            if (empty($reply) && $state->halted) {
                // Get the prompt from collectInput meta if available
                $reply = $collectInputMeta['prompt'] ?? 'Por favor, selecciona una de las opciones disponibles.';
            }

            // If the workflow expects a choice but there are no options, show a clearer message
            if ($state->halted && $inputType === 'choice' && empty($choices)) {
                $reply = $reply && $reply !== 'Por favor, selecciona una de las opciones disponibles.'
                    ? $reply . "\n\nNo hay opciones disponibles en este momento."
                    : 'No hay opciones disponibles en este momento. Por favor, intenta con otra fecha o servicio.';
            }

            return [
                'success' => true,
                'nova_request_id' => $request->id,
                'message' => $message,
                'reply' => $reply,
                'choices' => $choices,
                'input_type' => $inputType,
                'workflow' => [
                    'id' => $workflow->id,
                    'name' => $workflow->name,
                    'halted' => $state->halted,
                    'halt_reason' => $state->haltReason,
                ],
            ];
        } catch (\Throwable $exception) {
            Log::error('Workflow execution failed', [
                'workflow_id' => $workflow->id,
                'conversation_id' => $ragConversation->id,
                'error' => $exception->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Workflow execution failed: ' . $exception->getMessage(),
            ];
        }
    }

    /**
     * Run NovaFactu scenario for WhatsApp integration.
     * Handles invoicing, clients, expenses operations via WhatsApp.
     *
     * @return array<string, mixed>
     */
    public function runNovaFactuScenario(
        string $message,
        string $userPhone,
        string $channel = 'whatsapp',
        ?string $conversationId = null,
        array $channelContext = [],
    ): array {
        try {
            Log::info('NovaFactu scenario started', [
                'message' => $message,
                'userPhone' => $userPhone,
            ]);

            // Check if a previous step is waiting for a numbered selection
            $pendingIntent = Cache::get("nova_factu_pending_{$userPhone}");

            // Detect intent from message
            $intent = $this->detectNovaFactuIntent($message, $pendingIntent);

            Log::info('NovaFactu intent detected', [
                'intent' => $intent,
                'pending_intent' => $pendingIntent,
                'message' => $message,
            ]);

            if ($intent === 'unknown') {
                Cache::forget("nova_factu_pending_{$userPhone}");

                return [
                    'success' => true,
                    'reply' => 'No estoy seguro de lo que necesitas. Puedo ayudarte con:\n- Resumen mensual de reservas\n- Consultar facturas\n- Crear facturas\n- Ver clientes\n- Consultar gastos\n- Crear gastos\n\n¿Qué necesitas hacer?',
                    'intent' => 'unknown',
                ];
            }

            // Execute the appropriate NovaFactu operation
            $result = $this->executeNovaFactuOperation($intent, $message, $userPhone);

            // If the handler is waiting for a follow-up selection, keep the intent in cache;
            // otherwise clear it so a future number is interpreted as a main-menu option.
            if (! empty($result['expects_followup'])) {
                Cache::put("nova_factu_pending_{$userPhone}", $intent, now()->addMinutes(15));
            } else {
                Cache::forget("nova_factu_pending_{$userPhone}");
            }

            Log::info('NovaFactu operation completed', [
                'intent' => $intent,
                'reply_length' => strlen($result['reply'] ?? ''),
            ]);

            return [
                'success' => true,
                'reply' => $result['reply'],
                'intent' => $intent,
                'data' => $result['data'] ?? null,
                'use_list' => $result['use_list'] ?? false,
                'use_buttons' => $result['use_buttons'] ?? false,
                'buttons' => $result['buttons'] ?? null,
                'rows' => $result['rows'] ?? null,
                'button_text' => $result['button_text'] ?? null,
                'section_title' => $result['section_title'] ?? null,
                'footer' => $result['footer'] ?? null,
            ];
        } catch (\Throwable $exception) {
            Log::error('NovaFactu scenario execution failed', [
                'intent' => $intent ?? 'unknown',
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'Error al procesar la solicitud: ' . $exception->getMessage(),
            ];
        }
    }

    /**
     * Detect NovaFactu intent from user message.
     */
    private function detectNovaFactuIntent(string $message, ?string $pendingIntent = null): string
    {
        $normalized = mb_strtolower($message);

        // If the previous step was waiting for a numbered selection, a bare number
        // should be treated as the selection rather than a main-menu option.
        if ($pendingIntent !== null && in_array($pendingIntent, ['create_invoice', 'send_invoice'], true) && preg_match('/^\s*\d+\s*$/', $message)) {
            return $pendingIntent;
        }

        // Handle button responses (WhatsApp button IDs)
        $buttonIntents = [
            'list_invoices' => 'list_invoices',
            'list_clients' => 'list_clients',
            'list_expenses' => 'list_expenses',
            'list_companies' => 'list_companies',
            'create_invoice' => 'create_invoice',
            'create_expense' => 'create_expense',
            'send_invoice' => 'send_invoice',
        ];

        if (in_array($normalized, array_keys($buttonIntents))) {
            return $buttonIntents[$normalized];
        }

        // Handle numbered menu responses (0-7) - MUST BE FIRST
        if (preg_match('/^\s*0\s*$/', $normalized)) {
            return 'monthly_reservations_report';
        }
        if (preg_match('/^\s*1\s*$/', $normalized)) {
            return 'list_expenses';
        }
        if (preg_match('/^\s*2\s*$/', $normalized)) {
            return 'list_invoices';
        }
        if (preg_match('/^\s*3\s*$/', $normalized)) {
            return 'list_clients';
        }
        if (preg_match('/^\s*4\s*$/', $normalized)) {
            return 'list_companies';
        }
        if (preg_match('/^\s*5\s*$/', $normalized)) {
            return 'create_invoice';
        }
        if (preg_match('/^\s*6\s*$/', $normalized)) {
            return 'create_expense';
        }
        if (preg_match('/^\s*7\s*$/', $normalized)) {
            return 'send_invoice';
        }

        // Numbered client selection for invoice creation (e.g., "cliente 1" or "1, 25/07/2026")
        if (preg_match('/^(?:cliente|factura)?\s*(\d+)(?:\s*,\s*(\d{1,2}\/\d{1,2}\/\d{2,4}))?\s*$/i', trim($message), $matches)) {
            if (isset($matches[2]) || (int) $matches[1] > 7 || preg_match('/^(?:cliente|factura)/i', trim($message))) {
                return 'create_invoice';
            }
        }

        // Numbered client selection for sending invoice (e.g., "enviar 1")
        if (preg_match('/^enviar\s+(?:factura\s+)?(\d+)(?:\s*,\s*(\d{1,2}\/\d{1,2}\/\d{2,4}))?\s*$/i', trim($message), $matches)) {
            return 'send_invoice';
        }

        // Greeting - show interactive menu - MUST BE AFTER NUMBERED SELECTIONS
        if (in_array($normalized, ['hola', 'buenos días', 'buenas tardes', 'buenas noches', 'hi', 'hello', 'hey'])) {
            return 'menu';
        }
        if (str_contains($normalized, 'ocr')) {
            return 'list_expenses';
        }
        // Invoice-related intents
        if (str_contains($normalized, 'factura') || str_contains($normalized, 'facturas')) {
            if (str_contains($normalized, 'crear') || str_contains($normalized, 'nueva') || str_contains($normalized, 'emitir')) {
                return 'create_invoice';
            }
            if (str_contains($normalized, 'enviar') || str_contains($normalized, 'pdf')) {
                return 'send_invoice';
            }
            return 'list_invoices';
        }

        // Client-related intents
        if (str_contains($normalized, 'cliente') || str_contains($normalized, 'clientes')) {
            return 'list_clients';
        }

        // Expense-related intents
        if (str_contains($normalized, 'gasto') || str_contains($normalized, 'gastos')) {
            if (str_contains($normalized, 'crear') || str_contains($normalized, 'nuevo') || str_contains($normalized, 'registrar')) {
                return 'create_expense';
            }
            return 'list_expenses';
        }

        // Company-related intents
        if (str_contains($normalized, 'empresa') || str_contains($normalized, 'empresas')) {
            return 'list_companies';
        }

        // Check if message contains amount (€) - likely creating something
        if (preg_match('/\d+(?:[.,]\d+)?\s*(?:€|eur|euros)/i', $message)) {
            // Determine if it's expense or invoice based on context
            if (str_contains($normalized, 'factura')) {
                return 'create_invoice';
            }
            return 'create_expense';
        }

        // Check if message is a single word that might be a client name (likely invoice creation) - MUST BE LAST
        $trimmed = trim($message);
        if (preg_match('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/', $trimmed) && strlen($trimmed) > 2) {
            return 'create_invoice';
        }

        return 'unknown';
    }

    /**
     * Execute NovaFactu operation based on intent.
     *
     * @return array{reply: string, data?: mixed}
     */
    public function executeNovaFactuOperation(string $intent, string $message, string $userPhone): array
    {
        return match ($intent) {
            'menu' => $this->handleMenu(),
            'monthly_reservations_report' => $this->handleMonthlyReservationsReport($message),
            'list_invoices' => $this->handleListInvoices($message),
            'create_invoice' => $this->handleCreateInvoice($message),
            'send_invoice' => $this->handleSendInvoice($message),
            'list_clients' => $this->handleListClients($message),
            'create_expense' => $this->handleCreateExpense($message),
            'list_expenses' => $this->handleListExpenses($message),
            'list_companies' => $this->handleListCompanies($message),
            default => ['reply' => 'Operación no reconocida.'],
        };
    }

    /**
     * Handle menu - show interactive options.
     */
    private function handleMenu(): array
    {
        return [
            'reply' => "👋 ¡Hola! Soy tu asistente de facturación.\n\n" .
                     "¿Qué necesitas hacer hoy? Puedo ayudarte con:\n\n" .
                     "0. 💰 Resumen Mensual - Reservas\n" .
                     "1. 💰 Ver gastos - Revisa tus gastos recientes\n" .
                     "2. 📋 Ver facturas - Lista tus facturas recientes\n" .
                     "3. 👥 Ver clientes - Consulta tu cartera de clientes\n" .
                     "4. 🏢 Ver empresas - Consulta las empresas registradas\n" .
                     "5. 📝 Crear factura - Solo nombre del cliente (usa sus conceptos)\n" .
                     "6. 💸 Crear gasto - Registra un nuevo gasto\n" .
                     "7. 📧 Enviar factura - Envía una factura por email\n\n" .
                     "Escribe el número o el nombre de lo que te interesa.",
        ];
    }

    /**
     * Handle monthly reservations report operation.
     */
    private function handleMonthlyReservationsReport(string $message): array
    {
        try {
            $params = [];

            if (preg_match('/(\d{1,2})\s*[\/\-\.]\s*(\d{4})/', $message, $matches)) {
                $params['month'] = (int) $matches[1];
                $params['year'] = (int) $matches[2];
            } elseif (preg_match('/(\d{4})/', $message, $matches)) {
                $params['year'] = (int) $matches[1];
            }

            $tool = app(MonthlyReservationsReportTool::class);
            $response = $tool->handle(new McpRequest($params));
            $data = json_decode((string) $response->content(), true);

            $period = $data['period'];
            $summary = $data['summary'];

            $reply = "💰 *Resumen mensual de reservas - {$period['month']}/{$period['year']}*\n\n";
            $reply .= "• Reservas: {$summary['total_reservations']}\n";
            $reply .= "• Confirmadas: {$summary['confirmed_reservations']}\n";
            $reply .= "• Ingresos totales: {$summary['total_amount']}€\n";
            $reply .= "• Ingresos confirmados: {$summary['confirmed_amount']}€\n";
            $reply .= "• Noches: {$summary['total_nights']}\n";
            $reply .= "• Payout: {$summary['total_payout']}€\n";
            $reply .= "• Limpieza: {$summary['total_cleaning_fees']}€\n";
            $reply .= "• Tarifa media/noche: {$summary['avg_nightly_rate']}€\n\n";

            if (! empty($summary['by_status'])) {
                $reply .= "*Por estado:*\n";
                foreach ($summary['by_status'] as $status => $info) {
                    $reply .= "• {$status}: {$info['count']} ({$info['amount']}€)\n";
                }
                $reply .= "\n";
            }

            if (! empty($summary['by_property'])) {
                $reply .= "*Por propiedad:*\n";
                foreach ($summary['by_property'] as $property) {
                    $reply .= "• {$property['property']}: {$property['count']} reservas, {$property['amount']}€, {$property['nights']} noches\n";
                }
                $reply .= "\n";
            }

            $reservas = $data['reservations'] ?? [];
            if (! empty($reservas)) {
                $reply .= "*Próximas reservas:*\n";
                foreach (array_slice($reservas, 0, 5) as $reserva) {
                    $reply .= "• {$reserva['reference_code']} - {$reserva['guest']} ({$reserva['property']})\n";
                    $reply .= "  Entrada: {$reserva['check_in']} | Noches: {$reserva['nights']} | Importe: {$reserva['amount']}€\n";
                }
            }

            return ['reply' => $reply, 'data' => $data];
        } catch (\Throwable $e) {
            \Log::error('Monthly reservations report failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['reply' => '❌ Error al generar el informe de reservas: ' . $e->getMessage()];
        }
    }

    /**
     * Handle list invoices operation.
     */
    private function handleListInvoices(string $message): array
    {
        // Query invoices directly from NovaFactu models
        $facturas = \App\Models\Factura::query()
            ->with('cliente:id,nombretotal,dni')
            ->orderByDesc('fechaemitido')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($facturas->isEmpty()) {
            return ['reply' => 'No hay facturas recientes.'];
        }

        $reply = "📋 *Facturas recientes:*\n\n";
        foreach ($facturas as $factura) {
            $viewUrl = route('factura.pdf', ['codfactura' => $factura->codfactura], true);
            $pdfUrl = url('/facturas-pdf/'.$factura->codfactura.'.pdf');

            $reply .= "• {$factura->codfactura} - {$factura->cliente?->nombretotal}\n";
            $reply .= "  Fecha: {$factura->fechaemitido?->toDateString()}\n";
            $reply .= "  Importe: {$factura->importe}€\n";
            $reply .= "  Estado: " . ($factura->pagada ? 'Pagada' : 'Pendiente') . "\n";
            $reply .= "  Ver factura: {$viewUrl}\n";
            $reply .= "  Descargar PDF: {$pdfUrl}\n\n";
        }

        return ['reply' => $reply, 'data' => ['count' => $facturas->count()]];
    }

    /**
     * Handle list clients operation.
     */
    private function handleListClients(string $message): array
    {
        $clientes = \App\Models\Cliente::query()
            ->withCount('facturas')
            ->orderBy('nombretotal')
            ->limit(10)
            ->get();

        if ($clientes->isEmpty()) {
            return ['reply' => 'No hay clientes registrados.'];
        }

        $reply = "👥 *Clientes:*\n\n";
        foreach ($clientes as $cliente) {
            $reply .= "• {$cliente->nombretotal}\n";
            $reply .= "  DNI: {$cliente->dni}\n";
            $reply .= "  Tel: {$cliente->telefono}\n";
            $reply .= "  Facturas: {$cliente->facturas_count}\n\n";
        }

        return ['reply' => $reply, 'data' => ['count' => $clientes->count()]];
    }

    /**
     * Handle list expenses operation.
     */
    private function handleListExpenses(string $message): array
    {
        $gastos = \App\Models\Gasto::query()
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        if ($gastos->isEmpty()) {
            return ['reply' => 'No hay gastos recientes.'];
        }

        $reply = "💰 *Gastos recientes:*\n\n";
        foreach ($gastos as $gasto) {
            $reply .= "• {$gasto->descripcion}\n";
            $reply .= "  Fecha: {$gasto->fecha?->toDateString()}\n";
            $reply .= "  Importe: {$gasto->total}€\n";
            $reply .= "  Estado: {$gasto->estado}\n\n";
        }

        return ['reply' => $reply, 'data' => ['count' => $gastos->count()]];
    }

    /**
     * Handle list companies operation.
     */
    private function handleListCompanies(string $message): array
    {
        $empresas = \App\Models\Empresa::query()
            ->orderBy('nombre')
            ->limit(10)
            ->get();

        if ($empresas->isEmpty()) {
            return ['reply' => 'No hay empresas registradas.'];
        }

        $reply = "🏢 *Empresas:*\n\n";
        foreach ($empresas as $empresa) {
            $reply .= "• {$empresa->nombre}\n";
            $reply .= "  NIF: {$empresa->nif}\n\n";
        }

        return ['reply' => $reply, 'data' => ['count' => $empresas->count()]];
    }

    /**
     * Handle create invoice operation.
     */
    private function handleCreateInvoice(string $message): array
    {
        Log::info('handleCreateInvoice called', ['message' => $message]);

        $normalized = mb_strtolower(trim($message));
        $listTriggers = ['crear factura', 'factura', 'nueva factura', '5'];

        if (in_array($normalized, $listTriggers, true)) {
            return $this->showNumberedClientsList(
                'create_invoice',
                "Para crear una factura, envía el número del cliente:\n\n" .
                'Ejemplo: "cliente 1" o "1, 25/07/2026"'
            );
        }

        // Numbered client with optional date and optional prefix (e.g., "cliente 1" or "1, 25/07/2026")
        if (preg_match('/^(?:cliente|factura)?\s*(\d+)(?:\s*,\s*(\d{1,2}\/\d{1,2}\/\d{2,4}))?\s*$/i', trim($message), $matches)) {
            $numero = (int) $matches[1];
            $fecha = $matches[2] ?? null;
            $fechaIso = $fecha ? Carbon::createFromFormat('d/m/Y', $fecha)->toDateString() : null;

            return $this->callCreateInvoiceTool(cliente_numero: $numero, fechaemitido: $fechaIso);
        }

        // Client name only or with date
        if (preg_match('/^([^,]+?)(?:,\s*(\d{1,2}\/\d{1,2}\/\d{2,4}))?$/', trim($message), $matches)) {
            $nombre = trim($matches[1]);
            $fecha = $matches[2] ?? null;
            $fechaIso = $fecha ? Carbon::createFromFormat('d/m/Y', $fecha)->toDateString() : null;

            return $this->callCreateInvoiceTool(cliente: $nombre, fechaemitido: $fechaIso);
        }

        return [
            'reply' => 'Para crear una factura necesito:\n\n' .
                     '• Número del cliente (según lista numerada)\n' .
                     '• Fecha (opcional, formato DD/MM/YYYY)\n\n' .
                     'El sistema usará automáticamente los conceptos del cliente.\n\n' .
                     'Ejemplo: "cliente 1" o "1, 25/07/2026"\n\n' .
                     'Usa "crear factura" para ver la lista numerada.',
        ];
    }

    /**
     * Handle send invoice operation.
     */
    private function handleSendInvoice(string $message): array
    {
        $normalized = mb_strtolower(trim($message));
        $listTriggers = ['enviar factura', 'enviar', '7'];

        if (in_array($normalized, $listTriggers, true)) {
            return $this->showNumberedClientsList(
                'send_invoice',
                "Para enviar una factura, escribe el número del cliente:\n\n" .
                'Ejemplo: "enviar 1"'
            );
        }

        // Selection: "enviar 1" or bare number (when pending context resolved here)
        if (preg_match('/^(?:enviar\s+(?:factura\s+)?)?(\d+)\s*$/i', trim($message), $matches)) {
            return $this->sendInvoiceForClientNumber((int) $matches[1]);
        }

        return [
            'reply' => 'Para enviar una factura necesito el número del cliente según la lista numerada.\n\n' .
                     'Ejemplo: "enviar 1"\n\n' .
                     'Usa "enviar factura" para ver la lista numerada.',
        ];
    }

    /**
     * Show a numbered list of clients and mark the step as awaiting a selection.
     */
    private function showNumberedClientsList(string $intent, string $prompt): array
    {
        try {
            $clientes = \App\Models\Cliente::with('empresa')->orderBy('nombretotal')->limit(20)->get();

            if ($clientes->isEmpty()) {
                return ['reply' => 'No hay clientes registrados.'];
            }

            $reply = "📋 **Lista de clientes numerados**\n\n";
            foreach ($clientes as $index => $cliente) {
                $reply .= ($index + 1) . ". {$cliente->nombretotal}";
                if ($cliente->empresa) {
                    $reply .= " ({$cliente->empresa->nombre})";
                }
                $reply .= "\n";
            }

            $reply .= "\n" . $prompt;

            return ['reply' => $reply, 'expects_followup' => true];
        } catch (\Throwable $e) {
            \Log::error("List clients failed for {$intent}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['reply' => "❌ Error al obtener lista de clientes: {$e->getMessage()}"];
        }
    }

    /**
     * Call CreateInvoiceTool and format the result.
     */
    private function callCreateInvoiceTool(?int $cliente_numero = null, ?string $cliente = null, ?string $fechaemitido = null): array
    {
        try {
            $params = [];
            if ($cliente_numero !== null) {
                $params['cliente_numero'] = $cliente_numero;
            } elseif ($cliente !== null) {
                $params['cliente'] = $cliente;
            }
            if ($fechaemitido !== null) {
                $params['fechaemitido'] = $fechaemitido;
            }

            $tool = app(CreateInvoiceTool::class);
            $response = $tool->handle(new McpRequest($params));
            $data = json_decode((string) $response->content(), true);

            if (isset($data['success']) && $data['success'] === false) {
                return ['reply' => '❌ Error: ' . ($data['error'] ?? 'No se pudo crear la factura.')];
            }

            if (isset($data['error'])) {
                return ['reply' => '❌ Error: ' . $data['error']];
            }

            $reply = "✅ Factura creada:\n\n" .
                     "• Número: {$data['codfactura']}\n" .
                     "• Cliente: {$data['cliente']}\n" .
                     "• Fecha: {$data['fechaemitido']}\n" .
                     "• Base imponible: {$data['baseimponible']}€\n" .
                     "• Impuestos: {$data['impuesto']}€\n" .
                     "• Retenciones: {$data['retenciones']}€\n" .
                     "• Total: {$data['importe']}€\n" .
                     "• Líneas: {$data['lineas']}";

            if (! empty($data['cliente_id'])) {
                $clienteModel = \App\Models\Cliente::with('empresa')->find($data['cliente_id']);
                $empresaEmail = $clienteModel?->empresa?->email;
                if ($empresaEmail) {
                    $reply .= "\n\n¿Quieres recibir esta factura por email en {$empresaEmail}? Responde 'sí' o 'no'.";
                }
            }

            return ['reply' => $reply, 'data' => $data];
        } catch (\Throwable $e) {
            \Log::error('CreateInvoiceTool failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['reply' => '❌ Error al crear factura: ' . $e->getMessage()];
        }
    }

    /**
     * Send the latest invoice for a client selected by number.
     */
    private function sendInvoiceForClientNumber(int $numero): array
    {
        try {
            $clientes = \App\Models\Cliente::with('empresa')->orderBy('nombretotal')->limit(20)->get();

            if ($numero < 1 || $numero > $clientes->count()) {
                return [
                    'reply' => "❌ Número de cliente inválido. Debe estar entre 1 y {$clientes->count()}.\n\n" .
                               'Envía "enviar factura" para ver la lista numerada.',
                ];
            }

            $cliente = $clientes[$numero - 1];
            $factura = \App\Models\Factura::where('cliente_id', $cliente->id)
                ->orderBy('id', 'desc')
                ->first();

            if (! $factura) {
                return ['reply' => '❌ No hay facturas para enviar para este cliente.'];
            }

            $tool = app(SendInvoicePdfTool::class);
            $response = $tool->handle(new McpRequest(['factura_id' => $factura->id]));
            $data = json_decode((string) $response->content(), true);

            if (isset($data['success']) && $data['success'] === false) {
                return ['reply' => '❌ Error: ' . ($data['error'] ?? 'No se pudo enviar la factura.')];
            }

            if (isset($data['error'])) {
                return ['reply' => '❌ Error: ' . $data['error']];
            }

            $reply = "✅ Factura enviada:\n\n" .
                     "• Número: {$data['codfactura']}\n" .
                     "• Cliente: {$data['cliente']}\n" .
                     "• Email: {$data['email']}\n" .
                     "• Adjunto: {$data['adjunto']}";

            return ['reply' => $reply, 'data' => $data];
        } catch (\Throwable $e) {
            \Log::error('SendInvoicePdfTool failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return ['reply' => '❌ Error al enviar factura: ' . $e->getMessage()];
        }
    }

    /**
     * Handle create expense operation.
     */
    private function handleCreateExpense(string $message): array
    {
        // Parse the message to extract expense details
        $parsed = $this->parseExpenseMessage($message);
        ds($parsed);
        if ($parsed['id']) {
            return [
                'reply' => "✅ Gasto creado correctamente:\n\n" .
                    "• Código: {$parsed['codigo']}\n" .
                    "• Descripción: {$parsed['descripcion']}\n" .
                    "• Importe: {$parsed['total']}€\n" .
                    "• Fecha: {$parsed['fecha']}\n" .
                    "• Categoría: {$parsed['categoria']}\n" .
                    "• Estado: Pendiente",
                'data' => ['gasto_id' => $parsed['id'], 'codigo' => $parsed['codigo']],
            ];

        }

        if (!$parsed['has_amount']) {
            return [
                'reply' => 'No pude detectar el importe. Por favor incluye el importe en el mensaje.\n\n' .
                         'Ejemplo: "gasolina, 50€. Tabaojo, 24/07/2026"',
            ];
        }

        try {
                $gasto = \App\Models\Gasto::create([
                    'descripcion' => $parsed['description'] ?? 'Gasto desde WhatsApp',
                    'total' => $parsed['amount'],
                    'base_imponible' => $parsed['amount'], // Assuming no tax for now
                    'fecha' => $parsed['date'] ?? now(),
                    'categoria' => $parsed['category'] ?? $this->inferCategory($parsed['description'] ?? ''),
                    'notas' => $parsed['notes'] ?? null,
                    'estado' => 'pendiente',
                    'empresa_id' => 1, // Default company - could be configurable
                ]);

                return [
                    'reply' => "✅ Gasto creado correctamente:\n\n" .
                        "• Código: {$gasto->codigo}\n" .
                        "• Descripción: {$gasto->descripcion}\n" .
                        "• Importe: {$gasto->total}€\n" .
                        "• Fecha: {$gasto->fecha->toDateString()}\n" .
                        "• Categoría: " . (\App\Models\Gasto::categorias()[$gasto->categoria] ?? $gasto->categoria) . "\n" .
                        "• Estado: Pendiente",
                    'data' => ['gasto_id' => $gasto->id, 'codigo' => $gasto->codigo],
                ];

        } catch (\Throwable $e) {
            return [
                'reply' => '❌ Error al crear el gasto: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parse expense message to extract details.
     */
    private function parseExpenseMessage(string $message): array
    {
        $parsed = [
            'description' => null,
            'amount' => null,
            'date' => null,
            'category' => null,
            'notes' => null,
            'has_amount' => false,
        ];

        // Extract amount (e.g., "200€", "200", "200 euros")
        if (preg_match('/(\d+(?:[.,]\d+)?)\s*(?:€|eur|euros)?/i', $message, $matches)) {
            $parsed['amount'] = (float) str_replace(',', '.', $matches[1]);
            $parsed['has_amount'] = true;
        }

        // Extract date (e.g., "24/07/2026", "24-07-2026", "24/07/26")
        if (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/', $message, $matches)) {
            $day = $matches[1];
            $month = $matches[2];
            $year = strlen($matches[3]) === 2 ? '20' . $matches[3] : $matches[3];
            $parsed['date'] = \Carbon\Carbon::createFromFormat('Y-m-d', "$year-$month-$day")->startOfDay();
        }

        // Extract description (first part before comma or period)
        $parts = preg_split('/[,.]/', $message, 3);
        if (!empty($parts[0])) {
            $parsed['description'] = trim($parts[0]);
        }

        // Extract notes (second part between commas)
        if (isset($parts[1])) {
            $parsed['notes'] = trim($parts[1]);
        }

        return $parsed;
    }

    /**
     * Infer category from description.
     */
    private function inferCategory(string $description): string
    {
        $normalized = mb_strtolower($description);

        $categoryMap = [
            'gasolina' => 'suministros',
            'combustible' => 'suministros',
            'electricidad' => 'suministros',
            'luz' => 'suministros',
            'agua' => 'suministros',
            'internet' => 'servicios',
            'telefono' => 'servicios',
            'móvil' => 'servicios',
            'alquiler' => 'alquiler',
            'renta' => 'alquiler',
            'seguro' => 'seguros',
            'seguros' => 'seguros',
            'nomina' => 'nomina',
            'nómina' => 'nomina',
            'salario' => 'nomina',
            'impuesto' => 'impuestos',
            'impuestos' => 'impuestos',
            'tasas' => 'impuestos',
            'marketing' => 'marketing',
            'publicidad' => 'marketing',
            'mantenimiento' => 'mantenimiento',
            'reparacion' => 'mantenimiento',
            'reparación' => 'mantenimiento',
        ];

        foreach ($categoryMap as $keyword => $category) {
            if (str_contains($normalized, $keyword)) {
                return $category;
            }
        }

        return 'otros';
    }

    /**
     * Get active MCP server by type.
     *
     * Priority:
     * 1. Active server from database (servers table)
     * 2. Fallback to config (services.nova.sirvo_endpoint_url, etc.) for development/production
     *
     * This allows development with local endpoints without DB entries,
     * and production with server endpoints configured via .env.
     */
    private function activeServer(string $type): ?Server
    {
        // Solo usar tabla Server como fuente de verdad
        // Configurar servers en Filament
        return Server::query()
            ->where('type', $type)
            ->where('status', 'active')
            ->latest('last_checked_at')
            ->first();
    }

    /**
     * Get server for intent using dynamic routing from Filament mappings.
     *
     * Priority:
     * 1. Business-specific mapping (nova_business_id)
     * 2. Global mapping (nova_business_id = null)
     * 3. Fallback to legacy activeServer() method
     *
     * @return array{server: Server|null, tool: Tool|null, mapping: NovaIntentToServerMapping|null}
     */
    private function getServerForIntent(string $intentKey, ?NovaBusiness $business): array
    {
        $businessId = $business?->id;

        // Try business-specific mapping first
        $mapping = NovaIntentToServerMapping::query()
            ->active()
            ->forIntent($intentKey)
            ->forBusiness($businessId)
            ->orderedByPriority()
            ->first();

        // If no business-specific mapping, try global mapping
        if (!$mapping && $businessId) {
            $mapping = NovaIntentToServerMapping::query()
                ->active()
                ->forIntent($intentKey)
                ->whereNull('nova_business_id')
                ->orderedByPriority()
                ->first();
        }

        if (!$mapping) {
            // Sin mapping = sin configuración en Filament
            return [
                'server' => null,
                'tool' => null,
                'mapping' => null,
            ];
        }

        $server = $mapping->server;
        $tool = $mapping->tool;

        // Verify server has the required capability
        if ($server && !$server->hasCapability($intentKey)) {
            Log::warning("Server {$server->name} does not have capability {$intentKey}");
        }

        return [
            'server' => $server,
            'tool' => $tool,
            'mapping' => $mapping,
        ];
    }

    private function businessForConversation(array $conversation, string $message): ?NovaBusiness
    {
        $normalized = mb_strtolower($message);

        // 1. Data-driven: loop active businesses with recognition_terms in settings
        $businesses = NovaBusiness::query()->where('status', 'active')->get();

        foreach ($businesses as $business) {
            $terms = $business->settings['recognition_terms'] ?? [];

            if (! empty($terms)) {
                foreach ($terms as $term) {
                    if (str_contains($normalized, mb_strtolower($term))) {
                        return $business;
                    }
                }

                continue;
            }

            // 2. Fallback: match on slug/name directly
            if (str_contains($normalized, mb_strtolower($business->slug))
                || str_contains($normalized, mb_strtolower($business->name))) {
                return $business;
            }
        }

        // 3. Intent-based fallback for known intents
        return NovaBusiness::query()
            ->where('status', 'active')
            ->when(
                in_array($conversation['intent'], ['winery_visit', 'restaurant_and_winery_visit'], true),
                fn ($query) => $query->where(fn ($query) => $query
                    ->where('slug', 'like', '%geria%')
                    ->orWhere('name', 'like', '%Geria%')),
                fn ($query) => $query->where(fn ($query) => $query
                    ->where('slug', 'like', '%sirvo%')
                    ->orWhere('name', 'like', '%Sirvo%')
                    ->orWhere('business_type', 'restaurant')),
            )
            ->first();
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function businessByTerms(array $terms): ?NovaBusiness
    {
        return NovaBusiness::query()
            ->where('status', 'active')
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query
                        ->orWhere('slug', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%")
                        ->orWhere('business_type', 'like', "%{$term}%");
                }
            })
            ->first();
    }

    /**
     * Generic server probe method - routes to specific probe methods based on server type
     *
     * @return array<string, mixed>
     */
    private function probeServer(Server $server): array
    {
        return match ($server->type) {
            'sirvo' => $this->probeSirvo($server),
            'la_geria' => $this->probeLaGeria($server),
            'taxi_lanzaloe', 'taxilanz' => $this->probeGenericServer($server),
            'lanzaloe' => $this->probeGenericServer($server),
            default => $this->probeGenericServer($server),
        };
    }

    /**
     * Generic server probe for unknown server types
     *
     * @return array<string, mixed>
     */
    private function probeGenericServer(Server $server): array
    {
        $baseUrl = rtrim((string) $server->endpoint_url, '/');

        if (empty($server->endpoint_url)) {
            return [
                'server_id' => $server->id,
                'endpoint_url' => $baseUrl,
                'reachable' => false,
                'message' => 'Endpoint URL is not configured',
            ];
        }

        try {
            $response = Http::timeout(3)->get($baseUrl);
        } catch (\Throwable $exception) {
            return [
                'server_id' => $server->id,
                'endpoint_url' => $baseUrl,
                'reachable' => false,
                'message' => $exception->getMessage(),
            ];
        }

        return [
            'server_id' => $server->id,
            'endpoint_url' => $baseUrl,
            'reachable' => $response->successful(),
            'status' => $response->status(),
            'message' => $this->compactBody($response->body()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function probeSirvo(Server $server): array
    {
        $baseUrl = rtrim((string) $server->endpoint_url, '/');

        // Skip HTTP requests if endpoint_url is null or empty
        if (empty($server->endpoint_url)) {
            return [
                'server_id' => $server->id,
                'endpoint_url' => $baseUrl,
                'config' => [
                    'url' => $baseUrl.'/api/config',
                    'status' => null,
                    'reachable' => false,
                    'message' => 'Endpoint URL is not configured',
                ],
                'branches' => [
                    'url' => $baseUrl.'/api/branches',
                    'status' => null,
                    'reachable' => false,
                    'message' => 'Endpoint URL is not configured',
                ],
            ];
        }

        try {
            $config = Http::timeout(3)->get($baseUrl.'/api/config');
            $branches = Http::timeout(3)->get($baseUrl.'/api/branches');
        } catch (\Throwable $exception) {
            return [
                'server_id' => $server->id,
                'endpoint_url' => $baseUrl,
                'config' => [
                    'url' => $baseUrl.'/api/config',
                    'status' => null,
                    'reachable' => false,
                    'message' => $exception->getMessage(),
                ],
                'branches' => [
                    'url' => $baseUrl.'/api/branches',
                    'status' => null,
                    'reachable' => false,
                    'message' => $exception->getMessage(),
                ],
            ];
        }

        return [
            'server_id' => $server->id,
            'endpoint_url' => $baseUrl,
            'config' => [
                'url' => $baseUrl.'/api/config',
                'status' => $config->status(),
                'reachable' => $config->status() === 400 && str_contains((string) $config->body(), 'restaurantId es requerido'),
                'message' => $this->compactBody($config->body()),
            ],
            'branches' => [
                'url' => $baseUrl.'/api/branches',
                'status' => $branches->status(),
                'reachable' => $branches->status() === 400 && str_contains((string) $branches->body(), 'Authorization'),
                'message' => $this->compactBody($branches->body()),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function probeLaGeria(Server $server): array
    {
        if ($this->isJsonRpcMcpEndpoint((string) $server->endpoint_url)) {
            return $this->probeLaGeriaJsonRpc($server);
        }

        // Skip HTTP requests if endpoint_url is null or empty
        if (empty($server->endpoint_url)) {
            return [
                'server_id' => $server->id,
                'endpoint_url' => $server->endpoint_url,
                'wordpress_rest' => [
                    'url' => null,
                    'status' => null,
                    'reachable' => false,
                    'name' => null,
                ],
                'mcp' => [
                    'url' => null,
                    'status' => null,
                    'reachable' => false,
                    'namespace' => null,
                    'routes' => [],
                ],
            ];
        }

        $restUrl = rtrim((string) $server->endpoint_url, '/').'/wp-json/';
        $mcpUrl = rtrim((string) $server->endpoint_url, '/').'/wp-json/mcp/v1';

        $restResponse = Http::withoutVerifying()->timeout(10)->acceptJson()->get($restUrl);
        $mcpResponse = Http::withoutVerifying()->timeout(10)->acceptJson()->get($mcpUrl);
        $mcpPayload = $mcpResponse->json();

        return [
            'server_id' => $server->id,
            'endpoint_url' => $server->endpoint_url,
            'wordpress_rest' => [
                'url' => $restUrl,
                'status' => $restResponse->status(),
                'reachable' => $restResponse->successful(),
                'name' => $restResponse->json('name'),
            ],
            'mcp' => [
                'url' => $mcpUrl,
                'status' => $mcpResponse->status(),
                'reachable' => $mcpResponse->successful(),
                'namespace' => data_get($mcpPayload, 'namespace'),
                'routes' => array_keys((array) data_get($mcpPayload, 'routes', [])),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function probeLaGeriaJsonRpc(Server $server): array
    {
        $this->mcpClient->setServer($server);

        $tools = $this->mcpClient->listJsonRpcTools();
        $endpointInfo = $this->mcpClient->callJsonRpcTool('remote-endpoint-info');
        $services = $this->mcpClient->callJsonRpcTool('lageria-latepoint-list-services', [
            'input' => ['per_page' => 20],
        ]);
        $upcomingBookings = $this->mcpClient->callJsonRpcTool('lageria-latepoint-upcoming-bookings', [
            'input' => ['per_page' => 3],
        ]);
        $products = $this->mcpClient->callJsonRpcTool('lageria-woo-products', [
            'query' => ['per_page' => 8, 'status' => 'publish'],
        ]);

        $remoteEndpoint = (string) data_get($endpointInfo, 'remote_endpoint', '');

        return [
            'server_id' => $server->id,
            'endpoint_url' => $server->endpoint_url,
            'wordpress_rest' => [
                'url' => $remoteEndpoint !== '' ? rtrim($remoteEndpoint, '/').'/wp-json/' : null,
                'reachable' => data_get($endpointInfo, 'remote_endpoint') !== null,
                'name' => data_get($endpointInfo, 'business'),
            ],
            'mcp' => [
                'url' => $server->endpoint_url,
                'reachable' => $tools !== [],
                'tools' => $this->compactMcpTools($tools),
            ],
            'latepoint' => [
                'services' => $this->compactLatePointServices($services),
                'upcoming_bookings' => $this->compactLatePointBookings($upcomingBookings),
            ],
            'woocommerce' => [
                'products' => $this->compactWooProducts($products),
            ],
        ];
    }

    private function isJsonRpcMcpEndpoint(string $endpointUrl): bool
    {
        return str_contains($endpointUrl, '/mcp/');
    }

    /**
     * @param  array<int, array<string, mixed>>  $tools
     * @return array<int, array<string, mixed>>
     */
    private function compactMcpTools(array $tools): array
    {
        return collect($tools)
            ->map(fn (array $tool): array => [
                'name' => (string) ($tool['name'] ?? ''),
                'title' => (string) ($tool['title'] ?? ''),
                'description' => (string) ($tool['description'] ?? ''),
            ])
            ->filter(fn (array $tool): bool => $tool['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function compactLatePointServices(array $payload): array
    {
        $services = data_get($payload, 'data.services', data_get($payload, 'services', []));

        if (! is_array($services)) {
            return [];
        }

        return collect($services)
            ->take(10)
            ->map(fn (array $service): array => [
                'id' => $service['id'] ?? null,
                'name' => (string) ($service['name'] ?? ''),
                'status' => (string) ($service['status'] ?? ''),
                'duration' => $service['duration'] ?? null,
                'price' => $service['price'] ?? null,
                'capacity_max' => $service['capacity_max'] ?? null,
            ])
            ->filter(fn (array $service): bool => $service['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function compactLatePointBookings(array $payload): array
    {
        $bookings = data_get($payload, 'data.bookings', data_get($payload, 'bookings', []));

        if (! is_array($bookings)) {
            return [];
        }

        return collect($bookings)
            ->take(3)
            ->map(fn (array $booking): array => [
                'id' => $booking['id'] ?? null,
                'status' => (string) ($booking['status'] ?? ''),
                'service_name' => (string) ($booking['service_name'] ?? ''),
                'start_date' => (string) ($booking['start_date'] ?? ''),
                'start_time' => $booking['start_time'] ?? null,
                'duration' => $booking['duration'] ?? null,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function compactWooProducts(array $payload): array
    {
        $products = data_get($payload, 'data', []);

        if (! is_array($products)) {
            return [];
        }

        return collect($products)
            ->take(8)
            ->map(fn (array $product): array => [
                'id' => $product['id'] ?? null,
                'name' => (string) ($product['name'] ?? ''),
                'status' => (string) ($product['status'] ?? ''),
                'price' => (string) ($product['price'] ?? ''),
                'permalink' => (string) ($product['permalink'] ?? ''),
                'latepoint_linked' => $this->wooProductHasLatePointMetadata($product),
            ])
            ->filter(fn (array $product): bool => $product['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $product
     */
    private function wooProductHasLatePointMetadata(array $product): bool
    {
        $meta = $product['meta_data'] ?? [];

        if (! is_array($meta)) {
            return false;
        }

        return collect($meta)->contains(function (mixed $row): bool {
            return is_array($row)
                && (string) ($row['key'] ?? '') === '_is_latepoint_product'
                && (string) ($row['value'] ?? '') === 'yes';
        });
    }

    /**
     * @param  array<string, mixed>|null  $sirvo
     * @param  array<string, mixed>|null  $lageria
     * @param  array<int, array<string, mixed>>  $crossSellingSuggestions
     */
    private function buildCustomerReply(
        array $conversation,
        string $message,
        ?array $sirvo,
        ?array $lageria,
        ?array $reservationCheck = null,
        array $knowledge = [],
        ?string $contextSuggestion = null,
        ?string $previousDetailsSuggestion = null,
        array $crossSellingSuggestions = [],
        string $channel = 'api'
    ): string {
        // Always use the structured initial menu for selecting_intent (ensures clean numbered choices and saves tokens)
        if ($conversation['stage'] === 'selecting_intent') {
            $reply = $this->buildIntentSelectionReply();
            if ($contextSuggestion !== null) {
                $reply .= ' '.$contextSuggestion;
            }

            return $reply;
        }

        // Try AI generation first if configured AND not a simple interaction (saves tokens and improves speed)
        $aiEnabled = config('openai.api_key') !== null && config('openai.api_key') !== '';
        $isSimple = (bool) ($conversation['is_simple'] ?? false);

        // Use predefined responses for taxi_booking and ready_to_confirm stages
        $usePredefined = in_array($conversation['intent'], ['taxi_booking', 'restaurant_booking', 'winery_visit', 'product_purchase'], true)
            || in_array($conversation['stage'], ['ready_to_confirm', 'collecting_taxi_details'], true);

        // Intercept listing queries — call real MCP tools so AI cannot hallucinate
        if ($conversation['intent'] === 'commercial_info') {
            $liveReply = $this->buildLiveListingReply($message);
            if ($liveReply !== null) {
                return $liveReply;
            }
        }

        if ($aiEnabled && ! $isSimple && ! $usePredefined) {
            try {
                $context = [
                    'sirvo' => $sirvo,
                    'la_geria' => $lageria,
                    'taxilanz' => $taxilanz,


                    'reservation_check' => $reservationCheck,
                    'knowledge' => $knowledge,
                    'context_suggestion' => $contextSuggestion,
                    'previous_details_suggestion' => $previousDetailsSuggestion,
                    'cross_selling_suggestions' => $crossSellingSuggestions,
                ];

                return $this->aiService->generateResponse($message, $conversation, $context);
            } catch (\Throwable $exception) {
                Log::error('AI response generation failed, falling back to rule-based', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $sirvoReady = (bool) data_get($sirvo, 'config.reachable') && (bool) data_get($sirvo, 'branches.reachable');
        $lageriaReady = (bool) data_get($lageria, 'wordpress_rest.reachable') && (bool) data_get($lageria, 'mcp.reachable');

        if ($conversation['intent'] === 'commercial_info') {
            return $this->buildCommercialInfoReplyWithCrossSelling($knowledge, $message, $crossSellingSuggestions);
        }

        if ($conversation['intent'] === 'product_purchase') {
            return $this->buildProductPurchaseReply($knowledge, $message, $crossSellingSuggestions);
        }

        if ($conversation['intent'] === 'system_info') {
            return $this->buildSystemInfoReply($message);
        }

        if ($conversation['stage'] === 'awaiting_customer_name') {
            $baseReply = $this->buildAwaitingNameReply($conversation, $reservationCheck);

            // Add context suggestion if available
            if ($previousDetailsSuggestion !== null) {
                return $baseReply.' '.$previousDetailsSuggestion;
            }

            return $baseReply;
        }

        if ($conversation['stage'] === 'contact_details_received') {
            return $this->buildContactDetailsReceivedReply($conversation);
        }

        if ($conversation['stage'] === 'collecting_booking_details') {
            // If we already have contact details, mention them in the reply
            if ($conversation['customer_name'] !== null && $conversation['customer_phone'] !== null) {
                $reply = $this->buildCollectingDetailsReplyWithContact($conversation, $sirvoReady, $lageriaReady, $channel);
            } else {
                $reply = $this->buildCollectingDetailsReply($conversation, $sirvoReady, $lageriaReady);
            }

            // Add context suggestion if available
            if ($previousDetailsSuggestion !== null) {
                $reply .= ' '.$previousDetailsSuggestion;
            }

            return $reply;
        }

        if ($conversation['stage'] === 'selecting_visit_time') {
            return $this->buildVisitTimeSelectionReply($conversation);
        }

        if ($conversation['stage'] === 'intent_confirmed') {
            return $this->buildIntentConfirmedReply($conversation);
        }

        if ($conversation['stage'] === 'ready_to_confirm') {
            $reply = $this->buildReadyToConfirmReply($conversation, $reservationCheck);

            // Add cross-selling suggestion
            if (! empty($crossSellingSuggestions)) {
                $randomSuggestion = $crossSellingSuggestions[array_rand($crossSellingSuggestions)];
                $reply .= ' '.$randomSuggestion['message'];
            }

            return $reply;
        }

        if ($conversation['stage'] === 'booking_confirmed') {
            return $this->buildBookingConfirmedReply($conversation);
        }

        if ($conversation['stage'] === 'collecting_taxi_details') {
            return $this->buildTaxiDetailsReply($conversation);
        }

        if ($conversation['stage'] === 'lanzaloe_purchase') {
            return $this->buildLanzaloePurchaseReply($conversation);
        }

        if ($conversation['stage'] === 'lageria_purchase') {
            return $this->buildLageriaPurchaseReply($conversation);
        }

        if ($conversation['stage'] === 'selecting_intent') {
            $reply = $this->buildIntentSelectionReply();

            // Add context suggestion if available
            if ($contextSuggestion !== null) {
                $reply .= ' '.$contextSuggestion;
            }

            return $reply;
        }

        return $this->buildDefaultReply($conversation);
    }

    private function buildAwaitingNameReply(array $conversation, ?array $reservationCheck): string
    {
        if ($conversation['intent'] === 'winery_visit') {
            return sprintf(
                'Perfecto 🍷 Tengo estos datos para la visita: %s %s %s para %s personas. ¿A nombre de quién la preparo?',
                (string) $conversation['date_label'],
                $this->formatDateForReply($conversation),
                $this->formatTimeForReply((string) $conversation['time_label']),
                (string) $conversation['party_size'],
            );
        }

        if (! ((bool) data_get($reservationCheck, 'checked') && (bool) data_get($reservationCheck, 'available'))) {
            return sprintf(
                'Perfecto, tengo estos datos: %s %s %s para %s personas. ¿Alguna alergia o preferencia? ¿Y a nombre de quién la preparo?',
                (string) $conversation['date_label'],
                $this->formatDateForReply($conversation),
                $this->formatTimeForReply((string) $conversation['time_label']),
                (string) $conversation['party_size'],
            );
        }

        return sprintf(
            'Perfecto, tengo estos datos: %s %s %s para %s personas. ¿A nombre de quién la preparo?',
            (string) $conversation['date_label'],
            $this->formatDateForReply($conversation),
            $this->formatTimeForReply((string) $conversation['time_label']),
            (string) $conversation['party_size'],
        );
    }

    private function buildContactDetailsReceivedReply(array $conversation): string
    {
        $name = (string) $conversation['customer_name'];
        $phone = (string) $conversation['customer_phone'];
        $service = $this->getServiceNameForIntent($conversation['intent']);
        $date = (string) $conversation['date_label'];
        $time = (string) $conversation['time_label'];
        $partySize = (string) $conversation['party_size'];

        return sprintf(
            'Gracias %s. He recibido tu contacto: %s. Confirmo los datos para %s: %s %s para %s personas. ¿Procedo con la reserva?',
            $name,
            $phone,
            $service,
            $date,
            $this->formatTimeForReply($time),
            $partySize,
        );
    }

    private function getServiceNameForIntent(string $intent): string
    {
        return match ($intent) {
            'winery_visit' => 'Visita Guiada',
            'restaurant_booking' => 'reserva de mesa',
            'taxi_booking' => 'taxi',
            default => 'reserva',
        };
    }

    private function buildCollectingDetailsReply(array $conversation, bool $sirvoReady, bool $lageriaReady): string
    {
        $missing = $conversation['missing_labels'];
        $intent = $conversation['intent'];

        if (in_array('día', $missing, true)) {
            return match ($intent) {
                'restaurant_booking' => '¿Para qué día te apetece reservar? ¿Mañana, pasado mañana, o tienes otra fecha en mente?',
                'winery_visit' => '¿Qué día te viene bien para la visita? ¿Mañana por la mañana o prefieres otro día?',
                'taxi_booking' => '¿Para cuándo necesitas el taxi?',
                default => '¿Para qué día?',
            };
        }

        if (in_array('hora', $missing, true)) {
            return match ($intent) {
                'restaurant_booking' => '¿A qué hora te viene bien? ¿Te sirve mañana por la tarde o prefieres otro horario?',
                'winery_visit' => '¿Prefieres por la mañana (11:00) o por la tarde (16:00)?',
                'taxi_booking' => '¿A qué hora necesitas el taxi?',
                default => '¿A qué hora?',
            };
        }

        if (in_array('número de personas', $missing, true)) {
            return match ($intent) {
                'restaurant_booking' => '¿Para cuántas personas es la reserva?',
                'winery_visit' => '¿Para cuántas personas es la visita?',
                'taxi_booking' => '¿Para cuántas personas es el taxi?',
                default => '¿Para cuántas personas?',
            };
        }

        return '¿Qué más detalles necesitas?';
    }

    private function buildCollectingDetailsReplyWithContact(array $conversation, bool $sirvoReady, bool $lageriaReady, string $channel = 'api'): string
    {
        $missing = $conversation['missing_labels'];
        $intent = $conversation['intent'];
        $name = (string) $conversation['customer_name'];

        if (in_array('día', $missing, true)) {
            return match ($intent) {
                'restaurant_booking' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Para qué día te apetece reservar?",
                'winery_visit' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Qué día te viene bien para la visita?",
                'taxi_booking' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Para cuándo necesitas el taxi?",
                default => '¿Para qué día?',
            };
        }

        if (in_array('hora', $missing, true)) {
            if ($intent === 'restaurant_booking' && $channel === 'whatsapp') {
                // Use interactive buttons for WhatsApp restaurant time selection
                $this->sendRestaurantTimeButtons($conversation['customer_phone'] ?? '', $name);
                return "Perfecto {$name}, ya tengo tus datos de contacto. Selecciona una hora de las opciones disponibles:";
            }

            return match ($intent) {
                'restaurant_booking' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿A qué hora te viene bien?",
                'winery_visit' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Prefieres por la mañana (11:00) o por la tarde (16:00)?",
                'taxi_booking' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿A qué hora necesitas el taxi?",
                default => '¿A qué hora?',
            };
        }

        if (in_array('número de personas', $missing, true)) {
            if ($intent === 'restaurant_booking' && $channel === 'whatsapp') {
                // Use interactive buttons for WhatsApp restaurant party size selection
                $this->sendRestaurantPartySizeButtons($conversation['customer_phone'] ?? '', $name);
                return "Perfecto {$name}, ya tengo tus datos de contacto. Selecciona el número de personas:";
            }

            return match ($intent) {
                'restaurant_booking' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Vendréis solos o sois varios?",
                'winery_visit' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Sois un grupo grande o sois pocos?",
                'taxi_booking' => "Perfecto {$name}, ya tengo tus datos de contacto. ¿Para cuántas personas es el taxi?",
                default => '¿Para cuántas personas?',
            };
        }

        return '¿Qué más detalles necesitas?';
    }

    private function sendRestaurantTimeButtons(string $phone, string $name): void
    {
        $buttons = [
            ['id' => 'time_12:00', 'title' => '12:00'],
            ['id' => 'time_13:00', 'title' => '13:00'],
            ['id' => 'time_14:00', 'title' => '14:00'],
            ['id' => 'time_19:00', 'title' => '19:00'],
            ['id' => 'time_20:00', 'title' => '20:00'],
            ['id' => 'time_21:00', 'title' => '21:00'],
        ];

        $this->whatsappService->sendReplyButtons(
            $phone,
            "Perfecto {$name}, ya tengo tus datos de contacto. Selecciona una hora de las opciones disponibles:",
            $buttons,
            'Puedes escribir otra hora si prefieres'
        );
    }

    private function sendRestaurantPartySizeButtons(string $phone, string $name): void
    {
        $buttons = [
            ['id' => 'party_1', 'title' => '1 persona'],
            ['id' => 'party_2', 'title' => '2 personas'],
            ['id' => 'party_3', 'title' => '3 personas'],
            ['id' => 'party_4', 'title' => '4 personas'],
            ['id' => 'party_5', 'title' => '5 personas'],
            ['id' => 'party_6+', 'title' => '6+ personas'],
        ];

        $this->whatsappService->sendReplyButtons(
            $phone,
            "Perfecto {$name}, ya tengo tus datos de contacto. Selecciona el número de personas:",
            $buttons,
            'Puedes escribir el número exacto si es más de 6'
        );
    }

    /**
     * @return array<int, string>
     */
    private function availableVisitSlots(array $conversation, ?array $lageria): array
    {
        $date = (string) data_get($conversation, 'date.value', '');
        $bookedTimes = collect((array) data_get($lageria, 'latepoint.upcoming_bookings', []))
            ->filter(fn (array $booking): bool => (string) ($booking['start_date'] ?? '') === $date)
            ->map(fn (array $booking): string => substr((string) ($booking['start_time'] ?? ''), 0, 5))
            ->filter()
            ->values()
            ->all();

        return collect(['10:00', '11:00', '12:00', '16:00'])
            ->reject(fn (string $slot): bool => in_array($slot, $bookedTimes, true))
            ->values()
            ->all();
    }

    private function buildVisitTimeSelectionReply(array $conversation): string
    {
        $slots = (array) data_get($conversation, 'context.visit_slots', []);
        $date = (string) ($conversation['date_label'] ?? 'el día indicado');
        $partySize = (string) ($conversation['party_size'] ?? 'las');

        if ($slots === []) {
            return sprintf(
                'He consultado La Geria y no veo horas disponibles para %s para %s personas. ¿Quieres probar otro día?',
                $date,
                $partySize,
            );
        }

        $lines = [
            sprintf('He consultado La Geria. Para %s para %s personas hay estas horas disponibles:', $date, $partySize),
            '',
        ];

        foreach ($slots as $index => $slot) {
            $lines[] = sprintf('%d) %s', $index + 1, (string) $slot);
        }

        $lines[] = '';
        $lines[] = 'Responde con el número de la hora que prefieres.';

        return implode("\n", $lines);
    }

    private function buildIntentConfirmedReply(array $conversation): string
    {
        $intent = $conversation['intent'];
        $date = (string) $conversation['date_label'];
        $time = (string) $conversation['time_label'];
        $partySize = (string) $conversation['party_size'];

        $summary = match ($intent) {
            'restaurant_booking' => sprintf(
                'reserva en restaurante %s %s para %s personas',
                $date,
                $this->formatTimeForReply($time),
                $partySize,
            ),
            'winery_visit' => sprintf(
                'visita a la bodega %s %s para %s personas',
                $date,
                $this->formatTimeForReply($time),
                $partySize,
            ),
            'taxi_booking' => sprintf(
                'traslado de %s a %s %s %s para %s personas',
                (string) ($conversation['origin'] ?? 'origen indicado'),
                (string) ($conversation['destination'] ?? 'destino indicado'),
                $date,
                $this->formatTimeForReply($time),
                $partySize,
            ),
            default => 'tu reserva',
        };

        $configured = $this->configuredConversationReply("stages.intent_confirmed.{$intent}.reply", $conversation, [
            'summary' => $summary,
        ]);

        if ($configured !== null) {
            return $configured;
        }

        return sprintf(
            '¡Perfecto! Tengo apuntado tu %s. Para enviarte el enlace de pago y la confirmación, necesito tu nombre, email y teléfono.',
            $summary,
        );
    }

    private function buildReadyToConfirmReply(array $conversation, ?array $reservationCheck): string
    {
        $intent = $conversation['intent'];
        $date = (string) $conversation['date_label'];
        $time = (string) $conversation['time_label'];
        $partySize = (string) $conversation['party_size'];
        $expectsTaxi = (bool) data_get($conversation, 'context.expects_taxi', false);

        $configured = $this->configuredConversationReply("stages.ready_to_confirm.{$intent}.reply", $conversation);

        if ($configured !== null) {
            return $configured;
        }

        return match ($intent) {
            'restaurant_booking' => sprintf(
                $expectsTaxi
                    ? "¡Genial! Tengo apuntado restaurante %s %s %s para %s personas. Como elegiste restaurante + taxi, seguimos con el traslado.\n\n1) Confirmar solo restaurante\n2) Continuar con taxi del paquete\n3) Info productos aloe vera\n\nResponde con el número de tu elección."
                    : "¡Genial! Tengo apuntado %s %s %s para %s personas.\n\n1) Confirmar reserva\n2) Añadir taxi\n3) Info productos aloe vera\n\nResponde con el número de tu elección.",
                $date,
                $this->formatDateForReply($conversation),
                $this->formatTimeForReply($time),
                $partySize,
            ),
            'winery_visit' => sprintf(
                $expectsTaxi
                    ? "¡Perfecto! Visita bodega %s %s %s para %s personas. Como elegiste visita + taxi, seguimos con el traslado.\n\n1) Confirmar solo visita\n2) Continuar con taxi del paquete\n3) Añadir cena en Taberna La Cepa\n4) Info productos aloe vera\n\nResponde con el número de tu elección."
                    : "¡Perfecto! Visita bodega %s %s %s para %s personas.\n\n1) Confirmar visita\n2) Añadir taxi\n3) Añadir cena en Taberna La Cepa\n4) Info productos aloe vera\n\nResponde con el número de tu elección.",
                $date,
                $this->formatDateForReply($conversation),
                $this->formatTimeForReply($time),
                $partySize,
            ),
            'taxi_booking' => sprintf(
                "¡Perfecto! Taxi de %s a %s %s %s para %s personas.\n\n1) Confirmar taxi\n\nResponde con 1 para confirmar.",
                (string) ($conversation['origin'] ?? 'origen indicado'),
                (string) ($conversation['destination'] ?? 'destino indicado'),
                $date,
                $this->formatTimeForReply($time),
                $partySize,
            ),
            default => '¿Confirmamos?',
        };
    }

    private function buildBookingConfirmedReply(array $conversation): string
    {
        if ($conversation['intent'] === 'taxi_booking') {
            // Check if this is adding taxi to an existing visit (package flow)
            $originalIntent = data_get($conversation, 'context.original_intent');
            if (in_array($originalIntent, ['winery_visit', 'restaurant_booking'], true)) {
                try {
                    $originalService = $this->serviceForPackageOriginalIntent((string) $originalIntent);
                    $taxiService = $this->transferServiceForPackage();
                    $originalServiceId = data_get($conversation, 'context.original_service_id') ?: $originalService?->getKey();
                    $originalServiceName = data_get($conversation, 'context.original_service_name') ?: $originalService?->name ?: 'Visita';
                    $originalUnitPrice = (float) data_get($conversation, 'context.original_unit_price', data_get($conversation, 'original_unit_price', $originalService instanceof Tour ? $originalService->base_price : 15));
                    $originalDate = data_get($conversation, 'context.original_date');
                    $originalTime = data_get($conversation, 'context.original_time');
                    $originalPartySize = data_get($conversation, 'context.original_party_size', 1);

                    $taxiServiceId = data_get($conversation, 'service_id') ?: $taxiService?->getKey();
                    $taxiOrigin = data_get($conversation, 'origin', 'origen indicado');
                    $taxiDestination = data_get($conversation, 'destination', 'destino indicado');
                    $taxiDate = data_get($conversation, 'date');
                    $taxiTime = data_get($conversation, 'time');
                    $taxiPartySize = data_get($conversation, 'party_size', 1);

                    // Build package items
                    $items = [];
                    $taxiUnitPrice = (float) ($taxiService?->base_price ?? data_get($conversation, 'unit_price', 0));

                    // Add visit item
                    if ($originalServiceId && $originalDate && $originalTime) {
                        $items[] = [
                            'item_type' => $originalIntent === 'winery_visit' ? 'winery_visit' : 'restaurant',
                            'service_id' => $originalServiceId,
                            'service_name' => $originalServiceName,
                            'quantity' => (int) $originalPartySize,
                            'unit_price' => $originalUnitPrice,
                            'currency' => 'EUR',
                            'starts_at' => CarbonImmutable::parse($originalDate.' '.$originalTime, 'Europe/Madrid')->toIso8601String(),
                            'metadata' => [
                                'origin' => null,
                                'destination' => $originalServiceName,
                            ],
                        ];
                    }

                    // Add taxi item
                    if ($taxiDate && $taxiTime) {
                        $items[] = [
                            'item_type' => 'transfer',
                            'service_id' => $taxiServiceId,
                            'service_name' => $taxiService?->name ?? 'Taxilanz Transfer',
                            'quantity' => (int) $taxiPartySize,
                            'unit_price' => (float) $taxiUnitPrice,
                            'currency' => 'EUR',
                            'starts_at' => CarbonImmutable::parse($taxiDate.' '.$taxiTime, 'Europe/Madrid')->toIso8601String(),
                            'metadata' => [
                                'origin' => $taxiOrigin,
                                'destination' => $taxiDestination,
                                'passengers' => (int) $taxiPartySize,
                            ],
                        ];
                    }

                    if (count($items) === 2) {
                        $package = app(CreatePackageBookingRequest::class)->handle([
                            'customer_name' => data_get($conversation, 'customer_name', 'Cliente'),
                            'customer_email' => data_get($conversation, 'customer_email'),
                            'customer_phone' => data_get($conversation, 'customer_phone'),
                            'items' => $items,
                            'discount_percent' => 10,
                        ]);

                        $checkoutUrl = route('public.redsys.start', ['request' => $package->id]);

                        return sprintf(
                            "¡Perfecto! Tengo preparado tu paquete:\n- %s %s %s para %s personas\n- Taxi de %s a %s %s %s para %s personas\n\nDescuento del 10%% aplicado.\n\n🔗 [Pago] %s",
                            $originalServiceName,
                            (string) ($conversation['date_label'] ?? 'fecha indicada'),
                            $this->formatTimeForReply((string) ($originalTime ?? 'hora indicada')),
                            (string) $originalPartySize,
                            $taxiOrigin,
                            $taxiDestination,
                            (string) ($conversation['date_label'] ?? 'fecha indicada'),
                            $this->formatTimeForReply((string) ($taxiTime ?? 'hora indicada')),
                            (string) $taxiPartySize,
                            $checkoutUrl,
                        );
                    }
                } catch (\Throwable $exception) {
                    Log::error('Nova package booking request creation failed', [
                        'conversation' => $conversation,
                        'error' => $exception->getMessage(),
                    ]);
                }
            }

            // Fallback to taxi-only booking
            try {
                $bookingRequest = app(CreateTransferBookingRequestFromNovaConversation::class)->handle($conversation);

                // If booking is already paid, confirm without payment link
                if ($bookingRequest->payment_status === 'paid') {
                    return sprintf(
                        '¡Perfecto! Tu traslado de %s a %s %s %s para %s personas ya está confirmado y pagado. Te enviaré la confirmación operativa a %s.',
                        (string) ($conversation['origin'] ?? 'origen indicado'),
                        (string) ($conversation['destination'] ?? 'destino indicado'),
                        (string) ($conversation['date_label'] ?? 'fecha indicada'),
                        $this->formatTimeForReply((string) ($conversation['time_label'] ?? 'hora indicada')),
                        (string) ($conversation['party_size'] ?? 'las'),
                        $bookingRequest->customer_email ?? 'tu correo',
                    );
                }

                $checkoutUrl = route('public.redsys.start', ['request' => $bookingRequest->id]);
            } catch (\Throwable $exception) {
                Log::error('Nova transfer booking request creation failed', [
                    'conversation' => $conversation,
                    'error' => $exception->getMessage(),
                ]);

                return 'Perfecto, tengo todos los datos del traslado confirmados, pero no he podido generar el enlace de pago automáticamente. La dejo marcada para revisión manual.';
            }

            return sprintf(
                "Perfecto, tengo preparado el traslado de %s a %s %s %s para %s personas.\n\n🔗 [Pago] %s",
                (string) ($conversation['origin'] ?? 'origen indicado'),
                (string) ($conversation['destination'] ?? 'destino indicado'),
                (string) ($conversation['date_label'] ?? 'fecha indicada'),
                $this->formatTimeForReply((string) ($conversation['time_label'] ?? 'hora indicada')),
                (string) ($conversation['party_size'] ?? 'las'),
                $checkoutUrl,
            );
        }

        if ($conversation['intent'] === 'restaurant_booking') {
            return sprintf(
                '¡Perfecto! Tu reserva en restaurante %s %s para %s personas está confirmada. Te enviaré la confirmación operativa a %s.',
                (string) ($conversation['date_label'] ?? 'fecha indicada'),
                $this->formatTimeForReply((string) ($conversation['time_label'] ?? 'hora indicada')),
                (string) ($conversation['party_size'] ?? 'las'),
                $conversation['customer_email'] ?? 'tu correo',
            );
        }

        if ($conversation['intent'] === 'winery_visit') {
            try {
                $bookingRequest = app(CreateWineryVisitBookingRequestFromNovaConversation::class)->handle($conversation);

                // If booking is already paid, confirm without payment link
                if ($bookingRequest->payment_status === 'paid') {
                    return sprintf(
                        '¡Perfecto! Tu visita a la bodega %s %s para %s personas ya está confirmada y pagada. Te enviaré la confirmación operativa a %s.',
                        (string) ($conversation['date_label'] ?? 'fecha indicada'),
                        $this->formatTimeForReply((string) ($conversation['time_label'] ?? 'hora indicada')),
                        (string) ($conversation['party_size'] ?? 'las'),
                        $bookingRequest->customer_email ?? 'tu correo',
                    );
                }

                $checkoutUrl = route('public.redsys.start', ['request' => $bookingRequest->id]);
            } catch (\Throwable $exception) {
                Log::error('Nova winery visit booking request creation failed', [
                    'conversation' => $conversation,
                    'error' => $exception->getMessage(),
                ]);

                return 'Perfecto, tengo todos los datos de la visita confirmados, pero no he podido generar el enlace de pago automáticamente. La dejo marcada para revisión manual.';
            }

            return sprintf(
                "¡Perfecto! Tengo preparada tu visita a la bodega %s %s para %s personas.\n\n🔗 [Pago] %s",
                (string) ($conversation['date_label'] ?? 'fecha indicada'),
                $this->formatTimeForReply((string) ($conversation['time_label'] ?? 'hora indicada')),
                (string) ($conversation['party_size'] ?? 'las'),
                $checkoutUrl,
            );
        }

        return 'Perfecto, dejo la solicitud confirmada y te aviso con la confirmación operativa.';
    }

    private function buildTaxiDetailsReply(array $conversation): string
    {
        $missing = $conversation['missing_labels'];
        $templateKey = match (true) {
            in_array('día', $missing, true) => 'date',
            in_array('hora', $missing, true) => 'time',
            in_array('número de personas', $missing, true) => 'party_size',
            in_array('origen', $missing, true) && in_array('destino', $missing, true) => 'route',
            in_array('origen', $missing, true) => 'origin',
            in_array('destino', $missing, true) => 'destination',
            default => 'fallback',
        };

        $configured = $this->configuredConversationReply("stages.collecting_taxi_details.{$templateKey}.reply", $conversation);

        if ($configured !== null) {
            return $configured;
        }

        if (in_array('día', $missing, true)) {
            return "¿Para cuándo necesitas el taxi?\n\n1) Hoy\n2) Mañana\n3) Otra fecha\n\nResponde con el número o escribe la fecha.";
        }

        if (in_array('hora', $missing, true)) {
            return '¿A qué hora necesitas el taxi? Ejemplo: 11:00';
        }

        if (in_array('número de personas', $missing, true)) {
            return '¿Para cuántas personas es el taxi? Ejemplo: 2';
        }

        if (in_array('origen', $missing, true) && in_array('destino', $missing, true)) {
            return '¿De dónde a dónde necesitas el taxi? Ejemplo: Hotel Fariones a Bodega La Geria';
        }

        if (in_array('origen', $missing, true)) {
            return '¿Desde dónde necesitas que te recojen? Ejemplo: Hotel Fariones';
        }

        if (in_array('destino', $missing, true)) {
            return '¿A dónde necesitas ir? Ejemplo: Bodega La Geria';
        }

        return '¿De dónde a dónde necesitas el taxi?';
    }

    private function buildTaxiOffer(array $conversation): string
    {
        $time = data_get($conversation, 'time.value');
        $business = data_get($conversation, 'business', 'Bodega La Geria');

        if ($time === null) {
            return '';
        }

        try {
            $visitTime = CarbonImmutable::parse($time, 'Europe/Madrid');
            $pickupTime = $visitTime->subMinutes(30);
        } catch (\Throwable $e) {
            return '';
        }

        return sprintf(
            '🚕 Te interesa que un taxi te recoja a las %s y te traslade hasta %s? Solo necesito saber dónde recogerte y te ahorras 10%% en el taxi.',
            $pickupTime->format('H:i'),
            $business,
        );
    }

    private function buildIntentSelectionReply(): string
    {
        return '¿Qué te apetece hacer hoy? Puedo ayudarte con:

1. 🍽️ Reservar mesa en restaurante
2. 🍷 Visitar bodega
3. 🚕 Solicitar taxi
4. 📦 Info de productos La Geria
5. 🍷🚕 Visita + taxi
6. 🍽️🚕 Restaurante + taxi

Escribe el número o el nombre de lo que te interesa.';
    }

    private function buildDefaultReply(array $conversation): string
    {
        return match ($conversation['intent']) {
            'restaurant_booking' => '¿Te gustaría reservar mesa en restaurante? ¿Para cuándo?',
            'winery_visit' => '¿Te interesa visitar una bodega? ¿Prefieres mañana por la mañana o tarde?',
            'taxi_booking' => '¿Necesitas un taxi? ¿Para cuándo y de dónde a dónde?',
            default => '¿En qué puedo ayudarte?',
        };
    }

    /**
     * @param  array<int, array{title:string, content:string}>  $knowledge
     * @param  array<int, array<string, mixed>>  $crossSellingSuggestions
     */
    private function buildSystemInfoReply(string $message): string
    {
        $msg = mb_strtolower($message);
        $wantsStatus = str_contains($msg, 'estado')
            || str_contains($msg, 'conexion')
            || str_contains($msg, 'ping')
            || str_contains($msg, 'funciona')
            || str_contains($msg, 'accesible')
            || str_contains($msg, 'disponible');

        $servers = Server::query()
            ->where('is_active', true)
            ->where('slug', '!=', 'nova')
            ->withCount(['tools' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('name')
            ->get();

        $mcpServers = Server::query()->get()->keyBy('type');

        $filtered = $servers->filter(fn (Server $s) => str_contains($msg, mb_strtolower($s->name))
            || str_contains($msg, mb_strtolower($s->slug)));

        $target = $filtered->isNotEmpty() ? $filtered : $servers;

        if ($target->isEmpty()) {
            return 'En este momento no hay servidores MCP activos conectados a Nova.';
        }

        $lines = [];
        $lines[] = $wantsStatus
            ? 'Estado de conexion de los agentes MCP:'
            : 'Nova tiene '.$target->count().' agente(s) MCP conectado(s):';
        $lines[] = '';

        foreach ($target as $s) {
            $mcpType = str_replace('-', '_', $s->slug);
            $mcp = $mcpServers->get($mcpType)
                ?? $mcpServers->first(fn ($m) => str_contains($mcpType, $m->type) || str_contains($m->type, explode('_', $mcpType)[0]));

            $statusIcon = '⚪';
            $statusText = 'sin datos';
            $liveText = '';

            if ($mcp) {
                $statusIcon = match ($mcp->status) {
                    'active' => '🟢',
                    'error' => '🔴',
                    'draft' => '🟡',
                    default => '⚪',
                };
                $statusText = $mcp->status;

                if ($wantsStatus && $mcp->endpoint_url) {
                    try {
                        $res = Http::timeout(4)->head((string) $mcp->endpoint_url);
                        $liveText = $res->successful() || $res->status() < 500
                            ? ' | live: OK ('.$res->status().')'
                            : ' | live: ERROR ('.$res->status().')';
                    } catch (\Throwable) {
                        $liveText = ' | live: sin respuesta';
                    }
                }
            }

            $checkedAt = $mcp?->last_checked_at
                ? ' (ultimo check: '.Carbon::parse($mcp->last_checked_at)->diffForHumans().')'
                : '';

            $lines[] = "{$statusIcon} {$s->name} — {$s->tools_count} tools{$checkedAt}";

            if ($mcp?->endpoint_url) {
                $lines[] = "   Endpoint: {$mcp->endpoint_url}{$liveText}";
            }

            if ($wantsStatus) {
                $lines[] = "   Estado DB: {$statusText}";
            }
        }

        $lines[] = '';
        $lines[] = $wantsStatus
            ? 'Puedo reintentar la conexion con cualquiera de estos agentes si lo necesitas.'
            : 'Puedo ayudarte a reservar, consultar productos o solicitar informacion de cualquiera de ellos.';

        return implode("\n", $lines);
    }

    /**
     * For listing queries (restaurants, visits, hotels) call real MCP tools instead of AI.
     * Tool names are read from the Nova server metadata (editable in Filament → Servers).
     * Returns null when the message is not a listing query (AI runs normally).
     */
    private function buildLiveListingReply(string $message): ?string
    {
        $msg = mb_strtolower($message);

        $isRestaurant = str_contains($msg, 'restaurante') || str_contains($msg, 'restaurantes')
            || (str_contains($msg, 'comer') && ! str_contains($msg, 'quiero comer en'));
        $isVisit = str_contains($msg, 'visita') || str_contains($msg, 'tour') || str_contains($msg, 'excursion')
            || str_contains($msg, 'actividad') || str_contains($msg, 'ruta');
        $isHotel = str_contains($msg, 'hotel') || str_contains($msg, 'hoteles') || str_contains($msg, 'alojamiento');
        $isListing = str_contains($msg, 'listado') || str_contains($msg, 'lista') || str_contains($msg, 'dame')
            || str_contains($msg, 'muestra') || str_contains($msg, 'hay') || str_contains($msg, 'cuales')
            || str_contains($msg, 'ver') || str_contains($msg, 'obtener') || str_contains($msg, 'disponible')
            || str_contains($msg, 'activos') || str_contains($msg, 'activas') || str_contains($msg, 'activo');
        $isCount = str_contains($msg, 'cuántos') || str_contains($msg, 'cuantos')
            || str_contains($msg, 'cuántas') || str_contains($msg, 'cuantas')
            || str_contains($msg, 'total de') || str_contains($msg, 'el total')
            || str_contains($msg, 'número de') || str_contains($msg, 'numero de')
            || str_contains($msg, 'cuántos hay') || str_contains($msg, 'cuantos hay')
            || str_contains($msg, 'dime el total') || str_contains($msg, 'dame el total');

        // Extract location filter from message: "en Puerto del Carmen", "en Playa Blanca", etc.
        // Stops at a second "en " so "en Taxilanz en Puerto del Carmen" → "Puerto del Carmen"
        $locationFilter = null;
        $systemNames = ['taxilanz', 'sirvo', 'lageria', 'geria', 'lanzaloe', 'lanzarote', 'nova', 'mcp'];
        $stopWords = ['el', 'la', 'los', 'las', 'un', 'una', 'este', 'tiempo', 'real', 'directo'];
        // Find ALL "en X" occurrences and use the last valid one
        preg_match_all('/\ben\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ]*(?:\s+(?:del?|de\s+la|[A-ZÁÉÍÓÚÑ][a-záéíóúñA-ZÁÉÍÓÚÑ]*))*)/u', $message, $matches);
        foreach (array_reverse($matches[1] ?? []) as $candidate) {
            $candidate = trim($candidate);
            $lower = mb_strtolower($candidate);
            if (in_array($lower, $stopWords, true)) {
                continue;
            }
            if (in_array($lower, $systemNames, true)) {
                continue;
            }
            // Must look like a proper place name (starts with capital, reasonable length)
            if (mb_strlen($candidate) >= 3 && mb_strlen($candidate) <= 40) {
                $locationFilter = $candidate;
                break;
            }
        }

        // Read tool names from each server's "use-server" prompt metadata
        // Configurable in Filament → Servers → [Server] → Prompts → use-server → Metadata
        // Keys: listing_tool, listing_intro, listing_cta, listing_params (JSON)

        if ($isRestaurant) {
            $promptMeta = $this->promptMetaFor('sirvo');
            $toolName = $promptMeta['listing_tool'] ?? 'sirvo-restaurantes';
            $intro = $promptMeta['listing_intro'] ?? 'Estos son los negocios gestionados por Sirvo:';
            $cta = $promptMeta['listing_cta'] ?? '¿En cuál te gustaría reservar? Dime nombre, día, hora y número de personas.';

            $raw = $this->callMcpTool($toolName, []);
            $items = $this->unwrapToolResponse($raw);

            if ($isCount) {
                return $this->formatCountReply($items, 'restaurantes', $intro, $locationFilter);
            }

            $lines = [$intro, ''];
            if (! empty($items)) {
                foreach (array_slice($items, 0, 8) as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $name = $item['name'] ?? $item['title'] ?? $item['branch_name'] ?? ($item['id'] ?? '');
                    $group = $item['group'] ?? null;
                    $price = isset($item['precio']) ? $item['precio'].'€' : null;
                    $addr = $item['address'] ?? $item['location'] ?? null;
                    $line = '• **'.$name.'**';
                    $meta = array_filter([$group, $price, $addr]);
                    if ($meta !== []) {
                        $line .= ' — '.implode(' · ', $meta);
                    }
                    $lines[] = $line;
                }
            } else {
                $lines[] = 'Sirvo gestiona varios restaurantes y negocios en Lanzarote.';
                $lines[] = 'En este momento no puedo obtener la lista en tiempo real.';
            }
            $lines[] = '';
            $lines[] = $cta;

            return implode("\n", $lines);
        }

        if ($isVisit && $isListing) {
            $promptMeta = $this->promptMetaFor('geria');
            $toolName = $promptMeta['listing_tool'] ?? 'lageria-latepoint-list-services';
            $intro = $promptMeta['listing_intro'] ?? 'Visitas y tours disponibles:';
            $cta = $promptMeta['listing_cta'] ?? '¿Cuál te interesa? Dime el día y número de personas y te busco disponibilidad.';

            $raw = $this->callMcpTool($toolName, ['input' => ['per_page' => 8]]);
            $list = $this->unwrapToolResponse($raw);

            if ($isCount) {
                return $this->formatCountReply($list, 'visitas', $intro, $locationFilter);
            }

            $lines = [$intro, ''];
            if (! empty($list)) {
                foreach (array_slice($list, 0, 8) as $svc) {
                    if (! is_array($svc)) {
                        continue;
                    }
                    $name = $svc['title'] ?? $svc['name'] ?? '';
                    $dur = isset($svc['duration']) ? $svc['duration'].' min' : null;
                    $price = isset($svc['price']) ? $svc['price'].'€' : null;
                    $line = '• **'.$name.'**';
                    $meta = array_filter([$dur, $price]);
                    if ($meta !== []) {
                        $line .= ' ('.implode(', ', $meta).')';
                    }
                    $lines[] = $line;
                }
            } else {
                $lines[] = 'La Geria ofrece visitas guiadas a la bodega, catas de vino y excursiones.';
                $lines[] = 'En este momento no puedo obtener la lista en tiempo real.';
            }
            $lines[] = '';
            $lines[] = $cta;

            return implode("\n", $lines);
        }

        if ($isHotel) {
            $promptMeta = $this->promptMetaFor('hotel');
            $toolName = $promptMeta['listing_tool'] ?? '';
            $intro = $promptMeta['listing_intro'] ?? 'Hoteles disponibles:';
            $cta = $promptMeta['listing_cta'] ?? 'Dime en cuál te interesa y te gestiono la reserva.';
            $extraParams = isset($promptMeta['listing_params'])
                ? (json_decode($promptMeta['listing_params'], true) ?? [])
                : [];

            if ($toolName !== '') {
                $raw = $this->callMcpTool($toolName, array_merge(['input' => ['per_page' => '20']], $extraParams));
                $items = $this->unwrapToolResponse($raw);

                // Client-side location filter when user says "en Puerto del Carmen", etc.
                if ($locationFilter !== null) {
                    $items = $this->filterItemsByLocation($items, $locationFilter);
                }

                if ($isCount) {
                    return $this->formatCountReply($items, 'hoteles', $intro, $locationFilter);
                }

                // Append location to intro label for listing
                if ($locationFilter !== null) {
                    $intro .= ' (en '.$locationFilter.')';
                }

                $lines = [$intro, ''];
                if (! empty($items)) {
                    $shown = 0;
                    foreach ($items as $item) {
                        if (! is_array($item) || $shown >= 10) {
                            continue;
                        }
                        $name = $item['nombre'] ?? $item['name'] ?? $item['hotel_name'] ?? '';
                        if ($name === '') {
                            continue;
                        }
                        $city = $item['poblacion'] ?? $item['ciudad'] ?? null;
                        $phone = $item['tel_fijo'] ?? $item['telefono'] ?? null;
                        $line = '• **'.$name.'**';
                        $meta = array_filter([$city, $phone]);
                        if ($meta !== []) {
                            $line .= ' — '.implode(' · ', $meta);
                        }
                        $lines[] = $line;
                        $shown++;
                    }
                }
                if (count($lines) <= 2) {
                    $lines[] = 'No se pudo obtener la lista de hoteles en este momento.';
                }
                $lines[] = '';
                $lines[] = $cta;

                return implode("\n", $lines);
            }
        }

        return null;
    }

    /**
     * Unwrap the response from a tool executed via ToolExecutor.
     * Tools return a JSON string with envelope {ok, data, status, ...}.
     * Falls back to treating the raw value as a list if no envelope detected.
     *
     * @return array<int, mixed>
     */
    private function unwrapToolResponse(mixed $raw): array
    {
        if ($raw === null || $raw === false || $raw === '') {
            return [];
        }

        $decoded = is_array($raw) ? $raw : (json_decode((string) $raw, true) ?? []);

        // Handle tool response envelope: {ok, data: {success, data: {tools: [...]}}}
        // e.g. hotel_list → data.data.tools
        if (isset($decoded['data']['data']['tools']) && is_array($decoded['data']['data']['tools'])) {
            return $decoded['data']['data']['tools'];
        }

        // Handle {ok, data: {services|items|results|branches: [...]}}
        foreach (['services', 'items', 'results', 'branches', 'hotels', 'tools'] as $key) {
            if (isset($decoded['data'][$key]) && is_array($decoded['data'][$key])) {
                return $decoded['data'][$key];
            }
        }

        // Handle {ok, data: {data: [...]}} double-nested
        if (isset($decoded['data']['data']) && is_array($decoded['data']['data']) && array_is_list($decoded['data']['data'])) {
            return $decoded['data']['data'];
        }

        // Handle {ok, data:[...]} envelope from handler_code tools
        if (isset($decoded['data']) && is_array($decoded['data']) && array_is_list($decoded['data'])) {
            return $decoded['data'];
        }

        // Handle {services:[...]} or {results:[...]} wrappers
        foreach (['services', 'results', 'items', 'branches', 'hotels'] as $key) {
            if (isset($decoded[$key]) && is_array($decoded[$key])) {
                return $decoded[$key];
            }
        }

        // If root is a list, return it directly
        if (array_is_list($decoded)) {
            return $decoded;
        }

        return [];
    }

    /**
     * Return a natural-language count reply for a listing query.
     *
     * @param  array<int, mixed>  $items
     */
    private function formatCountReply(array $items, string $entityLabel, string $intro, ?string $locationFilter): string
    {
        $count = count(array_filter($items, fn ($i) => is_array($i)));
        $location = $locationFilter ? ' en **'.$locationFilter.'**' : '';

        if ($count === 0) {
            return 'No encontré '.$entityLabel.$location.' en este momento.';
        }

        $verb = $count === 1 ? 'hay' : 'hay';
        $source = rtrim($intro, ':');

        return 'En total '.$verb.' **'.$count.' '.$entityLabel.'**'.$location.'. ('.$source.')';
    }

    /**
     * Filter a list of items by location string against common address fields.
     * Case/accent-insensitive partial match.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, mixed>
     */
    private function filterItemsByLocation(array $items, string $location): array
    {
        $needle = mb_strtolower($location);

        return array_values(array_filter($items, function ($item) use ($needle) {
            if (! is_array($item)) {
                return false;
            }

            foreach (['poblacion', 'ciudad', 'municipio', 'direccion', 'address', 'location'] as $field) {
                $val = mb_strtolower((string) ($item[$field] ?? ''));
                if ($val !== '' && str_contains($val, $needle)) {
                    return true;
                }
            }

            return false;
        }));
    }

    /**
     * Load the "use-server" prompt metadata for a server whose slug contains $slugKeyword.
     * Admins can set listing_tool, listing_intro, listing_cta in Filament:
     * Servers → [Server] → Prompts → use-server → Metadata.
     *
     * @return array<string, mixed>
     */
    private function promptMetaFor(string $slugKeyword): array
    {
        $prompt = Prompt::where('name', 'use-server')
            ->whereHas('server', fn ($q) => $q->where('slug', 'like', '%'.$slugKeyword.'%'))
            ->first();

        return is_array($prompt?->metadata) ? $prompt->metadata : [];
    }

    /**
     * Execute a named MCP tool by loading it from the DB and running it via ToolExecutor.
     */
    private function callMcpTool(string $toolName, array $params): mixed
    {
        try {
            $tool = Tool::where('name', $toolName)->where('is_active', true)->first();
            if (! $tool) {
                return null;
            }

            return $this->toolExecutor->execute($tool, $params);
        } catch (\Throwable $e) {
            Log::warning('callMcpTool failed', ['tool' => $toolName, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function buildProductPurchaseReply(array $knowledge, string $message, array $crossSellingSuggestions): string
    {
        $msg = mb_strtolower($message);
        $isVinoterapia = str_contains($msg, 'vinoterapia') || str_contains($msg, 'vino');
        $isAloe = str_contains($msg, 'aloe') || str_contains($msg, 'cosmético') || str_contains($msg, 'cosmetico') || str_contains($msg, 'crema');

        $lines = [];

        if ($isVinoterapia || $isAloe) {
            $lines[] = '🍷🌿 La vinoterapia de Lanzarote une dos de nuestros mejores productores:';
            $lines[] = '';
            $lines[] = '🌿 **Lanzaloe** — Cosméticos naturales de aloe vera y vinoterapia:';
            $lines[] = '  • Crema antioxidante de vino tinto (extracto de uva malvasía)';
            $lines[] = '  • Aceite corporal de extracto de uva';
            $lines[] = '  • Mascarilla de môst fermentado';
            $lines[] = '  • Gel purificante de aloe vera con resveratrol';
            $lines[] = '';
            $lines[] = '🍷 **La Geria** — Vinos que se usan como ingrediente en los productos de Lanzaloe:';
            $lines[] = '  • Malvasía seco — base de la línea antioxidante';
            $lines[] = '  • Moscatel — nota dulce en aceites corporales';
            $lines[] = '  • Listán negro (tinto) — extracto en mascarillas';
        } else {
            if ($knowledge !== []) {
                $fragments = [];
                foreach (array_slice($knowledge, 0, 2) as $item) {
                    $content = trim((string) $item['content']);
                    if ($content !== '') {
                        $fragments[] = '• '.$this->compactKnowledgeText($content, $message);
                    }
                }
                if ($fragments !== []) {
                    $lines[] = '📦 Productos disponibles:
'.implode('
', $fragments);
                }
            } else {
                $lines[] = '📦 Tenemos productos de La Geria (vinos y conservas) y Lanzaloe (aloe vera y vinoterapia). ¿Qué te interesa más?';
            }
        }

        if (! empty($crossSellingSuggestions)) {
            $lines[] = '';
            $lines[] = $crossSellingSuggestions[0]['message'];
        }

        $lines[] = '';
        $lines[] = '¿Te envío el enlace a la tienda de Lanzaloe o de La Geria para que puedas comprar?';
        $lines[] = '';
        $lines[] = '1) Comprar en Lanzaloe';
        $lines[] = '2) Comprar en La Geria';
        $lines[] = '';
        $lines[] = 'Responde con el número de tu elección.';

        return implode('
', $lines);
    }

    private function serviceForPackageOriginalIntent(string $intent): Tour|Restaurant|null
    {
        if ($intent === 'winery_visit') {
            return Tour::query()
                ->where('is_active', true)
                ->whereHas('externalSyncMappings', fn ($query) => $query->where('source_platform', 'latepoint')->whereIn('resource_type', ['tour_visit', 'tour', 'service']))
                ->orderBy('id')
                ->first();
        }

        if ($intent === 'restaurant_booking') {
            return Restaurant::query()
                ->where('is_active', true)
                ->where(fn ($query) => $query
                    ->where('restaurant_name', 'like', '%Cepa%')
                    ->orWhere('name', 'like', '%Cepa%'))
                ->orderByDesc('is_featured')
                ->orderBy('id')
                ->first();
        }

        return null;
    }

    private function transferServiceForPackage(): ?Tour
    {
        return Tour::query()
            ->where('is_active', true)
            ->whereHas('externalSyncMappings', fn ($query) => $query->where('source_platform', 'woo')->where('resource_type', 'tour_route'))
            ->orderBy('id')
            ->first();
    }

    private function buildLanzaloePurchaseReply(array $conversation): string
    {
        $lines = [];
        $lines[] = '🌿 **Compra en Lanzaloe**';
        $lines[] = '';
        $lines[] = 'Puedes comprar productos de Lanzaloe directamente en su tienda online:';
        $lines[] = '';
        $lines[] = '🔗 https://www.lanzaloe.com/es/';
        $lines[] = '';
        $lines[] = '¿Quieres que te ayude a:';
        $lines[] = '1) Buscar un producto específico';
        $lines[] = '2) Ver categorías de productos';
        $lines[] = '3) Volver al menú principal';
        $lines[] = '';
        $lines[] = 'Responde con el número de tu elección.';

        return implode('
', $lines);
    }

    private function buildLageriaPurchaseReply(array $conversation): string
    {
        $lines = [];
        $lines[] = '🍷 **Compra en La Geria**';
        $lines[] = '';
        $lines[] = 'Puedes comprar vinos y productos de La Geria directamente en su tienda:';
        $lines[] = '';
        $lines[] = '🔗 https://lageriawp.test/tienda/';
        $lines[] = '';
        $lines[] = '¿Quieres que te ayude a:';
        $lines[] = '1) Buscar un vino específico';
        $lines[] = '2) Ver categorías de vinos';
        $lines[] = '3) Volver al menú principal';
        $lines[] = '';
        $lines[] = 'Responde con el número de tu elección.';

        return implode('
', $lines);
    }

    private function buildCommercialInfoReplyWithCrossSelling(array $knowledge, string $message, array $crossSellingSuggestions): string
    {
        $reply = $this->buildCommercialInfoReply($knowledge, $message);

        // Add cross-selling suggestion
        if (! empty($crossSellingSuggestions)) {
            $randomSuggestion = $crossSellingSuggestions[array_rand($crossSellingSuggestions)];
            $reply .= ' '.$randomSuggestion['message'];
        }

        return $reply;
    }

    /**
     * @param  array<int, array{title:string, content:string}>  $knowledge
     */
    private function buildCommercialInfoReply(array $knowledge, string $message): string
    {
        if ($knowledge === []) {
            return "Claro 😊 ¿Sobre qué quieres información?\n1. Restaurantes y comida\n2. Visitas guiadas / bodegas\n3. Taxis y traslados\n4. Productos de Lanzarote: aloe vera, vinoterapia o vinos\n\nRespóndeme con el número y te ayudo. También puedo preparar reserva o compra.";
        }

        $knowledge = $this->prioritizeCommercialKnowledge($knowledge, $message);
        $fragments = [];

        foreach (array_slice($knowledge, 0, 2) as $item) {
            $content = trim((string) $item['content']);

            if ($content === '') {
                continue;
            }

            $fragments[] = '• '.$this->compactKnowledgeText($content, $message);
        }

        if ($fragments === []) {
            return "Claro 😊 Tengo información de varias opciones:\n1. Restaurantes\n2. Visitas guiadas y bodegas\n3. Taxis\n4. Productos locales: aloe vera, vinoterapia y vinos\n\nRespóndeme con el número y te ayudo. También puedo preparar reserva o compra.";
        }

        return "Claro 😊 Te cuento:\n"
            .implode("\n", $fragments)
            ."\n\n¿Qué prefieres?\n1. Reservarlo ahora\n2. Ver más opciones relacionadas\n3. Hablar con una persona\n\nRespóndeme con el número de la opción.";
    }

    /**
     * @param  array<int, array{title:string, content:string}>  $knowledge
     * @return array<int, array{title:string, content:string}>
     */
    private function prioritizeCommercialKnowledge(array $knowledge, string $message): array
    {
        $terms = $this->commercialTerms($message);

        usort($knowledge, function (array $first, array $second) use ($terms): int {
            $firstScore = $this->commercialKnowledgeScore($first, $terms);
            $secondScore = $this->commercialKnowledgeScore($second, $terms);

            return $secondScore <=> $firstScore;
        });

        return $knowledge;
    }

    /**
     * @param  array{title:string, content:string}  $knowledge
     * @param  array<int, string>  $terms
     */
    private function commercialKnowledgeScore(array $knowledge, array $terms): int
    {
        $haystack = mb_strtolower($knowledge['title'].' '.$knowledge['content']);
        $score = 0;

        foreach ($terms as $term) {
            if (str_contains($haystack, $term)) {
                $score++;
            }
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private function commercialTerms(string $message): array
    {
        $normalizedMessage = mb_strtolower($message);
        $terms = preg_split('/\W+/u', $normalizedMessage) ?: [];
        $terms = array_values(array_filter($terms, fn (string $term): bool => mb_strlen($term) >= 4));

        if (str_contains($normalizedMessage, 'visita') || str_contains($normalizedMessage, 'visitas')) {
            $terms = array_merge(['visita', 'visitas', 'guiada', 'guiadas', 'tour', 'cata'], $terms);
        }

        if (str_contains($normalizedMessage, 'tinto') || str_contains($normalizedMessage, 'tintos')) {
            $terms = array_merge(['tinto', 'tintos', 'listán', 'syrah', 'manto'], $terms);
        }

        if (str_contains($normalizedMessage, 'vino') || str_contains($normalizedMessage, 'vinos')) {
            $terms = array_merge(['vino', 'vinos', 'malvasía', 'tinto', 'rosado'], $terms);
        }

        return array_values(array_unique($terms));
    }

    private function compactKnowledgeText(string $content, string $message): string
    {
        $focusedSentences = $this->focusedKnowledgeSentences($content, $message);

        if ($focusedSentences !== '') {
            return $focusedSentences;
        }

        $sentences = preg_split('/(?<=[.!?])\s+/u', trim($content)) ?: [];
        $selected = array_slice(array_filter($sentences), 0, 2);
        $text = trim(implode(' ', $selected));

        if ($text === '') {
            $text = $content;
        }

        return mb_substr($text, 0, 420);
    }

    private function focusedKnowledgeSentences(string $content, string $message): string
    {
        $terms = $this->focusedCommercialTerms($message);
        $lines = preg_split('/\R+/u', trim($content)) ?: [];
        $matches = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $normalizedLine = mb_strtolower($line);

            foreach ($terms as $term) {
                if (str_contains($normalizedLine, $term)) {
                    $matches[] = $this->focusLineAroundTerm($line, $term);
                    break;
                }
            }

            if (count($matches) >= 4) {
                break;
            }
        }

        if ($matches === []) {
            return '';
        }

        return mb_substr(implode(' · ', $matches), 0, 420);
    }

    private function focusLineAroundTerm(string $line, string $term): string
    {
        $normalizedLine = mb_strtolower($line);
        $position = mb_strpos($normalizedLine, $term);

        if ($position === false || mb_strlen($line) <= 220) {
            return $line;
        }

        $start = max(0, $position);
        $focused = trim(mb_substr($line, $start, 260));

        return $focused;
    }

    /**
     * @return array<int, string>
     */
    private function focusedCommercialTerms(string $message): array
    {
        $normalizedMessage = mb_strtolower($message);

        if (str_contains($normalizedMessage, 'tinto') || str_contains($normalizedMessage, 'tintos')) {
            return ['tinto', 'tintos', 'listán', 'listan', 'syrah', 'merlot', 'tintilla'];
        }

        if (str_contains($normalizedMessage, 'visita') || str_contains($normalizedMessage, 'visitas')) {
            return ['visita', 'visitas', 'guiada', 'guiadas', 'tour', 'cata', 'recorrido'];
        }

        if (str_contains($normalizedMessage, 'aloe') || str_contains($normalizedMessage, 'vinoterapia')) {
            return ['aloe', 'vinoterapia', 'producto', 'productos', 'tratamiento', 'tratamientos'];
        }

        if (str_contains($normalizedMessage, 'taxi') || str_contains($normalizedMessage, 'taxis') || str_contains($normalizedMessage, 'traslado')) {
            return ['taxi', 'taxis', 'traslado', 'traslados', 'origen', 'destino'];
        }

        return $this->commercialTerms($message);
    }

    private function normalizeIntent(string $intent): string
    {
        $intent = mb_strtolower(trim($intent));

        return match (true) {
            str_contains($intent, 'taxi') => 'taxi_booking',
            str_contains($intent, 'ride') => 'taxi_booking',

            str_contains($intent, 'restaurant') => 'restaurant_booking',
            str_contains($intent, 'table') => 'restaurant_booking',

            str_contains($intent, 'winery') => 'winery_visit',
            str_contains($intent, 'bodega') => 'winery_visit',

            default => 'unknown',
        };
    }

    /**
     * @return array<string, mixed>|null
     */
    private function previousConversation(string $touristPhone): ?array
    {
        $request = NovaRequest::query()
            ->where('type', 'tourism_orchestration')
            ->where('context->tourist_phone', $touristPhone)
            ->where('created_at', '>=', now()->subHours(2))
            ->latest()
            ->first();

        $conversation = data_get($request?->context, 'conversation');

        return is_array($conversation) ? $conversation : null;
    }

    private function formatTimeForReply(string $timeLabel): string
    {
        if (in_array($timeLabel, ['tarde', 'mediodía'], true)) {
            return "por la {$timeLabel}";
        }

        return "a las {$timeLabel}";
    }

    /**
     * @param  array<string, mixed>  $conversation
     */
    private function formatDateForReply(array $conversation): string
    {
        $date = (string) data_get($conversation, 'date.value');

        if ($date === '') {
            return '';
        }

        return CarbonImmutable::parse($date, 'Europe/Madrid')->format('d/m');
    }

    /**
     * @param  array<string, mixed>  $conversation
     * @param  array<string, mixed>  $extra
     */
    private function configuredConversationReply(string $path, array $conversation, array $extra = []): ?string
    {
        $template = data_get($this->conversationBehaviorConfig(), $path);

        if (! is_string($template) || trim($template) === '') {
            return null;
        }

        return $this->renderConversationTemplate($template, $conversation, $extra);
    }

    private function buildDebugInfo(array $conversation, string $touristPhone): string
    {
        $template = (string) data_get($this->conversationBehaviorConfig(), 'debug.template', <<<'TEXT'

--- DEBUG ---
Channel: {{channel}}
Conversation: {{conversation_id}}
Tourist: {{tourist_phone}}
Intent: {{intent}}
Stage: {{stage}}
Previous intent: {{previous_intent}}
Previous stage: {{previous_stage}}
Last menu: {{last_menu}}
Quick reply: {{quick_reply_action}}
Business: {{business_slug}}
Missing: {{missing_fields}}
Date: {{date_label}}
Time: {{time_label}}
Party: {{party_size}}
Origin: {{origin}}
Destination: {{destination}}
Customer: {{customer_name}}
Phone: {{customer_phone}}
Email: {{customer_email}}
---
TEXT);

        return $this->renderConversationTemplate($template, $conversation, [
            'tourist_phone' => $touristPhone,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function conversationBehaviorConfig(): array
    {
        $json = NovaPromptLoader::system('nova-conversation-behaviors', '');
        $config = json_decode($json, true);

        return is_array($config) ? $config : [];
    }

    /**
     * @param  array<string, mixed>  $conversation
     * @param  array<string, mixed>  $extra
     */
    private function renderConversationTemplate(string $template, array $conversation, array $extra = []): string
    {
        $values = array_merge([
            'channel' => data_get($conversation, 'channel', 'api'),
            'conversation_id' => data_get($conversation, 'conversation_id', data_get($conversation, 'tourist_phone', '')),
            'intent' => data_get($conversation, 'intent', ''),
            'stage' => data_get($conversation, 'stage', ''),
            'previous_intent' => data_get($conversation, 'previous_intent', ''),
            'previous_stage' => data_get($conversation, 'previous_stage', ''),
            'last_menu' => data_get($conversation, 'last_menu', ''),
            'quick_reply_action' => data_get($conversation, 'quick_reply_action', ''),
            'business_slug' => data_get($conversation, 'business_slug', ''),
            'business_name' => data_get($conversation, 'business_name', ''),
            'missing_fields' => implode(', ', (array) data_get($conversation, 'missing_fields', [])),
            'missing_labels' => implode(', ', (array) data_get($conversation, 'missing_labels', [])),
            'date_label' => data_get($conversation, 'date_label', ''),
            'date_value' => data_get($conversation, 'date.value', ''),
            'date_short' => $this->formatDateForReply($conversation),
            'time_label' => data_get($conversation, 'time_label', ''),
            'time_text' => $this->formatTimeForReply((string) data_get($conversation, 'time_label', '')),
            'party_size' => data_get($conversation, 'party_size', ''),
            'origin' => data_get($conversation, 'origin', ''),
            'destination' => data_get($conversation, 'destination', ''),
            'customer_name' => data_get($conversation, 'customer_name', ''),
            'customer_phone' => data_get($conversation, 'customer_phone', ''),
            'customer_email' => data_get($conversation, 'customer_email', ''),
        ], $extra);

        foreach ($values as $key => $value) {
            $template = str_replace('{{'.$key.'}}', is_scalar($value) ? (string) $value : '', $template);
        }

        return $template;
    }

    /**
     * @param  array<string, mixed>  $conversation
     * @return array<string, mixed>|null
     */
    private function maybeCheckRestaurantAvailability(array $conversation, ?Server $sirvo): ?array
    {
        if (! $sirvo || $conversation['intent'] !== 'restaurant_booking' || $conversation['missing_fields'] !== []) {
            return null;
        }

        return $this->sirvoReservationClient->checkCapacity(
            server: $sirvo,
            date: (string) data_get($conversation, 'date.value'),
            time: (string) data_get($conversation, 'time.value'),
            guests: (int) $conversation['party_size'],
        );
    }

    private function compactBody(string $body): string
    {
        return mb_substr(trim(preg_replace('/\s+/', ' ', $body) ?? ''), 0, 220);
    }

    /**
     * Execute MCP creation when user confirms booking
     *
     * @param  array<string, mixed>  $conversation
     * @return array<string, mixed>
     */
    public function executeMcpCreation(array $conversation, string $touristPhone): array
    {
        $business = $this->businessForConversation($conversation, '');
        $service = $business?->services()->where('has_mcp', true)->first();

        if (! $business || ! $service) {
            return [
                'success' => false,
                'error' => 'Business or service not found for MCP creation',
            ];
        }

        $intent = $conversation['intent'] ?? 'unknown';

        return match ($intent) {
            'restaurant_booking' => $this->executeRestaurantCreation($business, $service, $conversation, $touristPhone),
            'winery_visit' => $this->executeWineryCreation($business, $service, $conversation, $touristPhone),
            'taxi_booking' => $this->executeTaxiCreation($business, $service, $conversation, $touristPhone),
            default => [
                'success' => false,
                'error' => 'Unknown intent for MCP creation',
            ],
        };
    }

    /**
     * Execute restaurant booking creation via MCP
     *
     * @param  array<string, mixed>  $conversation
     * @return array<string, mixed>
     */
    private function executeRestaurantCreation(NovaBusiness $business, NovaService $service, array $conversation, string $touristPhone): array
    {
        // Try LatePoint first (La Geria)
        if ($this->mcpCreationService->isAvailableForCreation($business, $service, 'latepoint')) {
            return $this->mcpCreationService->createLatePointBooking($business, $service, [
                'service_id' => data_get($conversation, 'service_id'),
                'date' => data_get($conversation, 'date.value'),
                'time' => data_get($conversation, 'time.value'),
                'attendees' => $conversation['party_size'] ?? 1,
                'customer_name' => $conversation['customer_name'] ?? null,
                'customer_email' => data_get($conversation, 'customer_email'),
                'customer_phone' => $touristPhone,
                'notes' => $conversation['preferences'] ?? null,
            ]);
        }

        // Fallback to Sirvo
        if ($this->mcpCreationService->isAvailableForCreation($business, $service, 'sirvo')) {
            return $this->mcpCreationService->createSirvoReservation($business, $service, [
                'restaurant_id' => config('services.sirvo.default_restaurant_id'),
                'date' => data_get($conversation, 'date.value'),
                'time' => data_get($conversation, 'time.value'),
                'guests' => $conversation['party_size'] ?? 1,
                'customer_name' => $conversation['customer_name'] ?? null,
                'customer_phone' => $touristPhone,
                'customer_email' => data_get($conversation, 'customer_email'),
                'notes' => $conversation['preferences'] ?? null,
            ]);
        }

        return [
            'success' => false,
            'error' => 'No MCP server available for restaurant booking',
        ];
    }

    /**
     * Execute winery visit creation via MCP
     *
     * @param  array<string, mixed>  $conversation
     * @return array<string, mixed>
     */
    private function executeWineryCreation(NovaBusiness $business, NovaService $service, array $conversation, string $touristPhone): array
    {
        if ($this->mcpCreationService->isAvailableForCreation($business, $service, 'latepoint')) {
            return $this->mcpCreationService->createLatePointBooking($business, $service, [
                'service_id' => data_get($conversation, 'service_id'),
                'date' => data_get($conversation, 'date.value'),
                'time' => data_get($conversation, 'time.value'),
                'attendees' => $conversation['party_size'] ?? 1,
                'customer_name' => $conversation['customer_name'] ?? null,
                'customer_email' => data_get($conversation, 'customer_email'),
                'customer_phone' => $touristPhone,
                'notes' => $conversation['preferences'] ?? null,
            ]);
        }

        return [
            'success' => false,
            'error' => 'No MCP server available for winery visit',
        ];
    }

    /**
     * Execute taxi booking creation via MCP
     *
     * @param  array<string, mixed>  $conversation
     * @return array<string, mixed>
     */
    private function executeTaxiCreation(NovaBusiness $business, NovaService $service, array $conversation, string $touristPhone): array
    {
        // Try WooCommerce MCP for taxi routes
        if ($this->mcpCreationService->isAvailableForCreation($business, $service, 'woocommerce')) {
            return $this->mcpCreationService->createWooCommerceOrder($business, $service, [
                'product_id' => data_get($conversation, 'route_id'),
                'quantity' => 1,
                'customer_name' => $conversation['customer_name'] ?? null,
                'customer_email' => data_get($conversation, 'customer_email'),
                'customer_phone' => $touristPhone,
                'billing_address' => data_get($conversation, 'pickup_location'),
                'shipping_address' => data_get($conversation, 'dropoff_location'),
                'payment_method' => 'cash',
                'notes' => sprintf('Taxi: %s -> %s', data_get($conversation, 'pickup_location'), data_get($conversation, 'dropoff_location')),
            ]);
        }

        return [
            'success' => false,
            'error' => 'No MCP server available for taxi booking',
        ];
    }

    /**
     * Resolve {{path}} placeholders inside workflow output/meta using the workflow state.
     */
    private function resolveWorkflowPlaceholders(string $template, mixed $state): string
    {
        return (string) preg_replace_callback('/\{\{\s*([^}\s]+)\s*\}\}/', function (array $matches) use ($state): string {
            $path = $matches[1];
            $value = data_get($state->variables ?? [], $path) ?? data_get($state->meta ?? [], $path);

            if ($value === null) {
                return '';
            }

            if (is_array($value)) {
                return implode(', ', array_map(static function (mixed $item): string {
                    if (is_array($item)) {
                        return $item['label'] ?? $item['name'] ?? $item['text'] ?? $item['value'] ?? json_encode($item);
                    }

                    return (string) $item;
                }, $value));
            }

            return is_scalar($value) ? (string) $value : '';
        }, $template);
    }
}
