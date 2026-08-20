<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaBusiness;
use App\Models\NovaIntentRule;
use Carbon\CarbonImmutable;

final class NovaConversationDataExtractor
{
    private readonly NovaAiService $aiService;

    public function __construct(?NovaAiService $aiService = null)
    {
        $this->aiService = $aiService ?? app(NovaAiService::class);
    }

    /**
     * @param  array<string, mixed>|null  $previousConversation
     * @return array<string, mixed>
     */
    public function extract(string $message, string $touristPhone, ?array $previousConversation = null): array
    {
        $normalizedMessage = mb_strtolower($message);
        $previousStage = (string) data_get($previousConversation, 'stage', '');
        $previousIntent = (string) data_get($previousConversation, 'intent', 'unknown');
        $quickReplyAction = null;
        $context = (array) data_get($previousConversation, 'context', []);

        // Meta-queries about the system itself bypass all booking logic
        if ($this->isSystemInfoQuery($normalizedMessage)) {
            return [
                'tourist_phone' => $touristPhone,
                'intent' => 'system_info',
                'stage' => 'answering_system_info',
                'is_simple' => true,
                'date_label' => null,
                'date' => null,
                'time_label' => null,
                'time' => null,
                'party_size' => null,
                'customer_name' => null,
                'customer_phone' => null,
                'customer_email' => null,
                'preferences' => null,
                'missing_fields' => [],
                'missing_labels' => [],
            ];
        }

        $isSimple = $this->isSimpleInteraction($message);

        $aiBookingData = [
            'party_size' => null,
            'time' => null,
            'date' => null,
            'customer_name' => null,
            'customer_phone' => null,
            'customer_email' => null,
            'preferences' => null,
            'origin' => null,
            'destination' => null,
        ];
        $aiIntentResult = [
            'intent' => 'unknown',
            'confidence' => 0.0,
            'reasoning' => null,
        ];

        // Try AI extraction only for custom natural language messages
        if (! $isSimple) {
            $aiBookingData = $this->aiService->extractBookingData($message, $previousConversation);
            $aiIntentResult = $this->aiService->detectIntent($message, $previousConversation);
        }

        $currentPartySize = $aiBookingData['party_size'] ?? $this->extractPartySize($normalizedMessage);
        $currentTime = $aiBookingData['time'] ?? $this->extractTime($normalizedMessage);
        if (preg_match('/^\s*(\d{1,2})\s*$/u', trim($message), $numericMatches) === 1) {
            if (in_array($previousStage, ['ready_to_confirm', 'answering_commercial_info', 'selecting_visit_time'], true)) {
                $currentTime = null;
            }

            if ($previousStage === 'collecting_taxi_details' && in_array('party_size', (array) data_get($previousConversation, 'missing_fields', []), true)) {
                $currentTime = null;
                $currentPartySize = (int) $numericMatches[1];
            }

            if ($previousStage === 'collecting_taxi_details' && in_array('date', (array) data_get($previousConversation, 'missing_fields', []), true)) {
                $currentTime = null;
            }
        }
        $fallbackDate = $this->extractDate($normalizedMessage);
        $currentDate = $aiBookingData['date'] ?? $fallbackDate;
        if ($fallbackDate !== null && isset($currentDate['value']) && CarbonImmutable::parse((string) $currentDate['value'], 'Europe/Madrid')->isPast()) {
            $currentDate = $fallbackDate;
        }

        $currentRoute = $this->extractTaxiRoute($message);
        $currentOrigin = $aiBookingData['origin'] ?? $aiBookingData['pickup'] ?? $currentRoute['origin'] ?? $this->extractImplicitTaxiOrigin($message);
        $currentDestination = $aiBookingData['destination'] ?? $currentRoute['destination'];
        $intent = ($aiIntentResult['intent'] !== 'unknown') ? $aiIntentResult['intent'] : $this->detectIntent($normalizedMessage, $previousConversation);
        $packagePreset = $this->packagePresetFromMessage($normalizedMessage);
        if ($packagePreset === 'visit_taxi') {
            $intent = 'winery_visit';
        } elseif ($packagePreset === 'restaurant_taxi') {
            $intent = 'restaurant_booking';
        }

        // Extract contact details from AI or fall back to regex
        $customerName = $aiBookingData['customer_name'] ?? null;
        $customerPhone = $aiBookingData['customer_phone'] ?? null;
        $customerEmail = $aiBookingData['customer_email'] ?? null;

        $extractedContact = null;
        if (($customerName === null || $customerPhone === null || $customerEmail === null) && $this->looksLikeContactInfo($message)) {
            $extractedContact = $this->extractContactDetails($message);
            if ($extractedContact['name'] !== null && $customerName === null) {
                $customerName = $extractedContact['name'];
            }
            if ($extractedContact['phone'] !== null && $customerPhone === null) {
                $customerPhone = $extractedContact['phone'];
            }
            if ($extractedContact['email'] !== null && $customerEmail === null) {
                $customerEmail = $extractedContact['email'];
            }
        }
        $isBookingConfirmation = in_array($previousStage, ['ready_to_confirm', 'booking_confirmed'], true)
            && $this->isAffirmativeReply($normalizedMessage);

        if ($isBookingConfirmation) {
            $intent = $previousIntent;
            $quickReplyAction = 'confirm_booking';
            $currentDate = data_get($previousConversation, 'date');
            $currentTime = data_get($previousConversation, 'time');
            $currentPartySize = data_get($previousConversation, 'party_size');
            $currentOrigin = data_get($previousConversation, 'origin');
            $currentDestination = data_get($previousConversation, 'destination');
            $hasContactData = $this->hasContactData($customerName, $customerPhone, $customerEmail, $previousConversation);
            if (! $hasContactData && in_array($previousIntent, ['restaurant_booking', 'winery_visit'], true)) {
                $previousStage = 'intent_confirmed';
            } else {
                $previousStage = 'booking_confirmed';
            }
        } elseif ($previousStage === 'collecting_taxi_details' && $intent === 'unknown' && in_array('date', (array) data_get($previousConversation, 'missing_fields', []), true)) {
            if (preg_match('/^\s*(\d+)\s*\)?\s*$/u', trim($message), $matches) === 1) {
                $choice = (int) $matches[1];
                if ($choice === 1) {
                    $currentDate = $this->datePayload('hoy', CarbonImmutable::today('Europe/Madrid'));
                    $quickReplyAction = 'select_taxi_date_today';
                } elseif ($choice === 2) {
                    $currentDate = $this->datePayload('mañana', CarbonImmutable::tomorrow('Europe/Madrid'));
                    $quickReplyAction = 'select_taxi_date_tomorrow';
                } elseif ($choice === 3) {
                    $intent = 'taxi_booking';
                    $quickReplyAction = 'select_taxi_date_other';
                }
                if ($currentDate !== null) {
                    $intent = 'taxi_booking';
                }
            }
        } elseif ($previousStage === 'selecting_visit_time' && $previousIntent === 'winery_visit') {
            $slot = null;
            if (preg_match('/^\s*(\d+)\s*\)?\s*$/u', trim($message), $matches) === 1) {
                $slot = data_get($previousConversation, 'context.visit_slots.'.((int) $matches[1] - 1));
            }

            if (is_string($slot) && $slot !== '') {
                $intent = 'winery_visit';
                $currentTime = ['label' => $slot, 'value' => $slot];
                $quickReplyAction = 'select_visit_time';
                $context['selected_visit_time'] = true;
            }
        } elseif ($previousStage === 'intent_confirmed' && in_array($previousIntent, ['winery_visit', 'restaurant_booking'], true) && $extractedContact !== null) {
            $intent = $previousIntent;
        } elseif ($previousStage === 'ready_to_confirm') {
            $choice = null;
            if (preg_match('/^\s*(\d+)\s*\)?\s*$/u', trim($message), $matches) === 1) {
                $choice = (int) $matches[1];
                if ($choice === 1) {
                    $hasContactData = $this->hasContactData($customerName, $customerPhone, $customerEmail, $previousConversation);
                    $quickReplyAction = 'confirm_booking';
                    if (! $hasContactData) {
                        $intent = $previousIntent;
                        $previousStage = 'intent_confirmed';
                    } else {
                        $intent = $previousIntent;
                        $previousStage = 'booking_confirmed';
                    }
                } elseif ($choice === 2) {
                    $intent = 'taxi_booking';
                    $quickReplyAction = 'add_taxi';
                    $previousDate = data_get($previousConversation, 'date');
                    $previousTime = data_get($previousConversation, 'time');
                    $previousPartySize = data_get($previousConversation, 'party_size');
                    $business = data_get($previousConversation, 'business');

                    if ($currentDate === null && $previousDate !== null) {
                        $currentDate = $previousDate;
                    }
                    if ($currentPartySize === null && $previousPartySize !== null) {
                        $currentPartySize = $previousPartySize;
                    }
                    if ($currentDestination === null && $business !== null) {
                        $currentDestination = $business;
                    }

                    // Preserve original visit context for package creation
                    $context['original_intent'] = $previousIntent;
                    $context['original_service_id'] = data_get($previousConversation, 'service_id');
                    $context['original_service_name'] = data_get($previousConversation, 'service_name') ?? data_get($previousConversation, 'business');
                    $context['original_date'] = $previousDate;
                    $context['original_time'] = $previousTime;
                    $context['original_party_size'] = $previousPartySize;
                    $context['original_unit_price'] = data_get($previousConversation, 'unit_price');
                } elseif ($choice === 3 && in_array($previousIntent, ['winery_visit', 'restaurant_booking'], true)) {
                    $intent = 'restaurant_booking';
                    $quickReplyAction = 'add_restaurant';
                    $currentDate = data_get($previousConversation, 'date');
                    $currentPartySize = data_get($previousConversation, 'party_size');
                } elseif ($choice === 4) {
                    $intent = 'product_info';
                    $quickReplyAction = 'show_product_info';
                }
            } elseif ($previousIntent === 'product_purchase' && $intent === 'unknown') {
                if (preg_match('/^\s*(\d+)\s*\)?\s*$/u', trim($message), $matches) === 1) {
                    $choice = (int) $matches[1];
                    if ($choice === 1) {
                        $intent = 'product_purchase';
                        $previousStage = 'lanzaloe_purchase';
                        $quickReplyAction = 'buy_lanzaloe';
                    } elseif ($choice === 2) {
                        $intent = 'product_purchase';
                        $previousStage = 'lageria_purchase';
                        $quickReplyAction = 'buy_lageria';
                    }
                }
            } elseif ($this->hasBookingDetails($currentDate, $currentTime, $currentPartySize)) {
                $intent = $previousIntent;
            }

            if (! $this->hasBookingDetails($currentDate, $currentTime, $currentPartySize)) {
                $previousIntent = 'unknown';
            }

            $isConfirming = $choice === 1;
            if ($isConfirming && $intent === $previousIntent) {
                $previousStage = 'booking_confirmed';
            } else {
                $previousConversation = $previousConversation;
            }
        }

        // Preserve context when switching from visit to taxi
        if ($intent === 'taxi_booking' && in_array($previousIntent, ['winery_visit', 'restaurant_booking'], true)) {
            if ($currentDate === null) {
                $currentDate = data_get($previousConversation, 'date');
            }
            if ($currentTime === null && ! isset($context['original_intent'])) {
                $currentTime = data_get($previousConversation, 'time');
            }
            if ($currentPartySize === null) {
                $currentPartySize = data_get($previousConversation, 'party_size');
            }
            // Infer destination from visit context
            if ($currentDestination === null && $currentOrigin === null) {
                $business = data_get($previousConversation, 'business');
                if ($business !== null) {
                    $currentDestination = $business;
                }
            }
            // Also preserve if user said "con taxi" - force context inheritance
            if (str_contains($normalizedMessage, 'con taxi') || str_contains($normalizedMessage, 'con traslado') || isset($context['original_intent'])) {
                if ($currentDate === null) {
                    $currentDate = data_get($previousConversation, 'date');
                }
                if ($currentTime === null && ! isset($context['original_intent'])) {
                    $currentTime = data_get($previousConversation, 'time');
                }
                if ($currentPartySize === null) {
                    $currentPartySize = data_get($previousConversation, 'party_size');
                }
                if ($currentDestination === null) {
                    $business = data_get($previousConversation, 'business');
                    if ($business !== null) {
                        $currentDestination = $business;
                    }
                }
            }
        }

        $isStartingPackageTaxi = $intent === 'taxi_booking' && isset($context['original_intent']) && in_array($previousIntent, ['winery_visit', 'restaurant_booking'], true);

        $partySize = $currentPartySize ?? data_get($previousConversation, 'party_size');
        $time = $isStartingPackageTaxi ? $currentTime : ($currentTime ?? data_get($previousConversation, 'time'));
        $date = $currentDate ?? data_get($previousConversation, 'date');
        $origin = $currentOrigin ?? data_get($previousConversation, 'origin');
        $destination = $currentDestination ?? data_get($previousConversation, 'destination');
        $customerName = $customerName ?? data_get($previousConversation, 'customer_name');
        $customerPhone = $customerPhone ?? data_get($previousConversation, 'customer_phone');
        $customerEmail = $customerEmail ?? data_get($previousConversation, 'customer_email');
        $preferences = $aiBookingData['preferences'] ?? data_get($previousConversation, 'preferences');

        if ($intent === 'taxi_booking') {
            $shortPlace = $this->extractShortPlaceAnswer($message);

            if ($shortPlace !== null && data_get($previousConversation, 'origin') !== null && data_get($previousConversation, 'destination') === null && $currentDestination === null) {
                $origin = data_get($previousConversation, 'origin');
                $destination = $shortPlace;
            }

            if ($shortPlace !== null && data_get($previousConversation, 'destination') !== null && data_get($previousConversation, 'origin') === null && $currentOrigin === null) {
                $destination = data_get($previousConversation, 'destination');
                $origin = $shortPlace;
            }
        }

        /*if ($previousStage === 'selecting_intent' || $previousIntent === 'commercial_info') {
            $intent = $this->intentFromSelection($normalizedMessage, $previousConversation) ?? $intent;
        }*/

        if (in_array($intent, ['winery_visit', 'restaurant_booking'], true)) {
            $selectedPackagePreset = $packagePreset ?? $this->packagePresetFromSelection($normalizedMessage, $previousConversation);
            if ($selectedPackagePreset !== null) {
                $context['package_preset'] = $selectedPackagePreset;
                $context['expects_taxi'] = true;
            }
        }

        if ($intent !== 'unknown' && $previousIntent !== 'unknown' && $intent !== $previousIntent && ! in_array($quickReplyAction, ['add_taxi', 'add_restaurant'], true)) {
            $partySize = $this->extractPartySize($normalizedMessage);
            $time = $this->extractTime($normalizedMessage);
            $date = $this->extractDate($normalizedMessage);
            $customerName = null;
            $customerEmail = null;
            $preferences = null;
            $origin = $currentOrigin;
            $destination = $currentDestination;
        }

        if ($intent === 'unknown' && $this->hasBookingDetails($date, $time, is_numeric($partySize) ? (int) $partySize : null)) {
            $intent = (string) data_get($previousConversation, 'intent', 'unknown');
        }

        if ($intent === 'unknown' && $previousStage !== '') {
            $intent = (string) data_get($previousConversation, 'intent', 'unknown');
        }

        if ($previousStage === 'awaiting_customer_name' && ! $this->isAffirmativeReply($normalizedMessage) && ! $this->hasBookingDetails($this->extractDate($normalizedMessage), $this->extractTime($normalizedMessage), $this->extractPartySize($normalizedMessage))) {
            $customerName = $extractedContact['name'] ?? trim($message);
            $customerPhone = $extractedContact['phone'] ?? null;
            $customerEmail = $extractedContact['email'] ?? null;
            // Preserve booking details from previous conversation
            $partySize = $partySize ?? data_get($previousConversation, 'party_size');
            $time = $time ?? data_get($previousConversation, 'time');
            $date = $date ?? data_get($previousConversation, 'date');
            $origin = $origin ?? data_get($previousConversation, 'origin');
            $destination = $destination ?? data_get($previousConversation, 'destination');
        }

        // Use pre-extracted contact details if available
        if ($extractedContact !== null) {
            if ($extractedContact['name'] !== null && $customerName === null) {
                $customerName = $extractedContact['name'];
            }
            if ($extractedContact['phone'] !== null && $customerPhone === null) {
                $customerPhone = $extractedContact['phone'];
            }
        }

        // Always preserve contact details from previous conversation if not in current message
        if ($customerName === null) {
            $customerName = data_get($previousConversation, 'customer_name');
        }
        if ($customerPhone === null) {
            $customerPhone = data_get($previousConversation, 'customer_phone');
        }
        if ($customerEmail === null) {
            $customerEmail = data_get($previousConversation, 'customer_email');
        }

        if ($previousStage === 'awaiting_preferences') {
            $preferences = $this->extractPreferences($message);
        }

        $missingFields = [];
        $missingLabels = [];

        $needsBookingDetails = in_array($intent, ['restaurant_booking', 'restaurant_and_winery_visit', 'winery_visit', 'taxi_booking'], true);

        if ($needsBookingDetails) {
            if ($date === null) {
                $missingFields[] = 'date';
                $missingLabels[] = 'día';
            }

            if ($time === null) {
                $missingFields[] = 'time';
                $missingLabels[] = 'hora';
            }

            if ($partySize === null) {
                $missingFields[] = 'party_size';
                $missingLabels[] = 'número de personas';
            }

            if ($intent === 'taxi_booking') {
                if ($origin === null) {
                    $missingFields[] = 'origin';
                    $missingLabels[] = 'origen';
                }

                if ($destination === null) {
                    $missingFields[] = 'destination';
                    $missingLabels[] = 'destino';
                }
            }
        }

        if ($intent === 'winery_visit' && $date !== null && $partySize !== null && ! (bool) data_get($context, 'selected_visit_time', false)) {
            $missingFields = [];
            $missingLabels = [];
        }

        $stage = $isBookingConfirmation
            ? 'booking_confirmed'
            : $this->resolveStage($intent, $missingFields, is_string($customerName) ? $customerName : null, is_string($customerPhone) ? $customerPhone : null, is_string($customerEmail) ? $customerEmail : null, is_string($preferences) ? $preferences : null, $previousStage);

        if ($intent === 'winery_visit' && $date !== null && $partySize !== null && ! (bool) data_get($context, 'selected_visit_time', false)) {
            $stage = 'selecting_visit_time';
        }

        return [
            'tourist_phone' => $touristPhone,
            'intent' => $intent,
            'stage' => $stage,
            'is_simple' => $isSimple,
            'date_label' => $date['label'] ?? null,
            'date' => $date,
            'time_label' => $time['label'] ?? null,
            'time' => $time,
            'party_size' => $partySize === null ? null : (int) $partySize,
            'customer_name' => is_string($customerName) && $customerName !== '' ? $customerName : null,
            'customer_phone' => is_string($customerPhone) && $customerPhone !== '' ? $customerPhone : null,
            'customer_email' => is_string($customerEmail) && $customerEmail !== '' ? $customerEmail : null,
            'preferences' => is_string($preferences) && $preferences !== '' ? $preferences : null,
            'origin' => is_string($origin) && $origin !== '' ? $origin : null,
            'destination' => is_string($destination) && $destination !== '' ? $destination : null,
            'missing_fields' => $missingFields,
            'missing_labels' => $missingLabels,
            'previous_intent' => $previousIntent,
            'previous_stage' => $previousStage,
            'last_menu' => $this->lastMenuForStage($stage, $intent),
            'quick_reply_action' => $quickReplyAction,
            'context' => $context ?? [],
        ];
    }

    public function detectIntent(string $message, ?array $previousConversation = null): string
    {
        if ($this->isSystemInfoQuery($message)) {
            return 'system_info';
        }

        $selectionIntent = $this->intentFromSelection($message);

        if ($selectionIntent !== null) {
            return $selectionIntent;
        }

        $previousIntent = (string) data_get($previousConversation, 'intent', 'unknown');

        if ($previousIntent === 'taxi_booking' && $this->hasAnyTerm($message, ['bodega', 'geria', 'la geria'])) {
            return 'taxi_booking';
        }

        $configuredIntent = $this->detectIntentFromFilamentRules($message, $previousConversation);

        if ($configuredIntent !== null) {
            return $configuredIntent;
        }

        if ($this->hasAnyTerm($message, ['vinoterapia', 'cosmético', 'cosmetico', 'crema', 'aloe vera', 'comprar', 'tienda', 'shop'])
            && ! $this->hasAnyTerm($message, ['reservar', 'reserva', 'mesa', 'visita'])) {
            return 'product_purchase';
        }

        if ($this->isCommercialInfoRequest($message)) {
            return 'commercial_info';
        }

        $packagePreset = $this->packagePresetFromMessage($message);

        if ($packagePreset === 'visit_taxi') {
            return 'winery_visit';
        }

        if ($packagePreset === 'restaurant_taxi') {
            return 'restaurant_booking';
        }

        // Check for winery visit FIRST (before taxi) to avoid confusion
        if ($this->hasAnyTerm($message, ['visita', 'visitar', 'bodega', 'geria', 'wine tour', 'cata', 'excursión', 'excursion', 'tour'])
            && ! $this->hasAnyTerm($message, ['vinoterapia', 'cosmético', 'cosmetico', 'crema', 'comprar'])) {
            return 'winery_visit';
        }

        if ($this->hasAnyTerm($message, ['restaurante', 'reserva', 'reservar', 'mesa', 'comer', 'cenar', 'almorzar', 'comida', 'carta', 'menú', 'menu', 'taberna', 'cepa'])) {
            return $this->hasAnyTerm($message, ['visita', 'bodega', 'geria', 'wine tour', 'tour'])
                ? 'restaurant_and_winery_visit'
                : 'restaurant_booking';
        }

        if ($this->hasAnyTerm($message, ['taxi', 'taxis', 'traslado', 'trasladar', 'transfer', 'recoger', 'recogida', 'llevar', 'llévame', 'llevame', 'aeropuerto', 'puerto'])) {
            return 'taxi_booking';
        }

        return 'unknown';
    }

    private function detectIntentFromFilamentRules(string $message, ?array $previousConversation): ?string
    {
        try {
            $businessSlug = data_get($previousConversation, 'business_slug');
            $businessId = null;

            if (is_string($businessSlug) && $businessSlug !== '') {
                $businessId = NovaBusiness::query()
                    ->where('slug', $businessSlug)
                    ->value('id');
            }

            $rules = NovaIntentRule::query()
                ->active()
                ->ofType('include')
                ->where(function ($query) use ($businessId): void {
                    $query->whereNull('nova_business_id');

                    if ($businessId !== null) {
                        $query->orWhere('nova_business_id', $businessId);
                    }
                })
                ->get();

            foreach ($rules as $rule) {
                foreach ($rule->keywords ?? [] as $keyword) {
                    $keyword = mb_strtolower((string) $keyword);

                    if ($keyword !== '' && str_contains($message, $keyword)) {
                        return $rule->intent_key;
                    }
                }
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }

    private function isSystemInfoQuery(string $message): bool
    {
        $configuredServiceTopics = $this->keywordsForConfiguredIntentRule('commercial_info', 'system_topic');
        $configuredSystemKeywords = $this->keywordsForConfiguredIntentRule('system_info', 'include');

        $serviceTopics = $configuredServiceTopics !== [] ? $configuredServiceTopics : [
            'restaurante', 'restaurantes', 'bodega', 'bodegas',
            'taxi', 'taxis', 'traslado', 'transfer',
            'producto', 'productos', 'aloe', 'vinoterapia',
            'lanzaloe', 'geria', 'vino', 'vinos', 'tienda',
            'comer', 'cenar', 'reservar', 'reserva', 'mesa',
            'visita', 'visitas', 'tour', 'excursion', 'excursión', 'actividad',
            'hotel', 'hoteles', 'alojamiento', 'hospedaje',
            'ruta', 'rutas', 'itinerario',
        ];

        $systemKeywords = $configuredSystemKeywords !== [] ? $configuredSystemKeywords : [
            'agente', 'agentes', 'servidor', 'servidores',
            'herramienta', 'herramientas',
            'conexion', 'conexiones', 'conectado', 'conectados',
            'que puedes', 'que tienes', 'capacidad', 'capacidades',
            'estado de conexion', 'ping', 'accesible',
            'que agente', 'que servidor', 'quien gestiona',
            'mcp',
        ];

        foreach ($systemKeywords as $kw) {
            if (str_contains($message, $kw)) {
                return true;
            }
        }

        // 'listado' / 'lista de' only count as system_info when NOT about a service topic
        $hasListKeyword = str_contains($message, 'listado') || str_contains($message, 'lista de');
        if ($hasListKeyword) {
            foreach ($serviceTopics as $topic) {
                if (str_contains($message, $topic)) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    private function keywordsForConfiguredIntentRule(string $intent, string $ruleType): array
    {
        try {
            return NovaIntentRule::query()
                ->active()
                ->forIntent($intent)
                ->ofType($ruleType)
                ->get()
                ->flatMap(fn (NovaIntentRule $rule): array => $rule->keywords ?? [])
                ->map(fn (mixed $keyword): string => mb_strtolower((string) $keyword))
                ->filter(fn (string $keyword): bool => $keyword !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function intentFromSelection(string $message, ?array $previousConversation = null): ?string
    {
        $message = trim($message);

        if (data_get($previousConversation, 'intent') === 'commercial_info') {
            return match ($message) {
                '1', 'bodega', 'bodegas', 'vino', 'vinos' => 'winery_visit',
                '2', 'timanfaya', 'parque', 'volcan', 'volcán' => 'commercial_info',
                '3', 'playa', 'playas', 'papagayo' => 'commercial_info',
                '4', 'gastronomia', 'gastronomía', 'restaurante', 'comida', 'cepa', 'taberna' => 'restaurant_booking',
                '5', 'taxi', 'traslado', 'transfer' => 'taxi_booking',
                default => null,
            };
        }

        return match ($message) {
            '1', 'restaurante' => 'restaurant_booking',
            '2', 'visita', 'bodega' => 'winery_visit',
            '3', 'taxi' => 'taxi_booking',
            '4', 'productos', 'geria' => 'product_info',
            '5', 'visita taxi','visita + taxi','visita con taxi', 'visita más taxi', 'visita mas taxi', 'bodega taxi', 'bodega más taxi', 'bodega mas taxi' => 'winery_visit',
            '6', 'restaurante taxi', 'restaurante más taxi', 'restaurante mas taxi', 'mesa taxi', 'mesa más taxi', 'mesa mas taxi' => 'restaurant_booking',
            '7', 'info', 'información', 'informacion' => 'commercial_info',
            default => null,
        };
    }

    private function packagePresetFromMessage(string $message): ?string
    {
        $message = trim($message);

        if ($this->hasAnyTerm($message, ['visita con taxi', 'visita más taxi', 'visita mas taxi', 'visita + taxi', 'bodega con taxi', 'bodega más taxi', 'bodega mas taxi', 'bodega + taxi'])) {
            return 'visit_taxi';
        }

        if ($this->hasAnyTerm($message, ['restaurante con taxi', 'restaurante más taxi', 'restaurante mas taxi', 'restaurante + taxi', 'mesa con taxi', 'mesa más taxi', 'mesa mas taxi', 'mesa + taxi'])) {
            return 'restaurant_taxi';
        }

        return null;
    }

    private function packagePresetFromSelection(string $message, ?array $previousConversation = null): ?string
    {
        if (data_get($previousConversation, 'intent') === 'commercial_info') {
            return null;
        }

        return match (trim($message)) {
            '5', 'visita taxi', 'bodega taxi' => 'visit_taxi',
            '6', 'restaurante taxi', 'mesa taxi' => 'restaurant_taxi',
            default => null,
        };
    }

    private function isCommercialInfoRequest(string $message): bool
    {
        if (in_array(trim($message), ['hacer', 'reservar', 'reserva'], true)) {
            return false;
        }

        $infoTerms = ['info', 'información', 'informacion', 'dime', 'cuéntame', 'cuentame', 'qué tipos', 'que tipos', 'tipos de', 'disponible', 'disponibles', 'ofrece', 'ofrecen', 'tienen', 'cuanto cuesta', 'cuánto cuesta', 'precio', 'precios', 'cuanto dura', 'cuánto dura', 'duración', 'duracion', 'contacto', 'telefono', 'teléfono', 'email', 'qué puedo comer', 'que puedo comer', 'carta', 'menú', 'menu', 'horario', 'cocina', 'qué hacer', 'que hacer', 'puedo hacer', 'planes', 'recomienda', 'recomiéndame', 'recomiendame', 'opciones', 'excursiones', 'listado', 'lista', 'obtener', 'ver', 'cuales', 'cuáles', 'muestra', 'dame', 'hay'];
        $commercialTerms = ['bodega', 'geria', 'vino', 'vinos', 'visita', 'visitas', 'taxi', 'taxis', 'traslado', 'traslados', 'lanzaloe', 'aloe', 'vinoterapia', 'restaurante', 'comida', 'cangrejo rojo', 'producto', 'productos', 'taberna', 'cepa', 'tapas', 'excursión', 'excursion', 'tour', 'hacer', 'hotel', 'hoteles', 'alojamiento', 'hospedaje', 'habitacion', 'ruta', 'rutas', 'itinerario', 'actividad', 'actividades'];

        if (! $this->hasAnyTerm($message, $infoTerms)) {
            return false;
        }

        return $this->hasAnyTerm($message, $commercialTerms);
    }

    /**
     * @param  array<int, string>  $terms
     */
    private function hasAnyTerm(string $message, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($message, $term)) {
                return true;
            }
        }

        return false;
    }

    private function extractPartySize(string $message): ?int
    {
        if (preg_match('/\bpara\s+(\d{1,2})\b/u', $message, $matches) === 1) {
            return (int) $matches[1];
        }

        // Also match "X personas" pattern
        if (preg_match('/\b(\d{1,2})\s+personas\b/u', $message, $matches) === 1) {
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
            'nueve' => 9,
            'diez' => 10,
        ];

        foreach ($words as $word => $value) {
            if (str_contains($message, "para {$word}")) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array{origin: string|null, destination: string|null}
     */
    private function extractTaxiRoute(string $message): array
    {
        $cleanMessage = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);

        if (preg_match('/^\s*(?:hoy|mañana|pasado|para\s+\d+|\d+\s+personas|\d{1,2}[:.h]\d{2})\b/iu', $cleanMessage) === 1) {
            return ['origin' => null, 'destination' => null];
        }

        if (preg_match('/(?:\bde\s+)?(.+?)\s+a\s+(.+?)(?:\s+(?:para|por|mañana|hoy|a\s+las|\d{1,2}[:.h]\d{2}|\d{1,2}\s+personas)\b|$)/iu', $cleanMessage, $matches) !== 1) {
            return ['origin' => null, 'destination' => null];
        }

        $origin = trim($matches[1], " \t\n\r\0\x0B,.");
        $destination = trim($matches[2], " \t\n\r\0\x0B,.");

        return [
            'origin' => $origin !== '' ? $origin : null,
            'destination' => $destination !== '' ? $destination : null,
        ];
    }

    /**
     * @return array{label:string,value:string}|null
     */
    private function extractTime(string $message): ?array
    {
        if (preg_match('/\b([01]?\d|2[0-3])[:.h]([0-5]\d)\b/u', $message, $matches) === 1) {
            $value = sprintf('%02d:%02d', (int) $matches[1], (int) $matches[2]);

            return ['label' => $value, 'value' => $value];
        }

        if (preg_match('/\b([01]?\d|2[0-3])\s*(?:h|horas)\b/u', $message, $matches) === 1) {
            $value = sprintf('%02d:00', (int) $matches[1]);

            return ['label' => $value, 'value' => $value];
        }

        if (preg_match('/\ba\s+las\s+([01]?\d|2[0-3])\b/u', $message, $matches) === 1) {
            $value = sprintf('%02d:00', (int) $matches[1]);

            return ['label' => $value, 'value' => $value];
        }

        // Handle simple numbers as time when message is just a number
        if (preg_match('/^\s*(\d{1,2})\s*$/u', trim($message), $matches) === 1) {
            $hour = (int) $matches[1];
            if ($hour >= 0 && $hour <= 23) {
                $value = sprintf('%02d:00', $hour);

                return ['label' => $value, 'value' => $value];
            }
        }

        if (str_contains($message, 'tarde')) {
            return ['label' => 'tarde', 'value' => '21:00'];
        }

        if (str_contains($message, 'mediodía') || str_contains($message, 'medio dia')) {
            return ['label' => 'mediodía', 'value' => '14:00'];
        }

        return null;
    }

    /**
     * @return array{label:string,value:string,weekday:string}|null
     */
    private function extractDate(string $message): ?array
    {
        $today = CarbonImmutable::now('Europe/Madrid');

        if (str_contains($message, 'mañana')) {
            return $this->datePayload('mañana', $today->addDay());
        }

        if (str_contains($message, 'hoy')) {
            return $this->datePayload('hoy', $today);
        }

        if (preg_match('/\b(\d{1,2})[\/-](\d{1,2})(?:[\/-](\d{2,4}))?\b/u', $message, $matches) === 1) {
            $year = isset($matches[3]) ? (int) $matches[3] : (int) $today->year;
            $year = $year < 100 ? 2000 + $year : $year;
            $date = CarbonImmutable::createFromDate($year, (int) $matches[2], (int) $matches[1], 'Europe/Madrid');

            return $this->datePayload($matches[0], $date);
        }

        $months = [
            'enero' => 1,
            'febrero' => 2,
            'marzo' => 3,
            'abril' => 4,
            'mayo' => 5,
            'junio' => 6,
            'julio' => 7,
            'agosto' => 8,
            'septiembre' => 9,
            'setiembre' => 9,
            'octubre' => 10,
            'noviembre' => 11,
            'diciembre' => 12,
        ];

        if (preg_match('/\b(\d{1,2})\s+de\s+('.implode('|', array_keys($months)).')(?:\s+de\s+(\d{4}))?\b/u', $message, $matches) === 1) {
            $year = isset($matches[3]) ? (int) $matches[3] : (int) $today->year;
            $date = CarbonImmutable::createFromDate($year, $months[$matches[2]], (int) $matches[1], 'Europe/Madrid');

            if (! isset($matches[3]) && $date->isPast()) {
                $date = $date->addYear();
            }

            return $this->datePayload($matches[0], $date);
        }

        return null;
    }

    /**
     * @return array{label:string,value:string,weekday:string}
     */
    private function datePayload(string $label, CarbonImmutable $date): array
    {
        return [
            'label' => $label,
            'value' => $date->toDateString(),
            'weekday' => ucfirst($date->locale('es')->dayName),
        ];
    }

    private function hasBookingDetails(?array $date, ?array $time, ?int $partySize): bool
    {
        return $date !== null || $time !== null || $partySize !== null;
    }

    private function hasContactData(?string $customerName, ?string $customerPhone, ?string $customerEmail, ?array $previousConversation): bool
    {
        return $customerName !== null
            || $customerPhone !== null
            || $customerEmail !== null
            || data_get($previousConversation, 'customer_name') !== null
            || data_get($previousConversation, 'customer_phone') !== null
            || data_get($previousConversation, 'customer_email') !== null;
    }

    private function lastMenuForStage(string $stage, string $intent): ?string
    {
        if ($stage === 'ready_to_confirm') {
            return 'ready_to_confirm:'.$intent;
        }

        if ($stage === 'collecting_taxi_details') {
            return 'collecting_taxi_details';
        }

        if ($stage === 'answering_commercial_info') {
            return 'commercial_info';
        }

        return null;
    }

    private function extractPreferences(string $message): string
    {
        $normalizedMessage = mb_strtolower(trim($message));

        if (in_array($normalizedMessage, ['no', 'ninguna', 'ninguno', 'sin alergias', 'sin preferencias'], true)) {
            return 'Sin alergias ni preferencias';
        }

        return trim($message);
    }

    private function isAffirmativeReply(string $message): bool
    {
        return in_array(trim($message), ['sí', 'si', 'ok', 'vale', 'correcto', 'confirmar', 'confirmar con la visita', 'confirmo', 'adelante', '1'], true);
    }

    /**
     * @param  array<int, string>  $missingFields
     */
    private function resolveStage(string $intent, array $missingFields, ?string $customerName, ?string $customerPhone, ?string $customerEmail, ?string $preferences, string $previousStage = ''): string
    {
        // Handle booking_confirmed stage explicitly
        if ($previousStage === 'booking_confirmed') {
            return 'booking_confirmed';
        }

        if ($intent === 'commercial_info') {
            return 'answering_commercial_info';
        }

        if ($intent === 'product_info' || $intent === 'product_purchase') {
            if ($previousStage === 'lanzaloe_purchase') {
                return 'lanzaloe_purchase';
            }
            if ($previousStage === 'lageria_purchase') {
                return 'lageria_purchase';
            }

            return 'answering_product_info';
        }

        if ($intent === 'system_info') {
            return 'answering_system_info';
        }

        if ($intent === 'taxi_booking') {
            if ($missingFields !== []) {
                return 'collecting_taxi_details';
            }

            // If coming from intent_confirmed with new contact data, go to ready_to_confirm
            if ($previousStage === 'intent_confirmed' && ($customerName !== null || $customerPhone !== null || $customerEmail !== null)) {
                return 'ready_to_confirm';
            }

            // Go directly to ready_to_confirm with numbered options
            return 'ready_to_confirm';
        }

        if (! in_array($intent, ['restaurant_booking', 'restaurant_and_winery_visit', 'winery_visit'], true)) {
            return 'selecting_intent';
        }

        if ($missingFields !== []) {
            return 'collecting_booking_details';
        }

        if ($intent === 'winery_visit' && $previousStage === 'selecting_visit_time' && $customerName === null && $customerPhone === null && $customerEmail === null) {
            return 'intent_confirmed';
        }

        // If coming from intent_confirmed with new contact data, go to ready_to_confirm
        if ($previousStage === 'intent_confirmed' && ($customerName !== null || $customerPhone !== null || $customerEmail !== null)) {
            return 'ready_to_confirm';
        }

        // Go directly to ready_to_confirm with numbered options
        return 'ready_to_confirm';
    }

    /**
     * Check if message is a simple, structured interaction (menu number, click button, slot, simple yes/no).
     */
    private function isSimpleInteraction(string $message): bool
    {
        $trimmed = trim($message);

        // 1. Just a number (like '1', '2', '3', '4')
        if (is_numeric($trimmed) && strlen($trimmed) <= 2) {
            return true;
        }

        // 2. Just a time/slot (like '11:00', '16:00', '13:00')
        if (preg_match('/^([01]?\d|2[0-3])[:.h]([0-5]\d)$/u', $trimmed) === 1) {
            return true;
        }

        // 3. Simple yes/no/confirm/button selections
        $lower = mb_strtolower($trimmed);
        $simplePhrases = [
            'sí', 'si', 'no', 'confirmar', 'confirmo', 'ok', 'vale', 'bien',
            'correcto',
            'aportar datos', 'cambiar horario', 'servicios latepoint',
            'productos la geria', 'próximas reservas', 'tools mcp',
            'restaurante', 'visita', 'taxi', 'info',
        ];

        if (in_array($lower, $simplePhrases, true)) {
            return true;
        }

        // 4. Starts with a command pattern or number selection like "1. Opcion"
        if (preg_match('/^\d+[\.\)\s]/', $trimmed) === 1) {
            return true;
        }

        return false;
    }

    /**
     * Extract name and phone/email from contact message.
     *
     * @return array{name:string|null,phone:string|null,email:string|null}
     */
    private function extractContactDetails(string $message): array
    {
        $normalizedMessage = trim($message);
        $name = null;
        $phone = null;
        $email = null;

        // Extract phone number (Spanish format: 6XX XXX XXX or 9XX XXX XXX)
        if (preg_match('/(\d{9})\b/', $normalizedMessage, $matches) === 1) {
            $phone = $matches[1];
        }

        // Extract email
        if (preg_match('/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/', $normalizedMessage, $matches) === 1) {
            $email = $matches[1];
        }

        // Extract name (remove phone/email and common booking words from message)
        $name = $normalizedMessage;

        // Remove common booking words to isolate the name
        $bookingWords = ['reserva', 'reservar', 'visita', 'guiada', 'mañana', 'personas', 'para', 'elegir', 'horario'];
        foreach ($bookingWords as $word) {
            $name = str_ireplace($word, '', $name);
        }

        // Remove phone number if present
        if ($phone !== null) {
            $name = str_replace($phone, '', $name);
        }
        if ($email !== null) {
            $name = str_replace($email, '', $name);
        }

        // Remove standalone numbers (likely party size) but keep if part of name
        $name = preg_replace('/\b\d{1,2}\b(?=\s|$)/', '', $name);

        // Clean up extra spaces
        $name = preg_replace('/^\s+|\s+$/', '', $name);
        $name = preg_replace('/\s+/', ' ', $name);

        return [
            'name' => $name !== '' ? $name : null,
            'phone' => $phone,
            'email' => $email,
        ];
    }

    private function extractShortPlaceAnswer(string $message): ?string
    {
        $cleanMessage = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);

        if ($cleanMessage === '' || mb_strlen($cleanMessage) > 80) {
            return null;
        }

        $normalized = mb_strtolower($cleanMessage);
        if ($this->hasBookingDetails($this->extractDate($normalized), $this->extractTime($normalized), $this->extractPartySize($normalized))) {
            return null;
        }

        if (preg_match('/[?¿!¡]/u', $cleanMessage) === 1) {
            return null;
        }

        return trim($cleanMessage, " \t\n\r\0\x0B,.");
    }

    private function extractImplicitTaxiOrigin(string $message): ?string
    {
        $cleanMessage = trim(preg_replace('/\s+/u', ' ', $message) ?? $message);

        if (preg_match('/(?:desde\s+|en\s+)?(aeropuerto(?:\s+de\s+lanzarote)?)/iu', $cleanMessage, $matches) === 1) {
            return trim($matches[1]);
        }

        if (preg_match('/(?:desde\s+|en\s+)?(hotel\s+[^,.;!?]+)/iu', $cleanMessage, $matches) === 1) {
            return trim($matches[1]);
        }

        return null;
    }

    /**
     * Check if message looks like it contains contact information.
     */
    private function looksLikeContactInfo(string $message): bool
    {
        // Must contain a phone number or email
        $hasPhone = preg_match('/\b\d{9}\b/', $message) === 1;
        $hasEmail = preg_match('/[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}/', $message) === 1;

        if (! $hasPhone && ! $hasEmail) {
            return false;
        }

        // Should not look like a time selection or booking command
        $excludedPatterns = [
            '/elegir/i',
            '/seleccionar/i',
            '/horario/i',
            '/opciones/i',
        ];

        foreach ($excludedPatterns as $pattern) {
            if (preg_match($pattern, $message) === 1) {
                return false;
            }
        }

        return true;
    }
}
