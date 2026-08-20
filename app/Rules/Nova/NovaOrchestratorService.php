<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaBusiness;
use App\Models\NovaMcpServer;
use App\Models\NovaRequest;
use App\Models\NovaService;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

final class NovaOrchestratorService
{
    public function __construct(
        private readonly SirvoReservationClient $sirvoReservationClient,
        private readonly NovaConversationDataExtractor $conversationDataExtractor,
        private readonly NovaKnowledgeService $knowledgeService,
        private readonly NovaMcpCreationService $mcpCreationService,
        private readonly NovaConversationContextService $contextService,
        private readonly NovaCrossSellingService $crossSellingService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function runLocalTourismScenario(string $message, string $touristPhone = '+34646426442'): array
    {
        $sirvo = $this->activeServer('sirvo');
        $lageria = $this->activeServer('la_geria');

        $sirvoConfig = $sirvo ? $this->probeSirvo($sirvo) : null;
        $lageriaMcp = $lageria ? $this->probeLaGeria($lageria) : null;

        $previousConversation = $this->previousConversation($touristPhone);
        $conversation = $this->conversationDataExtractor->extract($message, $touristPhone, $previousConversation);

        // Get context and suggestions
        $context = $this->contextService->getContext($touristPhone);
        $contextSuggestion = $this->contextService->suggestBasedOnContext($touristPhone, $conversation['intent']);
        $previousDetailsSuggestion = $this->contextService->rememberPreviousDetails($touristPhone, $conversation['intent']);

        $business = $this->businessForConversation($conversation, $message);
        $crossSellingSuggestions = $business ? $this->crossSellingService->suggestCrossSelling($business->slug, $conversation['intent']) : [];

        $reservationCheck = $this->maybeCheckRestaurantAvailability($conversation, $sirvo);
        $knowledge = $this->knowledgeService->relevantKnowledge($business, $message);
        $summary = $this->buildCustomerReply($conversation, $message, $sirvoConfig, $lageriaMcp, $reservationCheck, $knowledge, $contextSuggestion, $previousDetailsSuggestion, $crossSellingSuggestions);

        $request = NovaRequest::create([
            'type' => 'tourism_orchestration',
            'status' => $conversation['missing_fields'] === [] ? 'ready_to_book' : 'collecting_details',
            'title' => 'Tourism booking conversation',
            'summary' => $summary,
            'context' => [
                'tourist_phone' => $touristPhone,
                'message' => $message,
                'conversation' => $conversation,
                'reservation_check' => $reservationCheck,
                'knowledge' => $knowledge,
                'sirvo' => $sirvoConfig,
                'la_geria' => $lageriaMcp,
                'context_suggestion' => $contextSuggestion,
                'cross_selling_suggestions' => $crossSellingSuggestions,
            ],
        ]);

        return [
            'success' => true,
            'nova_request_id' => $request->id,
            'message' => $message,
            'reply' => $summary,
            'checks' => [
                'sirvo' => $sirvoConfig,
                'la_geria' => $lageriaMcp,
                'reservation_check' => $reservationCheck,
                'knowledge' => $knowledge,
            ],
        ];
    }

    /**
     * Get active MCP server by type.
     *
     * Priority:
     * 1. Active server from database (nova_mcp_servers table)
     * 2. Fallback to config (services.nova.sirvo_endpoint_url, etc.) for development/production
     *
     * This allows development with local endpoints without DB entries,
     * and production with server endpoints configured via .env.
     */
    private function activeServer(string $type): ?NovaMcpServer
    {
        $server = NovaMcpServer::query()
            ->where('type', $type)
            ->where('status', 'active')
            ->latest('last_checked_at')
            ->first();

        if ($server !== null) {
            return $server;
        }

        $endpointUrl = match ($type) {
            'sirvo' => config('services.nova.sirvo_endpoint_url'),
            'la_geria' => config('services.nova.lageria_endpoint_url'),
            'lanzaloe' => config('services.nova.lanzaloe_endpoint_url'),
            'taxilanz' => config('services.nova.taxilanz_endpoint_url'),
            'taxilanz_hoteles' => config('services.nova.taxilanz_hoteles_endpoint_url'),
            default => null,
        };

        if ($endpointUrl === null) {
            return null;
        }

        return new NovaMcpServer([
            'type' => $type,
            'endpoint_url' => $endpointUrl,
            'status' => 'active',
            'last_checked_at' => now(),
        ]);
    }

    private function businessForConversation(array $conversation, string $message): ?NovaBusiness
    {
        $normalizedMessage = mb_strtolower($message);

        if (str_contains($normalizedMessage, 'lanzaloe') || str_contains($normalizedMessage, 'aloe') || str_contains($normalizedMessage, 'vinoterapia')) {
            return $this->businessByTerms(['lanzaloe', 'aloe']);
        }

        if (str_contains($normalizedMessage, 'geria') || str_contains($normalizedMessage, 'bodega') || str_contains($normalizedMessage, 'vino') || str_contains($normalizedMessage, 'taberna') || str_contains($normalizedMessage, 'cepa')) {
            return $this->businessByTerms(['la-geria', 'geria', 'bodega']);
        }

        if (str_contains($normalizedMessage, 'cangrejo rojo')) {
            return $this->businessByTerms(['cangrejo-rojo', 'cangrejo']);
        }

        if (str_contains($normalizedMessage, 'restaurante') || str_contains($normalizedMessage, 'comida')) {
            return $this->businessByTerms(['cangrejo', 'sirvo', 'restaurant']);
        }

        if (str_contains($normalizedMessage, 'taxi') || str_contains($normalizedMessage, 'taxis') || str_contains($normalizedMessage, 'traslado')) {
            return $this->businessByTerms(['taxi', 'taxilanz']);
        }

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
     * @return array<string, mixed>
     */
    private function probeSirvo(NovaMcpServer $server): array
    {
        $baseUrl = rtrim((string) $server->endpoint_url, '/');

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
    private function probeLaGeria(NovaMcpServer $server): array
    {
        $restUrl = rtrim($server->endpoint_url, '/').'/wp-json/';
        $mcpUrl = rtrim($server->endpoint_url, '/').'/wp-json/mcp/v1';

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
        array $crossSellingSuggestions = []
    ): string {
        $sirvoReady = (bool) data_get($sirvo, 'config.reachable') && (bool) data_get($sirvo, 'branches.reachable');
        $lageriaReady = (bool) data_get($lageria, 'wordpress_rest.reachable') && (bool) data_get($lageria, 'mcp.reachable');

        if ($conversation['intent'] === 'commercial_info') {
            return $this->buildCommercialInfoReplyWithCrossSelling($knowledge, $message, $crossSellingSuggestions);
        }

        if ($conversation['stage'] === 'awaiting_customer_name') {
            $baseReply = $this->buildAwaitingNameReply($conversation, $reservationCheck);

            // Add context suggestion if available
            if ($previousDetailsSuggestion !== null) {
                return $baseReply.' '.$previousDetailsSuggestion;
            }

            return $baseReply;
        }

        if ($conversation['stage'] === 'collecting_booking_details') {
            $reply = $this->buildCollectingDetailsReply($conversation, $sirvoReady, $lageriaReady);

            // Add context suggestion if available
            if ($previousDetailsSuggestion !== null) {
                $reply .= ' '.$previousDetailsSuggestion;
            }

            return $reply;
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

        if ($conversation['stage'] === 'collecting_taxi_details') {
            return $this->buildTaxiDetailsReply($conversation);
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
                'restaurant_booking' => '¿Vendréis solos o sois varios?',
                'winery_visit' => '¿Sois un grupo grande o sois pocos?',
                'taxi_booking' => '¿Para cuántas personas es el taxi?',
                default => '¿Para cuántas personas?',
            };
        }

        return '¿Qué más detalles necesitas?';
    }

    private function buildReadyToConfirmReply(array $conversation, ?array $reservationCheck): string
    {
        $intent = $conversation['intent'];
        $date = (string) $conversation['date_label'];
        $time = (string) $conversation['time_label'];
        $partySize = (string) $conversation['party_size'];

        return match ($intent) {
            'restaurant_booking' => sprintf(
                '¡Genial! Tengo apuntado %s %s %s para %s personas. ¿Confirmamos la reserva?',
                $date,
                $this->formatDateForReply($conversation),
                $this->formatTimeForReply($time),
                $partySize,
            ),
            'winery_visit' => sprintf(
                '¡Perfecto! Visita bodega %s %s %s para %s personas. ¿Confirmamos?',
                $date,
                $this->formatDateForReply($conversation),
                $this->formatTimeForReply($time),
                $partySize,
            ),
            'taxi_booking' => sprintf(
                '¡Perfecto! Taxi %s %s para %s personas. ¿Confirmamos?',
                $date,
                $this->formatTimeForReply($time),
                $partySize,
            ),
            default => '¿Confirmamos?',
        };
    }

    private function buildTaxiDetailsReply(array $conversation): string
    {
        $missing = $conversation['missing_labels'];

        if (in_array('día', $missing, true)) {
            return '¿Para cuándo necesitas el taxi? ¿Mañana, hoy, o tienes una fecha específica?';
        }

        if (in_array('hora', $missing, true)) {
            return '¿A qué hora necesitas el taxi?';
        }

        if (in_array('número de personas', $missing, true)) {
            return '¿Para cuántas personas es el taxi?';
        }

        return '¿De dónde a dónde necesitas el taxi?';
    }

    private function buildIntentSelectionReply(): string
    {
        return '¿Qué te apetece hacer hoy? Puedo ayudarte con:
1. 🍽️ Reservar mesa en restaurante
2. 🍷 Visitar bodega
3. 🚕 Solicitar taxi
4. ℹ️ Información sobre servicios

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

    /**
     * @param  array<string, mixed>|null  $previousConversation
     * @return array<string, mixed>
     */
    private function buildConversationState(string $message, string $touristPhone, ?array $previousConversation = null): array
    {
        $normalizedMessage = mb_strtolower($message);
        $partySize = $this->extractPartySize($normalizedMessage);
        $timeLabel = $this->extractTimeLabel($normalizedMessage);
        $dateLabel = $this->extractDateLabel($normalizedMessage);
        $intent = $this->detectIntent($normalizedMessage);

        if ($intent === 'unknown' && $this->hasBookingDetails($dateLabel, $timeLabel, $partySize)) {
            $intent = (string) data_get($previousConversation, 'intent', 'unknown');
        }

        $missingFields = [];
        $missingLabels = [];

        if ($dateLabel === null) {
            $missingFields[] = 'date';
            $missingLabels[] = 'día';
        }

        if ($timeLabel === null) {
            $missingFields[] = 'time';
            $missingLabels[] = 'hora';
        }

        if ($partySize === null) {
            $missingFields[] = 'party_size';
            $missingLabels[] = 'número de personas';
        }

        return [
            'tourist_phone' => $touristPhone,
            'intent' => $intent,
            'date_label' => $dateLabel,
            'time_label' => $timeLabel,
            'party_size' => $partySize,
            'missing_fields' => $missingFields,
            'missing_labels' => $missingLabels,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function previousConversation(string $touristPhone): ?array
    {
        $request = NovaRequest::query()
            ->where('type', 'tourism_orchestration')
            ->where('context->tourist_phone', $touristPhone)
            ->where('created_at', '>=', now()->subMinutes(30))
            ->latest()
            ->first();

        $conversation = data_get($request?->context, 'conversation');

        return is_array($conversation) ? $conversation : null;
    }

    private function hasBookingDetails(?string $dateLabel, ?string $timeLabel, ?int $partySize): bool
    {
        return $dateLabel !== null || $timeLabel !== null || $partySize !== null;
    }

    private function detectIntent(string $message): string
    {
        if (str_contains($message, 'taxi') || str_contains($message, 'traslado') || str_contains($message, 'recoger')) {
            return 'taxi_booking';
        }

        if (str_contains($message, 'restaurante') || str_contains($message, 'mesa') || str_contains($message, 'comer') || str_contains($message, 'cenar')) {
            return str_contains($message, 'visita') || str_contains($message, 'bodega')
                ? 'restaurant_and_winery_visit'
                : 'restaurant_booking';
        }

        if (str_contains($message, 'visita') || str_contains($message, 'bodega') || str_contains($message, 'geria')) {
            return 'winery_visit';
        }

        return 'unknown';
    }

    private function extractPartySize(string $message): ?int
    {
        if (preg_match('/\bpara\s+(\d{1,2})\b/u', $message, $matches) === 1) {
            return (int) $matches[1];
        }

        $words = [
            'uno' => 1,
            'una' => 1,
            'dos' => 2,
            'tres' => 3,
            'cuatro' => 4,
            'cinco' => 5,
            'seis' => 6,
            'siete' => 7,
            'ocho' => 8,
        ];

        foreach ($words as $word => $value) {
            if (str_contains($message, "para {$word}")) {
                return $value;
            }
        }

        return null;
    }

    private function extractTimeLabel(string $message): ?string
    {
        if (preg_match('/\b([01]?\d|2[0-3])[:.h]([0-5]\d)\b/u', $message, $matches) === 1) {
            return sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);
        }

        if (preg_match('/\b([01]?\d|2[0-3])\s*(?:h|horas)\b/u', $message, $matches) === 1) {
            return sprintf('%02d:00', (int) $matches[1]);
        }

        if (preg_match('/\ba\s+las\s+([01]?\d|2[0-3])\b/u', $message, $matches) === 1) {
            return sprintf('%02d:00', (int) $matches[1]);
        }

        if (str_contains($message, 'tarde')) {
            return 'tarde';
        }

        if (str_contains($message, 'mediodía') || str_contains($message, 'medio dia')) {
            return 'mediodía';
        }

        return null;
    }

    private function extractDateLabel(string $message): ?string
    {
        if (str_contains($message, 'mañana')) {
            return 'mañana';
        }

        if (str_contains($message, 'hoy')) {
            return 'hoy';
        }

        if (preg_match('/\b(\d{1,2})[\/-](\d{1,2})(?:[\/-](\d{2,4}))?\b/u', $message, $matches) === 1) {
            return $matches[0];
        }

        return null;
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
     * @return array<string, mixed>|null
     */
    private function maybeCheckRestaurantAvailability(array $conversation, ?NovaMcpServer $sirvo): ?array
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
}
