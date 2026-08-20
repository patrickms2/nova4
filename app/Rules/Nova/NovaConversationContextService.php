<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Models\NovaRequest;
use Illuminate\Support\Facades\Cache;

final class NovaConversationContextService
{
    private const CACHE_TTL = 86400; // 24 hours

    /**
     * Get conversation context for a phone number
     */
    public function getContext(string $phone): array
    {
        $cacheKey = "nova_context_{$phone}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($phone) {
            $recentConversations = NovaRequest::query()
                ->where('context->tourist_phone', $phone)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->toArray();

            return [
                'phone' => $phone,
                'recent_conversations' => collect($recentConversations)->map(fn ($req) => [
                    'intent' => data_get($req, 'context.conversation.intent'),
                    'date' => data_get($req, 'context.conversation.date.value'),
                    'time' => data_get($req, 'context.conversation.time.value'),
                    'party_size' => data_get($req, 'context.conversation.party_size'),
                    'business' => $this->extractBusiness((object) $req),
                    'created_at' => $req['created_at'],
                ])->toArray(),
                'detected_preferences' => $this->detectPreferences($recentConversations),
                'visited_businesses' => $this->getVisitedBusinesses($recentConversations),
                'patterns' => $this->detectPatterns($recentConversations),
            ];
        });
    }

    /**
     * Suggest based on context
     */
    public function suggestBasedOnContext(string $phone, string $currentIntent): ?string
    {
        $context = $this->getContext($phone);

        // If user visited La Geria before, suggest Lanzaloe
        if (in_array('la-geria', $context['visited_businesses'], true) && ! in_array('lanzaloe', $context['visited_businesses'], true)) {
            return match ($currentIntent) {
                'restaurant_booking' => '¿Te interesa visitar la finca de aloe vera de Lanzaloe después de cenar?',
                'winery_visit' => '¿Te gustaría probar los productos de aloe vera de Lanzaloe?',
                default => null,
            };
        }

        // If user visited Lanzaloe before, suggest La Geria
        if (in_array('lanzaloe', $context['visited_businesses'], true) && ! in_array('la-geria', $context['visited_businesses'], true)) {
            return match ($currentIntent) {
                'winery_visit' => '¿Te gustaría visitar la bodega de La Geria?',
                'restaurant_booking' => '¿Te interesa cenar en Taberna La Cepa?',
                default => null,
            };
        }

        // If user used taxi before, suggest again
        if (in_array('taxi_booking', array_column($context['recent_conversations'], 'intent'), true)) {
            return match ($currentIntent) {
                'restaurant_booking', 'winery_visit' => '¿Necesitas un taxi para llegar?',
                default => null,
            };
        }

        return null;
    }

    /**
     * Remember previous booking details naturally
     */
    public function rememberPreviousDetails(string $phone, string $currentIntent): ?string
    {
        $context = $this->getContext($phone);
        $lastConversation = $context['recent_conversations'][0] ?? null;

        if ($lastConversation === null) {
            return null;
        }

        $lastIntent = $lastConversation['intent'] ?? '';
        $lastPartySize = $lastConversation['party_size'] ?? null;
        $lastBusiness = $lastConversation['business'] ?? '';

        // Same intent, ask if same party size
        if ($lastIntent === $currentIntent && $lastPartySize !== null) {
            return "La última vez reservaste para {$lastPartySize} personas. ¿Es el mismo número o sois más/menos esta vez?";
        }

        // Different intent but same business
        if ($lastBusiness !== '' && $lastIntent !== $currentIntent) {
            return match ($currentIntent) {
                'restaurant_booking' => "Vi que visitaste {$lastBusiness} la última vez. ¿Te apetece cenar en Taberna La Cepa esta vez?",
                'winery_visit' => "Vi que estuviste en {$lastBusiness}. ¿Te interesa visitar la bodega?",
                default => null,
            };
        }

        return null;
    }

    private function extractBusiness(object|array $request): string
    {
        return data_get($request, 'context.conversation.business', '');
    }

    private function extractDietaryPreferences(string $preferences): array
    {
        $dietary = [];
        $terms = ['vegetariano', 'vegano', 'sin gluten', 'alergia', 'sin carne', 'sin pescado'];

        foreach ($terms as $term) {
            if (str_contains(mb_strtolower($preferences), $term)) {
                $dietary[] = $term;
            }
        }

        return $dietary;
    }

    private function extractTimingPreferences(string $preferences): array
    {
        $timing = [];
        $terms = ['mañana', 'tarde', 'noche', 'temprano', 'tarde'];

        foreach ($terms as $term) {
            if (str_contains(mb_strtolower($preferences), $term)) {
                $timing[] = $term;
            }
        }

        return $timing;
    }

    private function getVisitedBusinesses(array $conversations): array
    {
        $businesses = [];

        foreach ($conversations as $conversation) {
            $business = $conversation['business'] ?? '';
            if ($business !== '' && ! in_array($business, $businesses, true)) {
                $businesses[] = $business;
            }
        }

        return $businesses;
    }

    private function detectPatterns(array $conversations): array
    {
        $patterns = [];

        // Detect if user prefers morning bookings
        $morningCount = 0;
        foreach ($conversations as $conversation) {
            $time = $conversation['time'] ?? '';
            if ($time !== '' && (int) substr($time, 0, 2) < 14) {
                $morningCount++;
            }
        }

        if ($morningCount >= count($conversations) / 2) {
            $patterns[] = 'prefers_morning';
        }

        // Detect if user usually books for 2
        $partySizes = array_filter(array_column($conversations, 'party_size'));
        if (count($partySizes) > 0) {
            $avgPartySize = array_sum($partySizes) / count($partySizes);
            if ($avgPartySize <= 2) {
                $patterns[] = 'usually_small_group';
            }
        }

        return $patterns;
    }

    private function detectPreferences(array $conversations): array
    {
        $allPreferences = [];

        foreach ($conversations as $conversation) {
            $preferences = data_get($conversation, 'context.conversation.preferences');
            if ($preferences !== null && $preferences !== '') {
                $allPreferences[] = [
                    'dietary' => $this->extractDietaryPreferences($preferences),
                    'timing' => $this->extractTimingPreferences($preferences),
                ];
            }
        }

        return $allPreferences;
    }
}
