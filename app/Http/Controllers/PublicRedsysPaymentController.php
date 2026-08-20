<?php

namespace App\Http\Controllers;

use App\Actions\Booking\FulfillPackageBookingRequest;
use App\Models\Booking;
use App\Models\ExternalBooking;
use App\Models\ExternalCatalogItem;
use App\Models\ExternalPayment;
use App\Models\PublicBookingRequest;
use App\Models\Tour;
use App\Services\Nova\NovaWhatsAppCloudService;
use App\Services\Payments\RedsysService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Redsys\Tpv\Tpv;

class PublicRedsysPaymentController extends Controller
{
    public function start(PublicBookingRequest $request, RedsysService $redsys): View
    {
        if ($request->type === 'package') {
            abort_unless($request->payment_provider === 'redsys', 409, 'Package payment must be configured for Redsys.');
        } else {
            abort_unless($request->remote_booking_status === 'created', 409, 'Booking must be created before payment.');
            abort_unless(in_array($request->remote_source_platform, ['latepoint', 'woo'], true), 409, 'Only configured tour payments are supported.');
            abort_unless(in_array($request->type, ['tour', 'transfer'], true), 409, 'Only tour payments are supported.');
        }

        $amountCents = $this->amountCents($request);
        abort_if($amountCents <= 0, 422, 'Invalid amount.');

        if ($request->payment_status === 'paid') {
            return view('public.redsys.already-paid', ['request' => $request]);
        }

        $order = $request->payment_order ?: $this->generateOrder($request->id);
        $merchantName = Str::limit((string) config('services.redsys.merchant_name', config('app.name')), 25, '');

        $request->forceFill([
            'payment_provider' => 'redsys',
            'payment_status' => 'pending',
            'payment_amount_cents' => $amountCents,
            'payment_order' => $order,
        ])->save();

        $tpv = new Tpv([
            'Environment' => (string) config('services.redsys.environment', 'test'),
            'MerchantCode' => $redsys->merchantCode(),
            'Key' => (string) (config('services.redsys.secret_key_base64') ?: config('redsys.Key')),
            'Terminal' => $redsys->terminal(),
            'TransactionType' => $redsys->transactionType(),
            'Currency' => $redsys->currency(),
            'MerchantName' => $merchantName,
            'Titular' => $merchantName,
            'ConsumerLanguage' => '001',
            'SignatureVersion' => 'HMAC_SHA256_V1',
        ]);

        $tpv->setFormHiddens([
            'Amount' => $amountCents / 100,
            'Order' => $order,
            'MerchantURL' => route('public.redsys.notify'),
            'UrlOK' => route('public.redsys.ok', ['request' => $request->id]),
            'UrlKO' => route('public.redsys.ko', ['request' => $request->id]),
            'ProductDescription' => Str::ascii(Str::limit((string) $request->service_name, 125, '')),
            'Titular' => Str::ascii(Str::limit((string) ($request->customer_name ?: $merchantName), 60, '')),
            'MerchantData' => Str::limit('REF: '.$request->request_reference, 1024, ''),
        ]);

        return view('public.redsys.redirect', [
            'endpoint' => $redsys->endpoint(),
            'formHiddens' => $tpv->getFormHiddens(),
            'request' => $request,
        ]);
    }

    public function notify(Request $httpRequest, RedsysService $redsys): string
    {

        $merchantParameters = (string) $httpRequest->input('Ds_MerchantParameters', '');
        $signatureVersion = (string) $httpRequest->input('Ds_SignatureVersion', '');
        $signature = (string) $httpRequest->input('Ds_Signature', '');

        abort_unless($signatureVersion !== '' && $merchantParameters !== '' && $signature !== '', 400, 'Missing parameters.');

        $decoded = $redsys->decodeMerchantParameters($merchantParameters);
        $order = (string) Arr::get($decoded, 'Ds_Order', Arr::get($decoded, 'Ds_Merchant_Order', Arr::get($decoded, 'DS_MERCHANT_ORDER', '')));
        abort_unless($order !== '', 400, 'Missing order.');

        abort_unless($redsys->verifySignature($merchantParameters, $signature, $order), 400, 'Invalid signature.');

        $request = PublicBookingRequest::query()->where('payment_order', $order)->first();
        if (! $request) {
            // Do not leak details to the gateway.

            return 'OK';
        }

        $this->applyGatewayResponse($request, $decoded, $redsys);

        return response('OK', 200);
    }

    public function ok(Request $httpRequest, PublicBookingRequest $request, RedsysService $redsys): View
    {
        $merchantParameters = (string) $httpRequest->query('Ds_MerchantParameters', '');

        if ($merchantParameters !== '') {
            $decoded = $redsys->decodeMerchantParameters($merchantParameters);
            $this->applyGatewayResponse($request, $decoded, $redsys);
        } elseif ($request->payment_status !== 'paid') {
            $request->forceFill([
                'payment_status' => 'paid',
                'payment_paid_at' => now(),
                'payment_raw' => array_filter([
                    'source' => 'url_ok',
                    'payment_order' => $request->payment_order,
                    'payment_amount_cents' => $request->payment_amount_cents,
                ]),
            ])->save();

            // For packages, fulfill first to create child bookings and external bookings
            if ($request->type === 'package') {
                app(FulfillPackageBookingRequest::class)->handle($request);
            }
            $this->markLocalBookingAsPaid($request);
            $this->upsertExternalRedsysPayment($request, [
                'Ds_Amount' => $request->payment_amount_cents,
                'Ds_Order' => $request->payment_order,
                'Ds_AuthorisationCode' => $request->payment_reference,
            ]);
            // For non-packages, fulfill after payment is recorded
            if ($request->type !== 'package') {
                app(FulfillPackageBookingRequest::class)->handle($request);
            }

            // Materialize as Booking for transfers/taxis
            if (in_array($request->type, ['transfer', 'taxi'], true)) {
                $request->materializeAsBooking();
            }
        }

        return view('public.redsys.result', ['request' => $request, 'status' => 'ok']);
    }

    public function ko(PublicBookingRequest $request): View
    {
        return view('public.redsys.result', ['request' => $request, 'status' => 'ko']);
    }

    private function amountCents(PublicBookingRequest $request): int
    {
        if (! blank($request->payment_amount_cents)) {
            return (int) $request->payment_amount_cents;
        }

        $adults = max(1, (int) ($request->adults ?? 1));

        // Try LatePoint synced service price (per language/service), fallback to Tour.base_price.
        $tour = Tour::query()->find($request->service_id);
        $unitEur = null;

        if ($tour) {
            $mapping = $tour->externalSyncMappings()
                ->latest('last_synced_at')
                ->latest('id')
                ->first();

            if ($mapping?->external_item_id) {
                $item = ExternalCatalogItem::query()->find($mapping->external_item_id);
                $raw = data_get($item?->metadata, 'raw', []);
                $value = data_get($raw, 'price');
                if ($value === null || $value === '') {
                    $value = data_get($raw, 'charge_amount');
                }
                if ($value !== null && $value !== '') {
                    $unitEur = (float) $value;
                }
            }
        }

        $unit = (int) round(((float) ($unitEur ?? ($tour?->base_price ?? 15.0))) * 100);

        return max(0, $unit * $adults);
    }

    private function markLocalBookingAsPaid(PublicBookingRequest $request): void
    {
        if (! Schema::hasTable('bookings') || blank($request->remote_external_id)) {
            return;
        }

        $prefix = $request->remote_source_platform === 'woo' ? 'WOO-' : 'LAT-';
        $booking = Booking::query()->where('booking_reference', $prefix.(string) $request->remote_external_id)->first();
        if (! $booking) {
            return;
        }

        $booking->forceFill([
            'payment_status' => 'Paid',
            'status' => 'Confirmed',
            'total_price' => $request->payment_amount_cents
                ? ((int) $request->payment_amount_cents / 100)
                : $booking->total_price,
            'last_updated' => now(),
        ])->save();
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function applyGatewayResponse(PublicBookingRequest $request, array $decoded, RedsysService $redsys): void
    {
        $responseCode = (string) Arr::get($decoded, 'Ds_Response', Arr::get($decoded, 'Ds_Merchant_Response', ''));
        $authCode = (string) Arr::get($decoded, 'Ds_AuthorisationCode', Arr::get($decoded, 'Ds_Merchant_AuthorisationCode', ''));
        $date = (string) Arr::get($decoded, 'Ds_Date', Arr::get($decoded, 'Ds_Merchant_Date', ''));
        $hour = (string) Arr::get($decoded, 'Ds_Hour', Arr::get($decoded, 'Ds_Merchant_Hour', ''));
        $amount = Arr::get($decoded, 'Ds_Amount', Arr::get($decoded, 'Ds_Merchant_Amount'));

        $paid = $redsys->isSuccessfulResponse($responseCode);

        $request->forceFill([
            'payment_status' => $paid ? 'paid' : 'failed',
            'payment_reference' => $authCode ?: $request->payment_reference,
            'payment_amount_cents' => is_numeric($amount) ? (int) $amount : $request->payment_amount_cents,
            'payment_paid_at' => $paid ? ($redsys->parseGatewayDateTime($date, $hour) ?? now()) : null,
            'payment_raw' => $decoded,
        ])->save();

        if ($paid) {
            // For packages, fulfill first to create child bookings and external bookings
            if ($request->type === 'package') {
                app(FulfillPackageBookingRequest::class)->handle($request);
            }
            $this->markLocalBookingAsPaid($request);
            $this->upsertExternalRedsysPayment($request, $decoded);
            // For non-packages, fulfill after payment is recorded
            if ($request->type !== 'package') {
                app(FulfillPackageBookingRequest::class)->handle($request);
            }
            $this->sendPaymentConfirmationWhatsApp($request);
        }
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function upsertExternalRedsysPayment(PublicBookingRequest $request, array $decoded): void
    {
        if ($request->type === 'package') {
            $request->loadMissing('items');

            foreach ($request->items as $item) {
                $childId = data_get($item->metadata, 'child_public_booking_request_id');

                if (blank($childId)) {
                    continue;
                }

                $child = PublicBookingRequest::query()->find($childId);

                if (! $child) {
                    continue;
                }

                $this->upsertExternalRedsysPayment($child, array_merge($decoded, [
                    'Ds_Amount' => $child->payment_amount_cents,
                    'Ds_Order' => $request->payment_order,
                    'Ds_AuthorisationCode' => $request->payment_reference,
                    'package_request_id' => $request->id,
                    'package_request_reference' => $request->request_reference,
                    'package_item_id' => $item->id,
                ]));
            }

            return;
        }

        if (! in_array($request->remote_source_platform, ['latepoint', 'woo'], true) || blank($request->remote_external_id)) {
            return;
        }

        $externalBooking = ExternalBooking::query()
            ->where('source_platform', $request->remote_source_platform)
            ->where('external_id', (string) $request->remote_external_id)
            ->latest('id')
            ->first();

        if (! $externalBooking) {
            return;
        }

        $amount = (int) Arr::get($decoded, 'Ds_Amount', $request->payment_amount_cents);
        $order = (string) Arr::get($decoded, 'Ds_Order', $request->payment_order);
        $authCode = (string) Arr::get($decoded, 'Ds_AuthorisationCode', $request->payment_reference);
        $externalPaymentId = blank(data_get($decoded, 'package_request_id'))
            ? 'redsys-'.$order
            : 'redsys-'.$order.'-'.$request->id;

        ExternalPayment::query()->updateOrCreate(
            [
                'source_platform' => $request->remote_source_platform,
                'external_id' => $externalPaymentId,
            ],
            [
                'server_id' => $externalBooking->server_id,
                'external_source_id' => $externalBooking->external_source_id,
                'business_name' => $externalBooking->business_name,
                'source_label' => $externalBooking->source_label,
                'external_token' => $order,
                'external_receipt_number' => $authCode ?: null,
                'external_order_id' => $order,
                'external_booking_id' => (string) $request->remote_external_id,
                'external_service_id' => $externalBooking->external_item_id,
                'service_name' => $externalBooking->service_name,
                'resource_type' => $externalBooking->resource_type,
                'target_model' => $externalBooking->target_model,
                'customer_name' => $externalBooking->customer_name,
                'customer_email' => $externalBooking->customer_email,
                'processor' => 'redsys',
                'payment_method' => 'card',
                'kind' => 'payment',
                'status' => 'paid',
                'amount' => $amount / 100,
                'currency' => $externalBooking->currency ?: 'EUR',
                'paid_at' => $request->payment_paid_at ?: now(),
                'metadata' => [
                    'redsys' => $decoded,
                    'public_booking_request_id' => $request->id,
                    'public_booking_request_reference' => $request->request_reference,
                    'package_request_id' => data_get($decoded, 'package_request_id'),
                    'package_request_reference' => data_get($decoded, 'package_request_reference'),
                    'package_item_id' => data_get($decoded, 'package_item_id'),
                ],
                'source_updated_at' => now(),
                'source_fingerprint' => sha1(json_encode(['redsys_payment', $order, $request->id, $authCode])),
                'last_synced_at' => now(),
            ],
        );

        $externalBooking->forceFill([
            'payment_status' => 'fully_paid',
            'total' => $amount / 100,
            'currency' => $externalBooking->currency ?: 'EUR',
            'last_synced_at' => now(),
        ])->save();

        if ($request->remote_source_platform === 'latepoint') {
            $this->markRemoteLatePointOrderAsPaid($request, $externalBooking);
        }
    }

    private function markRemoteLatePointOrderAsPaid(PublicBookingRequest $request, ExternalBooking $externalBooking): void
    {
        $source = $externalBooking->externalSource;
        if (! $source) {
            return;
        }

        $orderId = data_get($request->remote_response, 'order_id')
            ?: data_get($request->remote_response, 'data.order_id')
            ?: data_get($request->remote_response, 'booking.order_id')
            ?: data_get($externalBooking->metadata, 'raw.order_id');

        try {
            $http = Http::baseUrl(rtrim((string) ($source->api_url ?: $source->base_url), '/'))
                ->acceptJson()
                ->connectTimeout(10)
                ->timeout(30);

            if (app()->isLocal()) {
                $http = $http->withoutVerifying();
            }

            $token = data_get($source->credentials, 'access_token');
            if (! blank($token)) {
                $http = $http->withToken((string) $token);
            }

            $localHeader = data_get($source->settings, 'local_header') ?: data_get($source->server?->metadata, 'local_header');
            $localHeaderName = data_get($localHeader, 'name');
            $localHeaderValue = data_get($localHeader, 'value') ?: env((string) data_get($localHeader, 'env'));
            if (! blank($localHeaderName) && ! blank($localHeaderValue)) {
                $http = $http->withHeaders([(string) $localHeaderName => (string) $localHeaderValue]);
            }

            if (blank($orderId)) {
                $bookingResponse = $http->get('wp-json/nova/v1/latepoint/booking/'.(int) $request->remote_external_id.'/order')
                    ->throw()
                    ->json();

                $orderId = data_get($bookingResponse, 'order_id');
            }

            if (blank($orderId)) {
                $bookingResponse = $http->post('wp-json/wp-abilities/v1/abilities/latepoint/get-booking/run', [
                    'input' => [
                        'id' => (int) $request->remote_external_id,
                    ],
                ])->throw()->json();

                $orderId = data_get($bookingResponse, 'order_id');
            }

            if (blank($orderId)) {
                return;
            }

            $response = $http->post('wp-json/nova/v1/latepoint/order/'.(int) $orderId.'/paid', [
                'notes' => 'Pago Redsys: '.$request->payment_order,
            ]);

            if ($response->successful()) {
                return;
            }

            $http->post('wp-json/wp-abilities/v1/abilities/latepoint/update-order/run', [
                'input' => [
                    'id' => (int) $orderId,
                    'payment_status' => 'fully_paid',
                    'notes' => trim((string) data_get($request->remote_response, 'notes')."\nPago Redsys: ".$request->payment_order),
                ],
            ])->throw();
        } catch (\Throwable $exception) {
            Log::warning('Could not mark remote LatePoint order as paid', [
                'public_booking_request_id' => $request->id,
                'remote_external_id' => $request->remote_external_id,
                'order_id' => $orderId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function generateOrder(int $requestId): string
    {
        return substr(now()->format('His').'Id'.$requestId, -12);
    }

    private function sendPaymentConfirmationWhatsApp(PublicBookingRequest $request): void
    {
        if (blank($request->customer_phone)) {
            return;
        }

        try {
            $whatsapp = app(NovaWhatsAppCloudService::class);

            $message = match ($request->type) {
                'transfer' => sprintf(
                    '✅ ¡Pago recibido! Tu traslado de %s a %s %s a las %s para %s personas está confirmado. Te enviaremos la confirmación operativa a %s.',
                    $request->pickup_address ?? 'origen indicado',
                    $request->dropoff_address ?? 'destino indicado',
                    $request->tour_date?->format('d/m/Y') ?? 'fecha indicada',
                    $request->tour_schedule ?? 'hora indicada',
                    $request->passengers ?? $request->adults ?? 'las',
                    $request->customer_email ?? 'tu correo',
                ),
                'package' => sprintf(
                    '✅ ¡Pago recibido! Tu paquete de servicios está confirmado. Te enviaremos la confirmación operativa a %s.',
                    $request->customer_email ?? 'tu correo',
                ),
                default => sprintf(
                    '✅ ¡Pago recibido! Tu reserva está confirmada. Te enviaremos la confirmación operativa a %s.',
                    $request->customer_email ?? 'tu correo',
                ),
            };

            $whatsapp->sendText($request->customer_phone, $message);
        } catch (\Throwable $exception) {
            Log::warning('Could not send WhatsApp payment confirmation', [
                'public_booking_request_id' => $request->id,
                'phone' => $request->customer_phone,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
