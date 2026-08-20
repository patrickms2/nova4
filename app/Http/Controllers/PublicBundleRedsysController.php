<?php

namespace App\Http\Controllers;

use App\Models\NovaBundleOrder;
use App\Services\Nova\NovaBundleOrderService;
use App\Services\Payments\RedsysService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Redsys\Tpv\Tpv;

class PublicBundleRedsysController extends Controller
{
    public function start(NovaBundleOrder $bundle, RedsysService $redsys): View
    {
        abort_if($bundle->payment_status === 'paid', 409, 'El pedido ya está pagado.');

        $total = (float) ($bundle->la_geria_total ?? 0);
        abort_if($total <= 0, 422, 'Importe inválido para pago.');

        $order = $bundle->redsys_order ?: $this->generateOrder($bundle->id);

        if (! $bundle->redsys_order) {
            $bundle->update(['redsys_order' => $order, 'payment_status' => 'pending']);
        }

        $merchantName = Str::limit((string) config('services.redsys.merchant_name', config('app.name')), 25, '');
        $customerName = ($bundle->customer_data['first_name'] ?? '').' '.($bundle->customer_data['last_name'] ?? '');

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
            'Amount' => $total,
            'Order' => $order,
            'MerchantURL' => route('bundle.redsys.notify'),
            'UrlOK' => route('bundle.redsys.ok', $bundle),
            'UrlKO' => route('bundle.redsys.ko', $bundle),
            'ProductDescription' => Str::ascii(Str::limit('Pedido cruzado '.$bundle->bundle_reference, 125, '')),
            'Titular' => Str::ascii(Str::limit((string) ($customerName ?: $merchantName), 60, '')),
            'MerchantData' => Str::limit('BUNDLE:'.$bundle->id, 1024, ''),
        ]);

        return view('public.redsys.redirect', [
            'endpoint' => $redsys->endpoint(),
            'formHiddens' => $tpv->getFormHiddens(),
        ]);
    }

    public function notify(Request $request, RedsysService $redsys): string
    {
        $merchantParameters = (string) $request->input('Ds_MerchantParameters', '');
        $signatureVersion = (string) $request->input('Ds_SignatureVersion', '');
        $signature = (string) $request->input('Ds_Signature', '');

        abort_unless($signatureVersion !== '' && $merchantParameters !== '' && $signature !== '', 400, 'Missing parameters.');

        $decoded = $redsys->decodeMerchantParameters($merchantParameters);
        $order = (string) Arr::get($decoded, 'Ds_Order', Arr::get($decoded, 'Ds_Merchant_Order', Arr::get($decoded, 'DS_MERCHANT_ORDER', '')));
        abort_unless($order !== '', 400, 'Missing order.');

        abort_unless($redsys->verifySignature($merchantParameters, $signature, $order), 400, 'Invalid signature.');

        $bundle = NovaBundleOrder::query()->where('redsys_order', $order)->first();
        if (! $bundle) {
            return 'OK';
        }

        $this->applyPayment($bundle, $decoded, $redsys);

        return response('OK', 200);
    }

    public function ok(Request $request, NovaBundleOrder $bundle, RedsysService $redsys): View
    {
        $merchantParameters = (string) $request->query('Ds_MerchantParameters', '');

        if ($merchantParameters !== '') {
            $decoded = $redsys->decodeMerchantParameters($merchantParameters);
            $this->applyPayment($bundle, $decoded, $redsys);
        } elseif ($bundle->payment_status !== 'paid') {
            $this->markPaid($bundle);
        }

        return view('public.redsys.result', ['request' => $bundle, 'status' => 'ok']);
    }

    public function ko(NovaBundleOrder $bundle): View
    {
        if ($bundle->payment_status !== 'paid') {
            $bundle->update(['payment_status' => 'failed']);
        }

        return view('public.redsys.result', ['request' => $bundle, 'status' => 'ko']);
    }

    private function applyPayment(NovaBundleOrder $bundle, array $decoded, RedsysService $redsys): void
    {
        $isSuccessful = $redsys->isSuccessfulResponse((string) Arr::get($decoded, 'Ds_Response'));

        $bundle->update([
            'payment_data' => [
                'decoded' => $decoded,
                'response_code' => Arr::get($decoded, 'Ds_Response'),
                'authorisation_code' => Arr::get($decoded, 'Ds_AuthorisationCode'),
                'date' => Arr::get($decoded, 'Ds_Date'),
                'hour' => Arr::get($decoded, 'Ds_Hour'),
            ],
        ]);

        if (! $isSuccessful) {
            $bundle->update(['payment_status' => 'failed']);

            return;
        }

        $this->markPaid($bundle);
    }

    private function markPaid(NovaBundleOrder $bundle): void
    {
        $service = app(NovaBundleOrderService::class);

        if ($bundle->la_geria_order_id) {
            $service->confirmLaGeriaPayment((int) $bundle->la_geria_order_id);
        }

        if ($bundle->lanzaloe_order_id && is_numeric($bundle->lanzaloe_order_id)) {
            $service->confirmLanzaloePayment((int) $bundle->lanzaloe_order_id);
        }

        $bundle->update([
            'payment_status' => 'paid',
            'la_geria_status' => 'completed',
            'lanzaloe_status' => 'paid',
            'paid_at' => now(),
        ]);
    }

    private function generateOrder(int $bundleId): string
    {
        return substr(str_pad((string) $bundleId, 12, '0', STR_PAD_LEFT), -12);
    }
}
