<?php

namespace Database\Seeders;

use App\Models\NovaBusiness;
use App\Models\NovaCrossSellingRule;
use App\Models\NovaIntentRule;
use App\Models\Prompt;
use App\Models\Server;
use Illuminate\Database\Seeder;

class NovaConversationBehaviorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $server = Server::query()->updateOrCreate(
            ['slug' => 'nova-conversation-core'],
            [
                'name' => 'Nova Conversation Core',
                'description' => 'Editable global behavior templates for Nova chat, bot, API and WhatsApp channels.',
                'version' => '1.0.0',
                'instructions' => 'Edit prompts, intents and cross-selling rules from Filament to tune Nova conversational behavior without code changes.',
                'transport' => 'web',
                'endpoint' => '/mcp/nova-conversation-core',
                'middleware' => ['web'],
                'metadata' => [
                    'domain' => 'nova',
                    'editable_from' => 'filament',
                    'purpose' => 'conversation_behavior',
                ],
                'is_active' => true,
            ],
        );

        $businesses = $this->seedBusinesses();
        $this->seedIntentRules();
        $this->seedCrossSellingRules($businesses);
        $this->seedBehaviorPrompt($server);

        $this->command?->info('Nova conversation behavior seeded: prompts, intents, cross-selling and base businesses.');
    }

    /**
     * @return array<string, NovaBusiness>
     */
    private function seedBusinesses(): array
    {
        $records = [
            'la-geria' => [
                'name' => 'Bodega La Geria',
                'business_type' => 'winery_tour',
                'settings' => ['recognition_terms' => ['la geria', 'bodega', 'vino', 'visita bodega', 'cata']],
            ],
            'sirvo' => [
                'name' => 'Taberna La Cepa',
                'business_type' => 'restaurant',
                'settings' => ['recognition_terms' => ['taberna la cepa', 'sirvo', 'restaurante', 'cena', 'mesa']],
            ],
            'taxilanz' => [
                'name' => 'Taxilanz',
                'business_type' => 'taxi',
                'settings' => ['recognition_terms' => ['taxi', 'taxilanz', 'traslado', 'transfer', 'recogida']],
            ],
            'lanzaloe' => [
                'name' => 'Lanzaloe',
                'business_type' => 'product_ecommerce',
                'settings' => ['recognition_terms' => ['lanzaloe', 'aloe', 'vinoterapia', 'cosmetica', 'cosmética']],
            ],
        ];

        return collect($records)
            ->mapWithKeys(fn (array $data, string $slug): array => [
                $slug => NovaBusiness::query()->updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'business_type' => $data['business_type'],
                        'status' => 'active',
                        'settings' => $data['settings'],
                    ],
                ),
            ])
            ->all();
    }

    private function seedIntentRules(): void
    {
        $rules = [
            ['restaurant_booking', 'include', ['restaurante', 'mesa', 'cenar', 'comer', 'reserva restaurante', 'taberna', 'cepa'], 'Reserva de restaurante', 10],
            ['winery_visit', 'include', ['visita bodega', 'bodega', 'la geria', 'cata', 'wine tour', 'visita guiada', 'vino'], 'Visita o cata en bodega', 10],
            ['taxi_booking', 'include', ['taxi', 'traslado', 'transfer', 'recogida', 'llévame', 'llevame', 'aeropuerto'], 'Reserva de taxi o traslado', 10],
            ['product_info', 'include', ['aloe', 'lanzaloe', 'vinoterapia', 'crema', 'cosmetica', 'cosmética', 'producto aloe'], 'Información de productos', 20],
            ['product_purchase', 'include', ['comprar aloe', 'tienda aloe', 'quiero comprar', 'shop', 'checkout'], 'Compra de productos', 15],
            ['commercial_info', 'include', ['info', 'información', 'informacion', 'qué hay', 'que hay', 'listado', 'lista', 'opciones', 'recomienda', 'planes'], 'Información comercial general', 30],
            ['system_info', 'include', ['debug', 'qué puedes hacer', 'que puedes hacer', 'herramientas', 'agente', 'mcp', 'estado del sistema'], 'Información sobre el agente/sistema', 5],
            ['route_recommendation', 'include', ['ruta', 'itinerario', 'plan de día', 'plan de dia', 'recomiéndame una ruta', 'recomiendame una ruta'], 'Recomendación de ruta o itinerario', 25],
            ['commercial_info', 'system_topic', ['restaurante', 'bodega', 'taxi', 'producto', 'aloe', 'vino', 'visita', 'tour', 'hotel', 'ruta'], 'Temas comerciales que evitan clasificar como system_info', 10],
        ];

        foreach ($rules as [$intent, $type, $keywords, $description, $priority]) {
            NovaIntentRule::query()->updateOrCreate(
                [
                    'nova_business_id' => null,
                    'intent_key' => $intent,
                    'rule_type' => $type,
                    'description' => $description,
                ],
                [
                    'keywords' => $keywords,
                    'priority' => $priority,
                    'is_active' => true,
                ],
            );
        }
    }

    /**
     * @param  array<string, NovaBusiness>  $businesses
     */
    private function seedCrossSellingRules(array $businesses): void
    {
        $rules = [
            ['la-geria', 'taxilanz', 'winery_visit', '¿Quieres añadir un taxi para llegar cómodo a la visita?', 'Añadir taxi', 0],
            ['la-geria', 'sirvo', 'winery_visit', 'Después de la visita puedo ayudarte a reservar mesa en Taberna La Cepa.', 'Añadir cena', 10],
            ['la-geria', 'lanzaloe', 'winery_visit', 'También puedes descubrir productos de aloe y vinoterapia relacionados con la experiencia.', 'Ver aloe/vinoterapia', 20],
            ['sirvo', 'taxilanz', 'restaurant_booking', 'Si vas a cenar, también puedo organizar un taxi de ida o vuelta.', 'Añadir taxi', 0],
            ['sirvo', 'la-geria', 'restaurant_booking', 'Antes de cenar, puedes completar el plan con una visita a Bodega La Geria.', 'Añadir visita', 10],
            ['taxilanz', 'la-geria', 'taxi_booking', 'Puedo proponerte una visita a Bodega La Geria como destino turístico.', 'Ver visita bodega', 10],
            ['taxilanz', 'sirvo', 'taxi_booking', 'También puedo ayudarte a reservar mesa en Taberna La Cepa.', 'Reservar restaurante', 20],
            ['lanzaloe', 'la-geria', 'product_info', 'Los productos de vinoterapia conectan muy bien con una visita a La Geria.', 'Ver bodega', 10],
        ];

        foreach ($rules as [$from, $to, $intent, $message, $cta, $priority]) {
            NovaCrossSellingRule::query()->updateOrCreate(
                [
                    'from_business_id' => $businesses[$from]->id,
                    'to_business_id' => $businesses[$to]->id,
                    'trigger_intent' => $intent,
                ],
                [
                    'message' => $message,
                    'cta_label' => $cta,
                    'cta_url' => null,
                    'excluded_intents' => null,
                    'priority' => $priority,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedBehaviorPrompt(Server $server): void
    {
        Prompt::query()->updateOrCreate(
            [
                'server_id' => $server->id,
                'name' => 'nova-conversation-behaviors',
            ],
            [
                'title' => 'Nova: Conversation Behaviors, Debug and Quick Replies',
                'description' => 'JSON editable desde Filament para ajustar mensajes por stage, opciones numeradas, tono, debug y comportamiento multi-canal.',
                'arguments' => [
                    ['name' => 'conversation', 'description' => 'Conversation state generated by NovaConversationDataExtractor', 'required' => true],
                ],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => json_encode($this->behaviorConfig(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
                'metadata' => [
                    'service_class' => 'App\\Services\\Nova\\NovaOrchestratorService',
                    'methods' => ['buildReadyToConfirmReply', 'buildIntentConfirmedReply', 'buildTaxiDetailsReply', 'buildDebugInfo'],
                    'editable_from' => 'Filament PromptResource',
                ],
                'is_active' => true,
                'sort_order' => 5,
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function behaviorConfig(): array
    {
        return [
            'tone' => [
                'name' => 'Natural, turístico, breve y proactivo',
                'rules' => [
                    'Una pregunta principal por turno.',
                    'Usar cross-selling suave solo cuando el usuario ya mostró intención clara.',
                    'Mantener opciones numeradas iguales en web, API y WhatsApp.',
                ],
            ],
            'debug' => [
                'template' => "\n\n--- DEBUG ---\nChannel: {{channel}}\nConversation: {{conversation_id}}\nTourist: {{tourist_phone}}\nIntent: {{intent}}\nStage: {{stage}}\nPrevious intent: {{previous_intent}}\nPrevious stage: {{previous_stage}}\nLast menu: {{last_menu}}\nQuick reply: {{quick_reply_action}}\nBusiness: {{business_slug}}\nMissing: {{missing_fields}}\nDate: {{date_label}} / {{date_value}}\nTime: {{time_label}}\nParty: {{party_size}}\nOrigin: {{origin}}\nDestination: {{destination}}\nCustomer: {{customer_name}}\nPhone: {{customer_phone}}\nEmail: {{customer_email}}\n---",
            ],
            'stages' => [
                'intent_confirmed' => [
                    'restaurant_booking' => ['reply' => '¡Perfecto! Tengo apuntada tu {{summary}}. Para confirmar necesito nombre, email y teléfono.'],
                    'winery_visit' => ['reply' => '¡Perfecto! Tengo apuntada tu {{summary}}. Para confirmar necesito nombre, email y teléfono.'],
                    'taxi_booking' => ['reply' => '¡Perfecto! Tengo apuntado tu {{summary}}. Para generar el enlace de pago necesito nombre, email y teléfono.'],
                ],
                'ready_to_confirm' => [
                    'restaurant_booking' => ['reply' => "¡Genial! Tengo apuntada la reserva {{date_label}} {{date_short}} {{time_text}} para {{party_size}} personas.\n\n1) Confirmar reserva\n2) Añadir taxi\n3) Info productos aloe vera\n\nResponde con el número de tu elección."],
                    'winery_visit' => ['reply' => "¡Perfecto! Tengo la visita a la bodega {{date_label}} {{date_short}} {{time_text}} para {{party_size}} personas.\n\n1) Confirmar visita\n2) Añadir taxi\n3) Añadir cena en Taberna La Cepa\n4) Info productos aloe vera\n\nResponde con el número de tu elección."],
                    'taxi_booking' => ['reply' => "¡Perfecto! Taxi de {{origin}} a {{destination}} {{date_label}} {{time_text}} para {{party_size}} personas.\n\n1) Confirmar taxi\n\nResponde con 1 para confirmar."],
                ],
                'collecting_taxi_details' => [
                    'date' => ['reply' => "¿Para cuándo necesitas el taxi?\n\n1) Hoy\n2) Mañana\n3) Otra fecha\n\nResponde con el número o escribe la fecha."],
                    'time' => ['reply' => '¿A qué hora necesitas el taxi? Ejemplo: 11:00'],
                    'party_size' => ['reply' => '¿Para cuántas personas es el taxi? Ejemplo: 2'],
                    'route' => ['reply' => '¿De dónde a dónde necesitas el taxi? Ejemplo: Hotel Fariones a Bodega La Geria'],
                    'origin' => ['reply' => '¿Desde dónde necesitas que te recojan? Ejemplo: Hotel Fariones'],
                    'destination' => ['reply' => '¿A dónde necesitas ir? Ejemplo: Bodega La Geria'],
                    'fallback' => ['reply' => '¿De dónde a dónde necesitas el taxi?'],
                ],
            ],
        ];
    }
}
