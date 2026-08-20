<?php

declare(strict_types=1);

namespace App\Services\Nova;

use Carbon\CarbonImmutable;

final class NovaConversationDataExtractor
{
    /**
     * @param  array<string, mixed>|null  $previousConversation
     * @return array<string, mixed>
     */
    public function extract(string $message, string $touristPhone, ?array $previousConversation = null): array
    {
        $normalizedMessage = mb_strtolower($message);
        $currentPartySize = $this->extractPartySize($normalizedMessage);
        $currentTime = $this->extractTime($normalizedMessage);
        $currentDate = $this->extractDate($normalizedMessage);
        $intent = $this->detectIntent($normalizedMessage);
        $previousStage = (string) data_get($previousConversation, 'stage', '');
        $previousIntent = (string) data_get($previousConversation, 'intent', 'unknown');

        if ($previousStage === 'ready_to_confirm' && $intent === 'unknown') {
            if ($this->hasBookingDetails($currentDate, $currentTime, $currentPartySize)) {
                $intent = $previousIntent;
            }

            if (! $this->hasBookingDetails($currentDate, $currentTime, $currentPartySize)) {
                $previousIntent = 'unknown';
            }

            $previousConversation = null;
            $previousStage = '';
        }

        $partySize = $currentPartySize ?? data_get($previousConversation, 'party_size');
        $time = $currentTime ?? data_get($previousConversation, 'time');
        $date = $currentDate ?? data_get($previousConversation, 'date');
        $customerName = data_get($previousConversation, 'customer_name');
        $preferences = data_get($previousConversation, 'preferences');

        if ($previousStage === 'selecting_intent') {
            $intent = $this->intentFromSelection($normalizedMessage) ?? $intent;
        }

        if ($intent !== 'unknown' && $previousIntent !== 'unknown' && $intent !== $previousIntent) {
            $partySize = $this->extractPartySize($normalizedMessage);
            $time = $this->extractTime($normalizedMessage);
            $date = $this->extractDate($normalizedMessage);
            $customerName = null;
            $preferences = null;
        }

        if ($intent === 'unknown' && $this->hasBookingDetails($date, $time, is_numeric($partySize) ? (int) $partySize : null)) {
            $intent = (string) data_get($previousConversation, 'intent', 'unknown');
        }

        if ($intent === 'unknown' && $previousStage !== '') {
            $intent = (string) data_get($previousConversation, 'intent', 'unknown');
        }

        if ($previousStage === 'awaiting_customer_name' && ! $this->hasBookingDetails($this->extractDate($normalizedMessage), $this->extractTime($normalizedMessage), $this->extractPartySize($normalizedMessage))) {
            $customerName = trim($message);
        }

        if ($previousStage === 'awaiting_preferences') {
            $preferences = $this->extractPreferences($message);
        }

        $missingFields = [];
        $missingLabels = [];

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

        $stage = $this->resolveStage($intent, $missingFields, is_string($customerName) ? $customerName : null, is_string($preferences) ? $preferences : null);

        return [
            'tourist_phone' => $touristPhone,
            'intent' => $intent,
            'stage' => $stage,
            'date_label' => $date['label'] ?? null,
            'date' => $date,
            'time_label' => $time['label'] ?? null,
            'time' => $time,
            'party_size' => $partySize === null ? null : (int) $partySize,
            'customer_name' => is_string($customerName) && $customerName !== '' ? $customerName : null,
            'preferences' => is_string($preferences) && $preferences !== '' ? $preferences : null,
            'missing_fields' => $missingFields,
            'missing_labels' => $missingLabels,
        ];
    }

    public function detectIntent(string $message): string
    {
        $selectionIntent = $this->intentFromSelection($message);

        if ($selectionIntent !== null) {
            return $selectionIntent;
        }

        if ($this->isCommercialInfoRequest($message)) {
            return 'commercial_info';
        }

        if ($this->hasAnyTerm($message, ['taxi', 'taxis', 'traslado', 'trasladar', 'transfer', 'recoger', 'recogida', 'llevar', 'llévame', 'llevame', 'aeropuerto', 'puerto'])) {
            return 'taxi_booking';
        }

        if ($this->hasAnyTerm($message, ['restaurante', 'reserva', 'reservar', 'mesa', 'comer', 'cenar', 'almorzar', 'comida', 'carta', 'menú', 'menu', 'taberna', 'cepa'])) {
            return $this->hasAnyTerm($message, ['visita', 'bodega', 'geria', 'wine tour', 'tour'])
                ? 'restaurant_and_winery_visit'
                : 'restaurant_booking';
        }

        if ($this->hasAnyTerm($message, ['visita', 'visitar', 'bodega', 'geria', 'wine tour', 'cata', 'excursión', 'excursion', 'tour'])) {
            return 'winery_visit';
        }

        return 'unknown';
    }

    private function intentFromSelection(string $message): ?string
    {
        $message = trim($message);

        return match ($message) {
            '1', 'restaurante' => 'restaurant_booking',
            '2', 'visita', 'bodega' => 'winery_visit',
            '3', 'taxi' => 'taxi_booking',
            '4', 'info', 'información', 'informacion' => 'commercial_info',
            default => null,
        };
    }

    private function isCommercialInfoRequest(string $message): bool
    {
        if (in_array(trim($message), ['hacer', 'reservar', 'reserva'], true)) {
            return false;
        }

        $infoTerms = ['info', 'información', 'informacion', 'dime', 'cuéntame', 'cuentame', 'qué tipos', 'que tipos', 'tipos de', 'disponible', 'disponibles', 'ofrece', 'ofrecen', 'tienen', 'cuanto cuesta', 'cuánto cuesta', 'precio', 'precios', 'cuanto dura', 'cuánto dura', 'duración', 'duracion', 'contacto', 'telefono', 'teléfono', 'email', 'qué puedo comer', 'que puedo comer', 'carta', 'menú', 'menu', 'horario', 'cocina', 'qué hacer', 'que hacer', 'puedo hacer', 'planes', 'recomienda', 'recomiéndame', 'recomiendame', 'opciones', 'excursiones'];
        $commercialTerms = ['bodega', 'geria', 'vino', 'vinos', 'visita', 'visitas', 'taxi', 'taxis', 'traslado', 'traslados', 'lanzaloe', 'aloe', 'vinoterapia', 'restaurante', 'comida', 'cangrejo rojo', 'producto', 'productos', 'taberna', 'cepa', 'tapas', 'excursión', 'excursion', 'tour', 'hacer'];

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

    private function extractPreferences(string $message): string
    {
        $normalizedMessage = mb_strtolower(trim($message));

        if (in_array($normalizedMessage, ['no', 'ninguna', 'ninguno', 'sin alergias', 'sin preferencias'], true)) {
            return 'Sin alergias ni preferencias';
        }

        return trim($message);
    }

    /**
     * @param  array<int, string>  $missingFields
     */
    private function resolveStage(string $intent, array $missingFields, ?string $customerName, ?string $preferences): string
    {
        if ($intent === 'commercial_info') {
            return 'answering_commercial_info';
        }

        if (! in_array($intent, ['restaurant_booking', 'restaurant_and_winery_visit', 'winery_visit'], true)) {
            return $intent === 'taxi_booking' ? 'collecting_taxi_details' : 'selecting_intent';
        }

        if ($missingFields !== []) {
            return 'collecting_booking_details';
        }

        if ($customerName === null) {
            return 'awaiting_customer_name';
        }

        if ($preferences === null) {
            return 'awaiting_preferences';
        }

        return 'ready_to_confirm';
    }
}
