<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use App\Contracts\PaymentProvider;
use App\Contracts\PaymentResult;
use App\Models\Rental;

/**
 * Mock implementacja PaymentProvider — symuluje sandbox P24 lokalnie.
 *
 * Sluzy do smoke E2E i developmentu kiedy nie ma jeszcze prawdziwych
 * credentials sandbox P24 (ktore wymagaja konta produkcyjnego klienta).
 *
 * Flow:
 *  1. registerTransaction() generuje fake token (random) i redirectuje
 *     na lokalna strone /api/payment/mock/{rentalId}/checkout
 *  2. Strona checkout pokazuje 2 przyciski: "Zaplac OK" / "Anuluj"
 *  3. Klikniecie wola endpoint mock confirm/cancel ktory calluje
 *     RentalService::markPaid lub cancel + redirect do urlReturn
 *
 * NIE uzywaj na produkcji. Aktywuj przez env:
 *   RENTAL_PAYMENT_PROVIDER=App\Services\MockP24Provider
 */
final class MockP24Provider implements PaymentProvider
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function registerTransaction(Rental $rental): PaymentResult
    {
        $token = 'mock_'.Str::random(20);

        $redirectUrl = URL::route('rental.payment.mock.checkout', ['rentalId' => $rental->id]);

        return new PaymentResult(
            token: $token,
            redirectUrl: $redirectUrl,
            metadata: [
                'provider' => 'mock-p24',
                'sandbox' => true,
            ],
        );
    }

    public function verifyTransaction(string $sessionId, int $orderId, int $amount): bool
    {
        return true;
    }

    public function getWebhookUrl(): string
    {
        return URL::route('rental.payment.webhook');
    }

    public function getStatus(Rental $rental): string
    {
        return match ($rental->status) {
            'paid' => PaymentProvider::STATUS_PAID,
            'cancelled', 'expired' => PaymentProvider::STATUS_FAILED,
            'pending', 'confirmed' => PaymentProvider::STATUS_PENDING,
            default => PaymentProvider::STATUS_UNKNOWN,
        };
    }
}
