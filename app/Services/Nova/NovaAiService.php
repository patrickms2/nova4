<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Ai\Agents\NovaBookingExtractionAgent;
use App\Ai\Agents\NovaIntentAgent;
use App\Ai\Agents\NovaResponseAgent;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Enums\Lab;

class NovaAiService
{
    private bool $enabled;

    public function __construct()
    {
        $provider = (string) config('nova_ai.provider', 'openai');

        $this->enabled = $provider === 'ollama'
            || filled(config("ai.providers.{$provider}.key"));
    }

    /**
     * Detect user intent using AI.
     *
     * @return array{intent:string,confidence:float,reasoning:string|null}
     */
    public function detectIntent(string $message, ?array $context = null): array
    {
        if (! $this->enabled) {
            return $this->fallbackIntentDetection($message);
        }

        try {
            $response = (new NovaIntentAgent)->prompt(
                $this->formatIntentDetectionMessage($message, $context),
                provider: $this->provider(),
                model: $this->model(),
            );

            $result = $this->decodeJson($response->text);

            return [
                'intent' => $result['intent'] ?? 'unknown',
                'confidence' => (float) ($result['confidence'] ?? 0.5),
                'reasoning' => $result['reasoning'] ?? null,
            ];
        } catch (\Throwable $exception) {
            Log::error('AI intent detection failed', [
                'message' => $message,
                'error' => $exception->getMessage(),
            ]);

            return $this->fallbackIntentDetection($message);
        }
    }

    /**
     * Extract booking data using AI.
     *
     * @return array{date:array|null,time:array|null,party_size:int|null,customer_name:string|null,customer_phone:string|null,preferences:string|null}
     */
    public function extractBookingData(string $message, ?array $context = null): array
    {
        if (! $this->enabled) {
            return $this->fallbackBookingDataExtraction($message, $context);
        }

        try {
            $response = (new NovaBookingExtractionAgent)->prompt(
                $this->formatBookingDataExtractionMessage($message, $context),
                provider: $this->provider(),
                model: $this->model(),
            );

            $result = $this->decodeJson($response->text);

            return [
                'date' => $result['date'] ?? null,
                'time' => $result['time'] ?? null,
                'party_size' => $result['party_size'] ?? null,
                'customer_name' => $result['customer_name'] ?? null,
                'customer_phone' => $result['customer_phone'] ?? null,
                'preferences' => $result['preferences'] ?? null,
                'origin' => $result['origin'] ?? null,
                'destination' => $result['destination'] ?? null,
            ];
        } catch (\Throwable $exception) {
            Log::error('AI booking data extraction failed', [
                'message' => $message,
                'error' => $exception->getMessage(),
            ]);

            return $this->fallbackBookingDataExtraction($message, $context);
        }
    }

    /**
     * Generate natural response using AI.
     *
     * @param  array<string, mixed>  $conversation
     */
    public function generateResponse(string $userMessage, array $conversation, ?array $context = null): string
    {
        if (! $this->enabled) {
            return $this->fallbackResponseGeneration($conversation);
        }

        try {
            $response = (new NovaResponseAgent)->prompt(
                $this->formatResponseGenerationMessage($userMessage, $conversation, $context),
                provider: $this->provider(),
                model: $this->responseModel(),
            );

            return $response->text;
        } catch (\Throwable $exception) {
            Log::error('AI response generation failed', [
                'conversation' => $conversation,
                'error' => $exception->getMessage(),
            ]);

            return $this->fallbackResponseGeneration($conversation);
        }
    }

    /**
     * Resolve the configured provider, including any failover providers.
     *
     * @return Lab|array<int, Lab>
     */
    private function provider(): Lab|array
    {
        $primary = Lab::from((string) config('nova_ai.provider', 'openai'));

        $failover = array_values(array_filter(array_map(
            static fn (string $name): ?Lab => Lab::tryFrom(trim($name)),
            (array) config('nova_ai.failover', []),
        )));

        if ($failover === []) {
            return $primary;
        }

        return array_values(array_unique([$primary, ...$failover], SORT_REGULAR));
    }

    private function model(): string
    {
        return (string) config('nova_ai.model', 'gpt-4o-mini');
    }

    private function responseModel(): string
    {
        return (string) config('nova_ai.response_model', $this->model());
    }

    /**
     * Decode a model response into an array, tolerating markdown code fences.
     *
     * @return array<string, mixed>
     */
    private function decodeJson(string $text): array
    {
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = trim((string) preg_replace('/^```(?:json)?|```$/m', '', $text));
        }

        $decoded = json_decode($text, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{.*\}/s', $text, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    private function formatIntentDetectionMessage(string $message, ?array $context): string
    {
        $contextInfo = $context !== null ? 'Previous context: '.json_encode($context, JSON_PRETTY_PRINT) : 'No previous context.';

        return "User message: \"{$message}\"\n\n{$contextInfo}\n\nDetect the user's primary intent.";
    }

    private function formatBookingDataExtractionMessage(string $message, ?array $context): string
    {
        $contextInfo = $context !== null ? 'Previous conversation data: '.json_encode($context, JSON_PRETTY_PRINT) : 'No previous data.';

        return "User message: \"{$message}\"\n\n{$contextInfo}\n\nExtract booking information.";
    }

    private function formatResponseGenerationMessage(string $userMessage, array $conversation, ?array $context): string
    {
        $conversationInfo = json_encode($conversation, JSON_PRETTY_PRINT);
        $contextInfo = $context !== null ? 'Additional context: '.json_encode($context, JSON_PRETTY_PRINT) : 'No additional context.';

        return "User said: \"{$userMessage}\"\n\nCurrent conversation state: {$conversationInfo}\n\n{$contextInfo}\n\nGenerate a helpful response.";
    }

    /**
     * Fallback intent detection using regex patterns (original method).
     */
    private function fallbackIntentDetection(string $message): array
    {
        $normalizedMessage = mb_strtolower($message);

        if (str_contains($normalizedMessage, 'taxi') || str_contains($normalizedMessage, 'traslado') || str_contains($normalizedMessage, 'recoger')) {
            return ['intent' => 'taxi_booking', 'confidence' => 0.8, 'reasoning' => 'Contains taxi/transport keywords'];
        }

        if (str_contains($normalizedMessage, 'restaurante') || str_contains($normalizedMessage, 'mesa') || str_contains($normalizedMessage, 'comer') || str_contains($normalizedMessage, 'cenar')) {
            $intent = str_contains($normalizedMessage, 'visita') || str_contains($normalizedMessage, 'bodega')
                ? 'restaurant_and_winery_visit'
                : 'restaurant_booking';

            return ['intent' => $intent, 'confidence' => 0.7, 'reasoning' => 'Contains restaurant keywords'];
        }

        if (str_contains($normalizedMessage, 'visita') || str_contains($normalizedMessage, 'bodega') || str_contains($normalizedMessage, 'geria')) {
            return ['intent' => 'winery_visit', 'confidence' => 0.8, 'reasoning' => 'Contains winery visit keywords'];
        }

        return ['intent' => 'unknown', 'confidence' => 0.3, 'reasoning' => 'No matching keywords found'];
    }

    /**
     * Fallback booking data extraction (simplified version).
     */
    private function fallbackBookingDataExtraction(string $message, ?array $context): array
    {
        return [
            'date' => null,
            'time' => null,
            'party_size' => null,
            'customer_name' => null,
            'customer_phone' => null,
            'preferences' => null,
        ];
    }

    /**
     * Fallback response generation (placeholder).
     */
    private function fallbackResponseGeneration(array $conversation): string
    {
        return 'Lo siento, estoy teniendo problemas para procesar tu solicitud. ¿Podrías intentar de nuevo?';
    }
}
