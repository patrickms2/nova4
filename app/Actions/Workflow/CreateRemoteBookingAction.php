<?php

namespace App\Actions\Workflow;

use App\Models\PublicBookingRequest;
use App\Models\Tour;
use App\Services\ExternalSync\RemoteBookingCreator;

class CreateRemoteBookingAction
{
    public function __construct(
        private readonly RemoteBookingCreator $remoteBookingCreator,
    ) {}

    /**
     * Create a remote booking using RemoteBookingCreator.
     *
     * Expected payload keys:
     *   - request_id: The PublicBookingRequest ID
     *   - service_id: The tour service ID
     */
    public function __invoke(array $payload): array
    {
        $requestId = $payload['request_id'] ?? null;
        $serviceId = $payload['service_id'] ?? null;

        if (! $requestId || ! $serviceId) {
            return ['error' => 'request_id and service_id are required.'];
        }

        try {
            $request = PublicBookingRequest::findOrFail((int) $requestId);
            $tour = Tour::query()->where('is_active', true)->findOrFail((int) $serviceId);

            $remoteBooking = $this->remoteBookingCreator->create($request, $tour);
            $request->refresh();

            $succeeded = in_array($request->remote_booking_status, ['created', 'skipped'], true);

            return [
                'success' => $succeeded,
                'result' => $succeeded ? 'success' : 'error',
                'request_id' => $request->id,
                'remote_booking_status' => $request->remote_booking_status,
                'remote_booking_id' => $request->remote_external_id,
                'remote_error' => $request->remote_error,
                'payment_status' => $request->payment_status,
                'payment_amount_cents' => $request->payment_amount_cents,
                'payment_amount_label' => sprintf('%.2f€', ($request->payment_amount_cents ?? 0) / 100),
                'pay_url' => $request->payment_provider === 'redsys' && $request->payment_status !== 'paid'
                    ? route('public.redsys.start', ['request' => $request->id])
                    : null,
            ];
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
