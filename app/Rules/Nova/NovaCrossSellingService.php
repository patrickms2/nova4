<?php

declare(strict_types=1);

namespace App\Services\Nova;

final class NovaCrossSellingService
{
    /**
     * Get cross-selling suggestions based on current business and intent
     */
    public function suggestCrossSelling(string $currentBusiness, string $intent): array
    {
        return match ($currentBusiness) {
            'la-geria' => $this->suggestFromLaGeria($intent),
            'lanzaloe' => $this->suggestFromLanzaloe($intent),
            'sirvo' => $this->suggestFromSirvo($intent),
            'taxilanz' => $this->suggestFromTaxilanz($intent),
            default => [],
        };
    }

    /**
     * Cross-selling from La Geria
     */
    private function suggestFromLaGeria(string $intent): array
    {
        return match ($intent) {
            'restaurant_booking' => [
                [
                    'target' => 'lanzaloe',
                    'message' => '¿Te interesa visitar la finca de aloe vera de Lanzaloe después de cenar? Tienen visitas guiadas muy interesantes.',
                    'priority' => 'medium',
                ],
                [
                    'target' => 'taxilanz',
                    'message' => '¿Necesitas un taxi para llegar a la bodega o para volver después?',
                    'priority' => 'high',
                ],
            ],
            'winery_visit' => [
                [
                    'target' => 'lanzaloe',
                    'message' => '¿Te gustaría probar los productos de aloe vera de Lanzaloe? Usan los vinos de La Geria en sus tratamientos de vinoterapia.',
                    'priority' => 'medium',
                ],
                [
                    'target' => 'sirvo',
                    'message' => '¿Te apetece cenar en Taberna La Cepa después de la visita? Tenemos mesas disponibles.',
                    'priority' => 'high',
                ],
            ],
            default => [],
        };
    }

    /**
     * Cross-selling from Lanzaloe
     */
    private function suggestFromLanzaloe(string $intent): array
    {
        return match ($intent) {
            'winery_visit' => [
                [
                    'target' => 'la-geria',
                    'message' => '¿Te gustaría visitar la bodega de La Geria? Sus vinos son los que usamos en nuestra vinoterapia.',
                    'priority' => 'high',
                ],
                [
                    'target' => 'taxilanz',
                    'message' => '¿Quieres un taxi para llegar a la finca o para ir a la bodega después?',
                    'priority' => 'medium',
                ],
            ],
            'restaurant_booking' => [
                [
                    'target' => 'la-geria',
                    'message' => '¿Te interesa cenar en Taberna La Cepa? Está muy cerca de la finca.',
                    'priority' => 'medium',
                ],
            ],
            default => [],
        };
    }

    /**
     * Cross-selling from Sirvo
     */
    private function suggestFromSirvo(string $intent): array
    {
        return match ($intent) {
            'restaurant_booking' => [
                [
                    'target' => 'la-geria',
                    'message' => '¿Te interesa una visita a la bodega después de cenar? Tenemos visitas guiadas a las 11:00 y 16:00.',
                    'priority' => 'high',
                ],
                [
                    'target' => 'taxilanz',
                    'message' => '¿Necesitas un taxi para volver al hotel después de cenar?',
                    'priority' => 'medium',
                ],
            ],
            default => [],
        };
    }

    /**
     * Cross-selling from Taxilanz
     */
    private function suggestFromTaxilanz(string $intent): array
    {
        return match ($intent) {
            'taxi_booking' => [
                [
                    'target' => 'la-geria',
                    'message' => '¿Te interesa visitar la bodega de La Geria? Puedo llevarte allí directamente.',
                    'priority' => 'high',
                ],
                [
                    'target' => 'sirvo',
                    'message' => '¿Quieres cenar en Sirvo? Puedo llevarte al restaurante.',
                    'priority' => 'medium',
                ],
                [
                    'target' => 'lanzaloe',
                    'message' => '¿Te apetece visitar la finca de aloe vera de Lanzaloe? Tenemos rutas turísticas que pasan por allí.',
                    'priority' => 'medium',
                ],
            ],
            default => [],
        };
    }

    /**
     * Get proactive suggestion based on booking details
     */
    public function getProactiveSuggestion(array $bookingDetails): ?string
    {
        $intent = $bookingDetails['intent'] ?? '';
        $date = $bookingDetails['date'] ?? '';
        $time = $bookingDetails['time'] ?? '';

        // If booking is for evening, suggest dinner
        if ($time !== '' && (int) substr($time, 0, 2) >= 20) {
            return match ($intent) {
                'winery_visit' => '¿Te apetece cenar en Taberna La Cepa después de la visita? Tenemos mesas disponibles.',
                default => null,
            };
        }

        // If booking is for morning, suggest taxi
        if ($time !== '' && (int) substr($time, 0, 2) < 12) {
            return match ($intent) {
                'restaurant_booking', 'winery_visit' => '¿Necesitas un taxi para llegar por la mañana?',
                default => null,
            };
        }

        return null;
    }

    /**
     * Format cross-selling suggestion naturally
     */
    public function formatSuggestion(array $suggestion): string
    {
        return $suggestion['message'] ?? '';
    }

    /**
     * Get random suggestion from available options
     */
    public function getRandomSuggestion(string $currentBusiness, string $intent): ?string
    {
        $suggestions = $this->suggestCrossSelling($currentBusiness, $intent);

        if (empty($suggestions)) {
            return null;
        }

        $randomIndex = array_rand($suggestions);

        return $this->formatSuggestion($suggestions[$randomIndex]);
    }
}
