<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use App\Contracts\PaymentProvider;
use App\Contracts\PaymentResult;
use App\Models\Rental;

/**
 * Przelewy24 payment gateway implementation.
 *
 * Generic gateway that reads credentials from constructor parameters,
 * not from .env. This allows multi-tenant setups where each tenant
 * has different P24 credentials stored in database.
 *
 * Usage:
 *   $service = new Przelewy24Service([
 *       'merchant_id' => 12345,
 *       'pos_id' => 12345,        // optional, defaults to merchant_id
 *       // payment CRC is loaded from configuration at runtime
 *       // payment API token is loaded from configuration at runtime
 *       'sandbox' => true,        // optional, defaults to true
 *       'app_url' => 'https://example.com', // optional, defaults to config('app.url')
 *       'webhook_path' => '/api/payment/webhook', // optional
 *       'return_path' => '/rezerwacja/status',    // optional
 *   ]);
 *
 * @see PaymentProvider
 */
class Przelewy24Service implements PaymentProvider
{
    private int $merchantId;

    private int $posId;

    private string $crc;

    private string $apiKey;

    private string $baseUrl;

    private string $appUrl;

    private string $webhookPath;

    private string $returnPath;

    private string $currency;

    private string $description;

    /**
     * @param  array{
     *     merchant_id: int,
     *     pos_id?: int,
     *     crc: string,
     *     api_key: string,
     *     sandbox?: bool,
     *     app_url?: string,
     *     webhook_path?: string,
     *     return_path?: string,
     *     currency?: string,
     *     description?: string,
     * }  $credentials
     */
    public function __construct(array $credentials)
    {
        $this->merchantId = (int) ($credentials['merchant_id'] ?? 0);
        $this->posId = (int) ($credentials['pos_id'] ?? $this->merchantId);
        $this->crc = $credentials['crc'] ?? '';
        $this->apiKey = $credentials['api_key'] ?? '';

        $sandbox = $credentials['sandbox'] ?? true;
        $this->baseUrl = $sandbox
            ? 'https://sandbox.przelewy24.pl'
            : 'https://secure.przelewy24.pl';

        $this->appUrl = $credentials['app_url'] ?? config('app.url');
        $this->webhookPath = $credentials['webhook_path'] ?? '/api/payment/webhook';
        $this->returnPath = $credentials['return_path'] ?? '/rezerwacja/status';
        $this->currency = $credentials['currency'] ?? 'PLN';
        $this->description = $credentials['description'] ?? 'Rezerwacja';
    }

    /**
     * Create instance from a settings model/array.
     *
     * Convenience factory for creating service from DB settings.
     *
     * @param  object|array  $settings  Object or array with p24_* fields
     */
    public static function fromSettings(object|array $settings): self
    {
        $s = is_array($settings) ? (object) $settings : $settings;

        return new self([
            'merchant_id' => $s->p24_merchant_id ?? $s->merchant_id ?? 0,
            'pos_id' => $s->p24_pos_id ?? $s->pos_id ?? null,
            'crc' => $s->p24_crc ?? $s->crc ?? '',
            'api_key' => $s->p24_api_key ?? $s->api_key ?? '',
            'sandbox' => $s->p24_sandbox ?? $s->sandbox ?? true,
            'app_url' => $s->app_url ?? config('app.url'),
            'webhook_path' => $s->p24_webhook_path ?? $s->webhook_path ?? '/api/payment/webhook',
            'return_path' => $s->p24_return_path ?? $s->return_path ?? '/rezerwacja/status',
            'currency' => $s->currency ?? 'PLN',
            'description' => $s->p24_description ?? $s->description ?? 'Rezerwacja',
        ]);
    }

    public function isConfigured(): bool
    {
        return $this->merchantId > 0
            && $this->crc !== ''
            && $this->apiKey !== '';
    }

    public function registerTransaction(Rental $rental): PaymentResult
    {
        if (! $this->isConfigured()) {
            throw new \RuntimeException(
                'Przelewy24 nie jest skonfigurowane (brak merchant_id, crc lub api_key).'
            );
        }

        $amount = (int) $rental->total_amount; // already in minor units (grosze)
        $sessionId = (string) $rental->id;
        $sign = $this->createSign($sessionId, $amount, $this->currency);

        $body = [
            'merchantId' => $this->merchantId,
            'posId' => $this->posId,
            'sessionId' => $sessionId,
            'amount' => $amount,
            'currency' => $this->currency,
            'description' => "{$this->description} #{$rental->id}",
            'email' => $rental->email,
            'country' => 'PL',
            'language' => 'pl',
            'urlReturn' => "{$this->appUrl}{$this->returnPath}?id={$rental->id}",
            'urlStatus' => $this->getWebhookUrl(),
            'sign' => $sign,
        ];

        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
        ])->post("{$this->baseUrl}/api/v1/transaction/register", $body);

        if (! $response->successful()) {
            throw new \RuntimeException(
                "P24 register failed: {$response->status()} {$response->body()}"
            );
        }

        $token = $response->json('data.token');

        return new PaymentResult(
            token: $token,
            redirectUrl: "{$this->baseUrl}/trnRequest/{$token}",
            metadata: [
                'sessionId' => $sessionId,
                'amount' => $amount,
            ],
        );
    }

    public function verifyTransaction(string $sessionId, int $orderId, int $amount): bool
    {
        $sign = $this->createVerifySign($sessionId, $orderId, $amount, $this->currency);

        $body = [
            'merchantId' => $this->merchantId,
            'posId' => $this->posId,
            'sessionId' => $sessionId,
            'amount' => $amount,
            'currency' => $this->currency,
            'orderId' => $orderId,
            'sign' => $sign,
        ];

        $response = Http::withHeaders([
            'Authorization' => $this->authHeader(),
        ])->put("{$this->baseUrl}/api/v1/transaction/verify", $body);

        if (! $response->successful()) {
            return false;
        }

        return $response->json('data.status') === 'success';
    }

    public function getWebhookUrl(): string
    {
        return rtrim($this->appUrl, '/').$this->webhookPath;
    }

    public function getStatus(Rental $rental): string
    {
        // P24 nie udostepnia query-status endpointu dla pojedynczej transakcji
        // bez orderId. Mapujemy status rezerwacji na status platnosci.
        return match ($rental->status) {
            'paid' => PaymentProvider::STATUS_PAID,
            'cancelled', 'expired' => PaymentProvider::STATUS_FAILED,
            'pending', 'confirmed' => PaymentProvider::STATUS_PENDING,
            default => PaymentProvider::STATUS_UNKNOWN,
        };
    }

    /**
     * Get the return URL for completed payments.
     */
    public function getReturnUrl(Rental $rental): string
    {
        return "{$this->appUrl}{$this->returnPath}?id={$rental->id}";
    }

    /**
     * Create SHA384 signature for P24 transaction register.
     */
    private function createSign(string $sessionId, int $amount, string $currency): string
    {
        $data = json_encode([
            'sessionId' => $sessionId,
            'merchantId' => $this->merchantId,
            'amount' => $amount,
            'currency' => $currency,
            'crc' => $this->crc,
        ], JSON_THROW_ON_ERROR);

        return hash('sha384', $data);
    }

    /**
     * Create SHA384 signature for P24 transaction verify.
     * Verify sign includes orderId — required by P24 API v2.1.
     */
    private function createVerifySign(string $sessionId, int $orderId, int $amount, string $currency): string
    {
        $data = json_encode([
            'sessionId' => $sessionId,
            'merchantId' => $this->merchantId,
            'amount' => $amount,
            'currency' => $currency,
            'orderId' => $orderId,
            'crc' => $this->crc,
        ], JSON_THROW_ON_ERROR);

        return hash('sha384', $data);
    }

    /**
     * Create Basic Auth header for P24 API.
     */
    private function authHeader(): string
    {
        return 'Basic '.base64_encode("{$this->posId}:{$this->apiKey}");
    }
}
