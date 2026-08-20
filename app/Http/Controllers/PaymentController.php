<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use App\Contracts\PaymentProvider;
use App\Models\Rental;
use App\Services\RentalService;

/**
 * Endpoints platnosci dla Rental.
 *
 *  - POST /api/rentals/{id}/payment        — inicjacja transakcji P24, zwraca redirectUrl
 *  - POST /api/payment/webhook             — odbior notyfikacji P24, weryfikacja, markPaid
 *
 * Wzorzec portu: blizne-art-cms PaymentController z dwoma adaptacjami:
 *  - dependency injection PaymentProvider (interfejs, nie hardcoded P24)
 *  - markPaid przez RentalService (wysylka emaila + spojnosc statusu)
 *
 * @see KML-0058 (E3)
 */
class PaymentController extends Controller
{
    public function __construct(
        protected PaymentProvider $provider,
        protected RentalService $service,
    ) {}

    /**
     * POST /api/rentals/{id}/payment
     * Inicjuje transakcje, zwraca redirectUrl. Zapisuje payment_session_id.
     */
    public function create(string $id): JsonResponse
    {
        $rental = Rental::find($id);

        if ($rental === null) {
            return response()->json(['message' => 'Rezerwacja nie znaleziona.'], 404);
        }

        if ($rental->status !== 'pending') {
            return response()->json([
                'message' => 'Rezerwacja nie wymaga platnosci.',
                'status' => $rental->status,
            ], 400);
        }

        if ((int) $rental->total_amount === 0) {
            return response()->json(['message' => 'Rezerwacja jest bezplatna.'], 400);
        }

        // KML-0047 ext: blokada re-payment online po pierwszej wplacie.
        // Dalsze rozliczenia (doplata/zwrot) sa obslugiwane wylacznie w biurze.
        if ((int) ($rental->paid_amount ?? 0) > 0) {
            return response()->json([
                'message' => 'Rezerwacja ma juz zaplate online — dalsze rozliczenia tylko w biurze.',
                'paid_amount' => (int) $rental->paid_amount,
            ], 409);
        }

        if (! $this->provider->isConfigured()) {
            Log::error('[rental.payment.create] PaymentProvider nie skonfigurowany');

            return response()->json([
                'message' => 'Platnosci online sa tymczasowo niedostepne. Prosimy o kontakt.',
            ], 503);
        }

        try {
            $result = $this->provider->registerTransaction($rental);

            $rental->update(['payment_session_id' => $result->token]);

            return response()->json([
                'redirect_url' => $result->redirectUrl,
                'token' => $result->token,
            ]);
        } catch (\Throwable $e) {
            Log::error('[rental.payment.create] '.$e->getMessage(), [
                'rental_id' => $rental->id,
            ]);

            return response()->json(['message' => 'Blad inicjalizacji platnosci.'], 500);
        }
    }

    /**
     * POST /api/payment/webhook
     *
     * Odbiera notyfikacje P24:
     *  sessionId  — id rezerwacji (przekazane przy register)
     *  orderId    — id transakcji u P24
     *  amount     — kwota w groszach (do weryfikacji sygnatury)
     *
     * Po pozytywnej weryfikacji: rental -> paid (przez RentalService),
     * co wysyla email RentalConfirmed (KML-0049).
     */
    public function webhook(Request $request): JsonResponse
    {
        $sessionId = (string) $request->input('sessionId', '');
        $orderId = $request->input('orderId');
        $amount = (int) $request->input('amount', 0);

        if ($sessionId === '' || $orderId === null) {
            return response()->json(['message' => 'Missing fields'], 400);
        }

        $rental = Rental::find($sessionId);

        if ($rental === null) {
            Log::warning('[rental.payment.webhook] Rental not found', ['sessionId' => $sessionId]);

            return response()->json(['message' => 'Rental not found'], 404);
        }

        $verified = $this->provider->verifyTransaction($sessionId, (int) $orderId, $amount);

        if (! $verified) {
            Log::warning('[rental.payment.webhook] Verification failed', [
                'sessionId' => $sessionId,
                'orderId' => $orderId,
            ]);

            return response()->json(['message' => 'Verification failed'], 400);
        }

        // Idempotencja — jesli juz paid, nie wysylaj emaila ponownie
        if ($rental->status === 'paid') {
            return response()->json(['status' => 'ok', 'idempotent' => true]);
        }

        $this->service->markPaid($rental, (string) $orderId);

        return response()->json(['status' => 'ok']);
    }
}
