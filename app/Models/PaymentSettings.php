<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Model konfiguracji bramki platnosci.
 *
 * Credentials (merchant_id, crc, api_key) sa automatycznie
 * szyfrowane/deszyfrowane przez mutatory.
 *
 * Uzycie z Przelewy24Service:
 *   $settings = PaymentSettings::forTenant($tenantId)->first();
 *   $service = Przelewy24Service::fromSettings($settings);
 *
 * @property int $id
 * @property string|null $tenant_id
 * @property string $provider
 * @property string|null $merchant_id (decrypted)
 * @property string|null $pos_id (decrypted)
 * @property string|null $crc (decrypted)
 * @property string|null $api_key (decrypted)
 * @property bool $sandbox
 * @property bool $enabled
 * @property string $webhook_path
 * @property string $return_path
 * @property array|null $meta
 */
class PaymentSettings extends Model
{
    protected $table = 'payment_settings';

    protected $guarded = [];

    protected $casts = [
        'sandbox' => 'boolean',
        'enabled' => 'boolean',
        'meta' => 'array',
    ];

    /**
     * Encrypted attributes - handled by custom mutators.
     */
    protected array $encrypted = [
        'merchant_id',
        'pos_id',
        'crc',
        'api_key',
    ];

    // -------------------------------------------------------------------------
    // Encryption Mutators
    // -------------------------------------------------------------------------

    public function getMerchantIdAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setMerchantIdAttribute(?string $value): void
    {
        $this->attributes['merchant_id'] = $this->encryptValue($value);
    }

    public function getPosIdAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setPosIdAttribute(?string $value): void
    {
        $this->attributes['pos_id'] = $this->encryptValue($value);
    }

    public function getCrcAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setCrcAttribute(?string $value): void
    {
        $this->attributes['crc'] = $this->encryptValue($value);
    }

    public function getApiKeyAttribute(?string $value): ?string
    {
        return $this->decryptValue($value);
    }

    public function setApiKeyAttribute(?string $value): void
    {
        $this->attributes['api_key'] = $this->encryptValue($value);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope for specific tenant.
     */
    public function scopeForTenant($query, ?string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope for specific provider.
     */
    public function scopeForProvider($query, string $provider = 'przelewy24')
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope for enabled settings only.
     */
    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Check if credentials are configured (all required fields present).
     */
    public function isConfigured(): bool
    {
        return ! empty($this->merchant_id)
            && ! empty($this->crc)
            && ! empty($this->api_key);
    }

    /**
     * Get full webhook URL.
     */
    public function getWebhookUrl(): string
    {
        return rtrim(config('app.url'), '/').$this->webhook_path;
    }

    /**
     * Get full return URL.
     */
    public function getReturnUrl(): string
    {
        return rtrim(config('app.url'), '/').$this->return_path;
    }

    /**
     * Get or create settings for tenant + provider.
     */
    public static function getOrCreate(?string $tenantId = null, string $provider = 'przelewy24'): self
    {
        return static::firstOrCreate([
            'tenant_id' => $tenantId,
            'provider' => $provider,
        ], [
            'sandbox' => true,
            'enabled' => false,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private Encryption Helpers
    // -------------------------------------------------------------------------

    private function encryptValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Crypt::encryptString($value);
    }

    private function decryptValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            // Return raw value if decryption fails (e.g., old unencrypted data)
            return $value;
        }
    }
}
