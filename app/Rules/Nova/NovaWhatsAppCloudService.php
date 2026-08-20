<?php

declare(strict_types=1);

namespace App\Services\Nova;

use Illuminate\Support\Facades\Http;

final class NovaWhatsAppCloudService
{
    /**
     * @return array<string, mixed>
     */
    public function sendText(string $to, string $message): array
    {
        $phoneNumberId = (string) config('services.nova.whatsapp_phone_number_id');
        $accessToken = (string) config('services.nova.whatsapp_access_token');

        if (! $this->isConfigured($phoneNumberId, $accessToken)) {
            return [
                'sent' => false,
                'error' => 'WhatsApp Cloud credentials are not configured',
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($this->messagesEndpoint($phoneNumberId), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhone($to),
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message,
                ],
            ]);

        return [
            'sent' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function markAsRead(string $messageId, ?string $phoneNumberId = null): array
    {
        $phoneNumberId ??= (string) config('services.nova.whatsapp_phone_number_id');
        $accessToken = (string) config('services.nova.whatsapp_access_token');

        if (! $this->isConfigured($phoneNumberId, $accessToken)) {
            return [
                'sent' => false,
                'error' => 'WhatsApp Cloud credentials are not configured',
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($this->messagesEndpoint($phoneNumberId), [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ]);

        return [
            'sent' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function sendReaction(string $to, string $messageId, string $emoji, ?string $phoneNumberId = null): array
    {
        $phoneNumberId ??= (string) config('services.nova.whatsapp_phone_number_id');
        $accessToken = (string) config('services.nova.whatsapp_access_token');

        if (! $this->isConfigured($phoneNumberId, $accessToken)) {
            return [
                'sent' => false,
                'error' => 'WhatsApp Cloud credentials are not configured',
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($this->messagesEndpoint($phoneNumberId), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhone($to),
                'type' => 'reaction',
                'reaction' => [
                    'message_id' => $messageId,
                    'emoji' => $emoji,
                ],
            ]);

        return [
            'sent' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ];
    }

    private function isConfigured(string $phoneNumberId, string $accessToken): bool
    {
        return $phoneNumberId !== '' && $accessToken !== '';
    }

    private function messagesEndpoint(string $phoneNumberId): string
    {
        $version = (string) config('services.nova.meta_graph_version', 'v22.0');

        return "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?: $phone;
    }
}
