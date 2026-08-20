<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * DTO representing result of payment transaction registration.
 *
 * Returned by PaymentProvider::registerTransaction().
 */
final readonly class PaymentResult
{
    public function __construct(
        /**
         * Token returned by payment gateway (used for verification).
         */
        public string $token,

        /**
         * URL to redirect user for payment completion.
         */
        public string $redirectUrl,

        /**
         * Optional additional data from the gateway.
         */
        public array $metadata = [],
    ) {}

    /**
     * Create from array (e.g., from API response).
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            redirectUrl: $data['redirectUrl'] ?? $data['redirect_url'] ?? '',
            metadata: $data['metadata'] ?? [],
        );
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'redirectUrl' => $this->redirectUrl,
            'metadata' => $this->metadata,
        ];
    }
}
