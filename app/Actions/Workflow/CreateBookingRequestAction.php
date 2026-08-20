<?php

namespace App\Actions\Workflow;

use App\Models\PublicBookingRequest;
use App\Models\Tour;
use App\Services\PublicBookingRequestAssigner;
use Illuminate\Support\Str;

class CreateBookingRequestAction
{
    public function __construct(
        private readonly PublicBookingRequestAssigner $assigner,
    ) {}

    /**
     * Create a PublicBookingRequest for a tour visit booking, matching the
     * real /explore flow (tour_date / tour_schedule, adults, assignment, base_price).
     *
     * Expected payload keys:
     *   - service_id   : The tour service ID
     *   - visit_date   : The date in YYYY-MM-DD format
     *   - visit_time   : The time in HH:MM format
     *   - participants : Number of participants (default 2)
     *   - customer_name: Customer name (or a combined "Name, email" string)
     *   - customer_email: Customer email
     *   - customer_phone: Customer phone (optional)
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function __invoke(array $payload): array
    {
        $serviceId = $payload['service_id'] ?? null;
        $visitDate = $payload['visit_date'] ?? null;
        $visitTime = $payload['visit_time'] ?? null;
        $participants = max(1, (int) ($payload['participants'] ?? 2));

        [$customerName, $customerEmail, $customerPhone] = $this->resolveCustomer($payload);

        if (! $serviceId || ! $visitDate || ! $visitTime || ! $customerName || ! $customerEmail || ! $customerPhone) {
            return ['error' => 'service_id, visit_date, visit_time, customer_name, customer_email, and customer_phone are required.'];
        }

        try {
            $tour = Tour::query()->where('is_active', true)->findOrFail((int) $serviceId);
            $assignment = $this->assigner->resolve('tour', $tour);

            $request = PublicBookingRequest::create([
                'request_reference' => $this->requestReference(),
                'type' => 'tour',
                'service_id' => $tour->getKey(),
                'service_name' => $tour->name,
                'assigned_admin_id' => $assignment['admin']?->id,
                'assignment_source' => $assignment['source'],
                'customer_name' => $customerName,
                'customer_email' => $customerEmail,
                'customer_phone' => $customerPhone,
                'status' => 'pending',
                'participants' => $participants,
                'adults' => $participants,
                'children' => 0,
                'tour_date' => $visitDate,
                'tour_schedule' => $visitTime,
                'base_price' => (float) ($tour->base_price ?? 0.0),
            ]);

            return [
                'success' => true,
                'request_id' => $request->id,
                'reference' => $request->request_reference,
                'status' => $request->status,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Resolve customer name, email and phone from explicit keys or a combined
     * free-text string like "Juan Pérez, juan@email.com, +34600111222".
     *
     * @param  array<string, mixed>  $payload
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function resolveCustomer(array $payload): array
    {
        $name = $payload['customer_name'] ?? null;
        $email = $payload['customer_email'] ?? null;
        $phone = $payload['customer_phone'] ?? null;

        $combined = collect([$name, $email, $phone])
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value))
            ->unique()
            ->implode(' ');

        $resolvedEmail = $this->extractEmail($combined);
        $resolvedPhone = is_string($phone) && $this->extractPhone($phone)
            ? $this->extractPhone($phone)
            : $this->extractPhone($combined);

        $resolvedName = $this->stripContacts($combined);

        return [
            blank($resolvedName) ? null : $resolvedName,
            $resolvedEmail,
            $resolvedPhone,
        ];
    }

    private function extractEmail(string $value): ?string
    {
        if (preg_match('/[\w.+-]+@[\w-]+\.[\w.-]+/', $value, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }

    private function extractPhone(string $value): ?string
    {
        $withoutEmail = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/', ' ', $value) ?? $value;

        if (preg_match('/\+?\d[\d\s().-]{6,}\d/', $withoutEmail, $matches) === 1) {
            return trim($matches[0]);
        }

        return null;
    }

    private function stripContacts(string $value): string
    {
        $clean = preg_replace('/[\w.+-]+@[\w-]+\.[\w.-]+/', '', $value) ?? $value;
        $clean = preg_replace('/\+?\d[\d\s().-]{6,}\d/', '', $clean) ?? $clean;

        return trim(trim($clean), " ,;\t\n\r");
    }

    private function requestReference(): string
    {
        do {
            $reference = 'REQ-'.now()->format('ymd').'-'.Str::upper(Str::random(6));
        } while (PublicBookingRequest::query()->where('request_reference', $reference)->exists());

        return $reference;
    }
}
