<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Rental;

/**
 * Email potwierdzajacy rezerwacje (po platnosci lub auto-confirm).
 *
 * Wysylany z RentalService::markPaid() i RentalService::confirm() gdy
 * config('rental.send_confirmation_email') === true.
 *
 * Aplikacja moze nadpisac szablon publikujac widoki:
 *   php artisan vendor:publish --tag=rental-views
 *   -> resources/views/vendor/rental/emails/rental-confirmed.blade.php
 *
 * Implementuje ShouldQueue — wysylka asynchronicznie. Jesli aplikacja nie ma
 * skonfigurowanego queue (QUEUE_CONNECTION=sync), maile ida natychmiast.
 */
class RentalConfirmed extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public Rental $rental) {}

    public function envelope(): Envelope
    {
        $subject = trans('rental::mail.confirmed_subject', [
            'id' => $this->shortId(),
        ], $this->rental->locale ?? 'pl');

        // Fallback gdy brak tlumaczen
        if ($subject === 'rental::mail.confirmed_subject') {
            $subject = "Potwierdzenie rezerwacji #{$this->shortId()}";
        }

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'rental::emails.rental-confirmed',
            with: [
                'rental' => $this->rental,
                'shortId' => $this->shortId(),
                'rentable' => $this->rental->rentable,
                'amount' => number_format($this->rental->total_amount / 100, 2, ',', ' '),
                'currency' => $this->rental->currency,
            ],
        );
    }

    /**
     * Skrocony identyfikator rezerwacji do wyswietlenia w mailu.
     */
    private function shortId(): string
    {
        return substr((string) $this->rental->id, 0, 8);
    }
}
