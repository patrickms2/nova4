<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Prompt;
use App\Models\Server;
use Illuminate\Support\Collection;

/**
 * Catalog of editable MCP prompts for App\Services\Nova services.
 *
 * Installing these prompts exposes the system prompts used by
 * NovaAiService and NovaCrossSellingService as Filament-editable MCP Prompt records.
 *
 * Services read their system prompts via NovaPromptLoader::system(),
 * falling back to the hardcoded defaults when a prompt is not installed.
 */
final class NovaServicesPromptCatalog
{
    public const SERVER_SLUG = 'nova';

    /**
     * Install or update all Nova service prompts into the database.
     * Creates the "nova-services" MCP server if it does not exist.
     *
     * @return array{server: Server, installed: int, skipped: int}
     */
    public function install(): array
    {
        $server = $this->resolveServer();
        $installed = 0;
        $skipped = 0;

        $this->deleteObsoletePrompts($server);

        foreach ($this->prompts($server->id) as $data) {
            $exists = Prompt::query()
                ->where('server_id', $server->id)
                ->where('name', $data['name'])
                ->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            Prompt::create($data);
            $installed++;
        }

        return compact('server', 'installed', 'skipped');
    }

    /**
     * Overwrite all Nova service prompts regardless of existing content.
     *
     * @return array{server: Server, updated: int}
     */
    public function reinstall(): array
    {
        $server = $this->resolveServer();
        $updated = 0;

        $this->deleteObsoletePrompts($server);

        foreach ($this->prompts($server->id) as $data) {
            Prompt::query()
                ->where('server_id', $server->id)
                ->where('name', $data['name'])
                ->delete();

            Prompt::create($data);
            $updated++;
        }

        return compact('server', 'updated');
    }

    /**
     * Return all prompt definitions for the given server_id.
     *
     * @return array<int, array<string, mixed>>
     */
    public function prompts(int $serverId): array
    {
        return [
            $this->intentDetectionPrompt($serverId),
            $this->bookingExtractionPrompt($serverId),
            $this->responseGenerationPrompt($serverId),
            $this->crossSellingRulesPrompt($serverId),
            $this->orchestratorPrompt($serverId),
        ];
    }

    // -------------------------------------------------------------------------
    // Prompt definitions
    // -------------------------------------------------------------------------

    private function intentDetectionPrompt(int $serverId): array
    {
        return [
            'server_id' => $serverId,
            'name' => 'nova-intent-detection',
            'title' => 'Nova: Intent Detection',
            'description' => 'System prompt used by NovaAiService to detect user intent via OpenAI.',
            'arguments' => [],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<'EOT'
You are an expert intent detection system for a tourism booking assistant in Lanzarote, Spain.

Analyze the user's message and determine their primary intent from these categories:
- "restaurant_booking": User wants to book a restaurant table. Also use this for "restaurante + taxi", "restaurante con taxi", "mesa + taxi", menu option 6; taxi is secondary package context, not the primary intent.
- "winery_visit": User wants to visit a winery/bodega. Also use this for "visita + taxi", "visita con taxi", "bodega + taxi", menu option 5; taxi is secondary package context, not the primary intent.
- "restaurant_and_winery_visit": User wants both restaurant and winery
- "taxi_booking": User needs a taxi/transportation
- "commercial_info": User is asking for information about services
- "unknown": Cannot determine intent

Respond in JSON format with these fields:
- intent: the detected intent category
- confidence: float between 0 and 1 indicating confidence level
- reasoning: brief explanation of why this intent was chosen

Be precise and context-aware. Consider previous messages if provided.
EOT,
                ],
            ],
            'metadata' => ['service_class' => 'App\\Services\\Nova\\NovaAiService', 'method' => 'detectIntent'],
            'is_active' => true,
            'sort_order' => 10,
        ];
    }

    private function bookingExtractionPrompt(int $serverId): array
    {
        return [
            'server_id' => $serverId,
            'name' => 'nova-booking-extraction',
            'title' => 'Nova: Booking Data Extraction',
            'description' => 'System prompt used by NovaAiService to extract booking fields from user messages.',
            'arguments' => [],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<'EOT'
You are an expert data extraction system for tourism bookings in Lanzarote, Spain.

Extract booking information from the user's message. Return JSON with these fields:
- date: object with "label" (user's wording) and "value" (YYYY-MM-DD format), or null if not found
- time: object with "label" (user's wording like "11:00") and "value" (HH:MM format), or null if not found
- party_size: integer number of people, or null if not found
- customer_name: string name of the person booking, or null if not found
- customer_phone: string phone number (9 digits) or email, or null if not found
- preferences: string with dietary requirements or special requests, or null if not found

Rules:
- "mañana" = tomorrow's date
- "hoy" = today's date
- Extract names even if mixed with other words
- Spanish phone numbers are 9 digits starting with 6 or 9
- Be conservative: if uncertain, return null
- Current date context: Use current date in Europe/Madrid timezone
EOT,
                ],
            ],
            'metadata' => ['service_class' => 'App\\Services\\Nova\\NovaAiService', 'method' => 'extractBookingData'],
            'is_active' => true,
            'sort_order' => 20,
        ];
    }

    private function responseGenerationPrompt(int $serverId): array
    {
        return [
            'server_id' => $serverId,
            'name' => 'nova-response-generation',
            'title' => 'Nova: Response Generation',
            'description' => 'System prompt used by NovaAiService to generate natural conversational replies.',
            'arguments' => [],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<'EOT'
You are a friendly, professional tourism assistant for Lanzarote, Spain. Your name is Nova.

Guidelines:
- Be concise and natural in Spanish
- Be helpful but not overly wordy
- Match the user's tone (formal/informal)
- Ask for missing information politely
- Confirm details before proceeding
- Use emojis sparingly and appropriately
- Reference local businesses (La Geria winery, Taberna La Cepa, Lanzaloe, etc.)
- Current date context: Use current date in Europe/Madrid timezone

Generate a natural, contextually appropriate response to the user.
EOT,
                ],
            ],
            'metadata' => ['service_class' => 'App\\Services\\Nova\\NovaAiService', 'method' => 'generateResponse'],
            'is_active' => true,
            'sort_order' => 30,
        ];
    }

    private function crossSellingRulesPrompt(int $serverId): array
    {
        return [
            'server_id' => $serverId,
            'name' => 'nova-cross-selling-rules',
            'title' => 'Nova: Cross-Selling Rules',
            'description' => 'Reglas de sugerencias cruzadas entre negocios. Cada mensaje system define un negocio y su matriz de sugerencias.',
            'arguments' => [
                ['name' => 'current_business', 'description' => 'Slug del negocio actual (la-geria, lanzaloe, sirvo, taxilanz)', 'required' => true],
                ['name' => 'intent', 'description' => 'Intención detectada del usuario', 'required' => true],
            ],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<'EOT'
Reglas de cross-selling entre negocios de Lanzarote.

== la-geria ==
winery_visit:
  → taxilanz: "¿Quieres un taxi para llegar a la visita ?" [high]
  → lanzaloe: "¿Te gustaría probar los productos de aloe vera de Lanzaloe? Usan los vinos de La Geria en sus tratamientos de vinoterapia." [medium]
  → sirvo: "¿Te apetece cenar en Taberna La Cepa después de la visita? Tenemos mesas disponibles." [high]

restaurant_booking:
  → lanzaloe: "¿Te interesa visitar la finca de aloe vera de Lanzaloe después de cenar? Tienen visitas guiadas muy interesantes." [medium]
  → taxilanz: "¿Necesitas un taxi para llegar a la bodega o para volver después?" [high]

== lanzaloe ==
winery_visit:
  → la-geria: "¿Te gustaría visitar la bodega de La Geria? Sus vinos son los que usamos en nuestra vinoterapia." [high]
  → taxilanz: "¿Quieres un taxi para llegar a la finca o para ir a la bodega después?" [medium]
restaurant_booking:
  → la-geria: "¿Te interesa cenar en Taberna La Cepa? Está muy cerca de la finca." [medium]

== sirvo ==
restaurant_booking:
  → la-geria: "¿Te interesa una visita a la bodega después de cenar? Tenemos visitas guiadas a las 11:00 y 16:00." [high]
  → taxilanz: "¿Necesitas un taxi para volver al hotel después de cenar?" [medium]

== taxilanz ==
taxi_booking:
  → la-geria: "¿Te interesa visitar la bodega de La Geria? Puedo llevarte allí directamente." [high]
  → sirvo: "¿Quieres cenar en Sirvo? Puedo llevarte al restaurante." [medium]
  → lanzaloe: "¿Te apetece visitar la finca de aloe vera de Lanzaloe? Tenemos rutas turísticas que pasan por allí." [medium]
EOT,
                ],
            ],
            'metadata' => ['service_class' => 'App\\Services\\Nova\\NovaCrossSellingService', 'method' => 'suggestCrossSelling'],
            'is_active' => true,
            'sort_order' => 50,
        ];
    }

    private function orchestratorPrompt(int $serverId): array
    {
        return [
            'server_id' => $serverId,
            'name' => 'nova-orchestrator',
            'title' => 'Nova: Orchestrator Instructions',
            'description' => 'Instrucciones de alto nivel para el NovaOrchestratorService: qué servicios activar y en qué orden.',
            'arguments' => [],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => <<<'EOT'
Eres Nova, el orquestador de turismo local en Lanzarote.

Flujo de decisión:
1. Extrae la intención y datos de reserva del mensaje del turista (NovaAiService via Laravel AI SDK).
2. Carga conocimiento relevante del negocio (NovaKnowledgeService).
3. Comprueba disponibilidad si la intención es restaurant_booking o winery_visit (Sirvo MCP).
4. Construye la respuesta combinando: intención, reserva, conocimiento, cross-selling y contexto previo.
5. Registra el resultado en NovaRequest.

Intenciones soportadas:
- taxi_booking → crear reserva vía TaxilanzMCPServer
- restaurant_booking → comprobar disponibilidad vía Sirvo, crear reserva
- winery_visit → obtener horarios vía La Geria MCP, crear reserva y añadir taxi
- restaurant_and_winery_visit → combinar restaurant_booking + winery_visit
- commercial_info → responder con conocimiento del negocio
- unknown → solicitar aclaración al usuario

Idioma: responde siempre en el mismo idioma del mensaje del turista.
EOT,
                ],
            ],
            'metadata' => ['service_class' => 'App\\Services\\Nova\\NovaOrchestratorService', 'method' => 'runLocalTourismScenario'],
            'is_active' => true,
            'sort_order' => 60,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function deleteObsoletePrompts(Server $server): void
    {
        Prompt::query()
            ->where('server_id', $server->id)
            ->whereIn('name', [
                'nova-ollama-intent',
            ])
            ->delete();
    }

    private function resolveServer(): Server
    {
        $server = Server::query()->where('slug', self::SERVER_SLUG)->first();

        if (! $server) {
            throw new \RuntimeException('Nova MCP server (slug: "'.self::SERVER_SLUG.'") not found. Make sure the Nova server exists in the database.');
        }

        return $server;
    }
}
