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

    /**
     * @param array<array{id: string, title: string}> $buttons
     * @return array<string, mixed>
     */
    public function sendReplyButtons(string $to, string $bodyText, array $buttons, ?string $footerText = null, ?string $phoneNumberId = null): array
    {
        $phoneNumberId ??= (string) config('services.nova.whatsapp_phone_number_id');
        $accessToken = (string) config('services.nova.whatsapp_access_token');

        if (! $this->isConfigured($phoneNumberId, $accessToken)) {
            return [
                'sent' => false,
                'error' => 'WhatsApp Cloud credentials are not configured',
            ];
        }

        $interactive = [
            'type' => 'button',
            'body' => [
                'text' => $bodyText,
            ],
            'action' => [
                'buttons' => array_map(fn ($button) => [
                    'type' => 'reply',
                    'reply' => [
                        'id' => $button['id'],
                        'title' => $button['title'],
                    ],
                ], $buttons),
            ],
        ];

        if ($footerText !== null) {
            $interactive['footer'] = [
                'text' => $footerText,
            ];
        }

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($this->messagesEndpoint($phoneNumberId), [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $this->normalizePhone($to),
                'type' => 'interactive',
                'interactive' => $interactive,
            ]);

        return [
            'sent' => $response->successful(),
            'status' => $response->status(),
            'response' => $response->json() ?? $response->body(),
        ];
    }

    /**
     * @param array<array{id: string, title: string, description?: string}> $rows
     * @return array<string, mixed>
     */
    public function sendListMessage(string $to, string $bodyText, string $buttonText, string $sectionTitle, array $rows, ?string $footerText = null, ?string $phoneNumberId = null): array
    {
        $phoneNumberId ??= (string) config('services.nova.whatsapp_phone_number_id');
        $accessToken = (string) config('services.nova.whatsapp_access_token');

        if (! $this->isConfigured($phoneNumberId, $accessToken)) {
            return [
                'sent' => false,
                'error' => 'WhatsApp Cloud credentials are not configured',
            ];
        }

        $interactive = [
            'type' => 'list',
            'header' => [
                'type' => 'text',
                'text' => 'Asistente de facturación',
            ],
            'body' => [
                'text' => $bodyText,
            ],
            'action' => [
                'button' => $buttonText,
                'sections' => [
                    [
                        'title' => $sectionTitle,
                        'rows' => array_map(fn ($row) => [
                            'id' => $row['id'],
                            'title' => $row['title'],
                            'description' => $row['description'] ?? '',
                        ], $rows),
                    ],
                ],
            ],
        ];

        if ($footerText !== null) {
            $interactive['footer'] = [
                'text' => $footerText,
            ];
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->normalizePhone($to),
            'type' => 'interactive',
            'interactive' => $interactive,
        ];

        \Log::info('WhatsApp list message payload', [
            'payload' => $payload,
        ]);

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->post($this->messagesEndpoint($phoneNumberId), $payload);

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
