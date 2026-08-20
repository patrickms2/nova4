<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use App\Models\Rental;
use App\Services\RentalService;

/**
 * Symulacja P24 sandbox checkout — pozwala na pelen E2E flow rezerwacji
 * bez prawdziwych credentials P24 (ktore wymagaja konta produkcyjnego).
 *
 * Routy aktywne tylko gdy aplikacja uzywa MockP24Provider:
 *   RENTAL_PAYMENT_PROVIDER=App\Services\MockP24Provider
 *
 * Flow:
 *  GET  /api/payment/mock/{rentalId}/checkout — strona z 2 buttonami
 *  POST /api/payment/mock/{rentalId}/confirm  — symuluje sukces, markPaid + redirect success
 *  POST /api/payment/mock/{rentalId}/cancel   — symuluje anulowanie, cancel + redirect failure
 *
 * @internal nie uzywaj na produkcji
 */
class MockP24Controller extends Controller
{
    public function __construct(
        protected RentalService $service,
    ) {}

    public function checkout(string $rentalId): View
    {
        $rental = Rental::findOrFail($rentalId);

        return view('rental::mock-p24-checkout', [
            'rental' => $rental,
            'amount' => number_format($rental->total_amount / 100, 2, ',', ' '),
            'currency' => $rental->currency,
            'confirmUrl' => route('rental.payment.mock.confirm', ['rentalId' => $rental->id]),
            'cancelUrl' => route('rental.payment.mock.cancel', ['rentalId' => $rental->id]),
        ]);
    }

    public function confirm(string $rentalId): RedirectResponse
    {
        $rental = Rental::findOrFail($rentalId);

        if ($rental->status === 'pending' || $rental->status === 'confirmed') {
            $this->service->markPaid($rental, 'mock-order-'.substr($rentalId, 0, 8));
        }

        return redirect($this->successUrl($rentalId));
    }

    public function cancel(string $rentalId): RedirectResponse
    {
        $rental = Rental::findOrFail($rentalId);

        if (! in_array($rental->status, ['cancelled', 'expired', 'paid'], true)) {
            $this->service->cancel($rental);
        }

        return redirect($this->failureUrl($rentalId));
    }

    private function successUrl(string $rentalId): string
    {
        $base = rtrim((string) config('rental.frontend_url', ''), '/');

        if ($base === '') {
            return route('rental.payment.mock.checkout', ['rentalId' => $rentalId]).'?status=success';
        }

        return $base.'/rezerwacja/sukces?id='.$rentalId;
    }

    private function failureUrl(string $rentalId): string
    {
        $base = rtrim((string) config('rental.frontend_url', ''), '/');

        if ($base === '') {
            return route('rental.payment.mock.checkout', ['rentalId' => $rentalId]).'?status=failure';
        }

        return $base.'/rezerwacja/blad?id='.$rentalId;
    }
}
