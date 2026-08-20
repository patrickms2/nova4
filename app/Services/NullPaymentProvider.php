<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\PaymentProvider;
use App\Contracts\PaymentResult;
use App\Models\Rental;

/**
 * No-op payment provider.
 *
 * Uzywany jako fallback gdy aplikacja nie ma skonfigurowanego providera
 * platnosci (config('rental.payment_provider') === null). Pozwala uzywac
 * reservation-system bez integracji platnosci (tylko email + manual confirm).
 *
 * isConfigured() zwraca false, dzieki czemu aplikacja moze tym warunkowac
 * UI (np. ukryc przycisk "Zaplac online"). Wywolanie registerTransaction
 * rzuca wyjatek — zapobiega nieoczekiwanemu try-pay.
 */
final class NullPaymentProvider implements PaymentProvider
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function registerTransaction(Rental $rental): PaymentResult
    {
        throw new \RuntimeException(
            'Brak skonfigurowanego providera platnosci. '
            .'Ustaw config("rental.payment_provider") na FQCN implementacji PaymentProvider '
            .'(np. \\Octadecimal\\Rental\\Services\\Przelewy24Service::class).'
        );
    }

    public function verifyTransaction(string $sessionId, int $orderId, int $amount): bool
    {
        return false;
    }

    public function getWebhookUrl(): string
    {
        return '';
    }

    public function getStatus(Rental $rental): string
    {
        return PaymentProvider::STATUS_UNKNOWN;
    }
}
