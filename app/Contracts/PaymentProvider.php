<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\Rental;
use App\Services\Przelewy24Service;

/**
 * Interface for payment gateway providers.
 *
 * Implementations handle communication with payment processors (Przelewy24, Stripe, etc.).
 * Credentials are passed via constructor or configure() method, NOT from .env,
 * allowing multi-tenant setups where each tenant has different payment settings.
 *
 * @see Przelewy24Service
 */
interface PaymentProvider
{
    /**
     * Check if the provider has valid credentials configured.
     */
    public function isConfigured(): bool;

    /**
     * Register a new transaction with the payment gateway.
     *
     * @param  Rental  $rental  The rental to create payment for
     * @return PaymentResult Token and redirect URL for the payment
     *
     * @throws \RuntimeException If provider is not configured or API call fails
     */
    public function registerTransaction(Rental $rental): PaymentResult;

    /**
     * Verify a completed transaction (called from webhook).
     *
     * @param  string  $sessionId  Session ID from the original transaction
     * @param  int  $orderId  Order ID returned by the payment gateway
     * @param  int  $amount  Amount in minor units (grosze for PLN)
     * @return bool True if verification succeeded
     */
    public function verifyTransaction(string $sessionId, int $orderId, int $amount): bool;

    /**
     * Get the webhook/notification URL for this provider.
     *
     * @return string Full URL where payment gateway should send notifications
     */
    public function getWebhookUrl(): string;

    /**
     * Get current payment status for a rental from the gateway.
     *
     * Returns one of PaymentProvider status constants. Allows reconciliation
     * (polling) gdy webhook nie dotarl lub do diagnostyki.
     *
     * @return string One of: 'pending', 'paid', 'failed', 'unknown'
     */
    public function getStatus(Rental $rental): string;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNKNOWN = 'unknown';
}
